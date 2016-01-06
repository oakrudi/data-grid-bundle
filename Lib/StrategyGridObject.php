<?php
/**
 * @Author: Rúdi Rocha <rrocha@ventureoak.com>
 * @Company: VentureOak
 */

namespace Ventureoak\DataGridBundle\Lib;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Ventureoak\DataGridBundle\Lib\Objects\ColumnObject;

abstract class StrategyGridObject
{
    private $em;
    private $translator;
    private $request;
    private $queryBuilder; //todo remove QueryBuilder Usage
    private $sqlQuery;
    private $render;
    private $columns = array();
    private $extraSortStaments = array();
    private $defaultSort = [];

    const GRID_LIMIT_PARAM = 'iDisplayLength';
    const GRID_OFFSET_PARAM = 'iDisplayStart';
    const GRID_SEARCH_TEXT = 'sSearch';
    const GRID_SORT_COL_PARAM = 'iSortCol_0';
    const GRID_SORT_TYPE = 'sSortDir_0';
    const EMPTY_YEAR = '-0001';

    /**
     * @param EntityManagerInterface $em
     * @param EngineInterface $twigEngine
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     */
    public function __construct(
        EntityManagerInterface $em,
        EngineInterface $twigEngine,
        TranslatorInterface $translator,
        RequestStack $requestStack
    )
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->render = $twigEngine;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param int $outputType
     * @return array
     */
    public function getResults($outputType = Query::HYDRATE_OBJECT)
    {
        return $this->getQueryBuilder()->getQuery()->getResult($outputType);
    }

    /**
     * Returns Json Object with configs for dataTable Grid
     * @param string $ajaxSource
     * @param bool $autoLoad
     * @return mixed
     * @throws \Exception
     */
    public function getGridObject($ajaxSource = '', $autoLoad = true)
    {
        //check ajax data source
        if (empty($ajaxSource)) {
            throw new \Exception('No Ajax Source Provided');
        }


        return json_encode($this->buildGridObject($ajaxSource, $this->getColumns(), $autoLoad));
    }

    /**
     * Build json code for data tables. This is used to init a new DataTable Object
     * @param $ajaxSource
     * @param $columns
     * @param bool $autoLoad
     * @return array
     */
    public function buildGridObject($ajaxSource, $columns, $autoLoad = true)
    {
        $grid = array(
            "dom" => '<"dt-toolbar">lrtip',
            "sAjaxSource" => sprintf("%s", $ajaxSource),
            "order" => $this->getDefaultSort(),
            "processing" => true,
            "paginate" => true,
            "deferLoading" => $autoLoad == false ?:null,
            "language" => [
                'emptyTable' => $this->getTranslator()->trans('datagrid.general.emptyTable',[], 'datagrid'),
                'info' => $this->getTranslator()->trans('datagrid.general.gridInfo',[],  'datagrid'),
                'zeroRecords' => $this->getTranslator()->trans('datagrid.general.gridNoRecords',[],  'datagrid'),
                'processing' => $this->getTranslator()->trans('datagrid.general.gridProcessing',[],  'datagrid'),
                'lengthMenu' => $this->getTranslator()->trans('datagrid.general.gridLengthMenu',[],  'datagrid'),
                'search' => $this->getTranslator()->trans('datagrid.general.gridSearchBox',[],  'datagrid'),
                'infoFiltered' => $this->getTranslator()->trans('datagrid.general.gridFilteredText',[],  'datagrid'),
                'paginate' => [
                    'next' => $this->getTranslator()->trans('datagrid.general.next',[],  'datagrid'),
                    'previous' => $this->getTranslator()->trans('datagrid.general.previous',[],  'datagrid'),
                ],
            ],
//            'scrollY' => '500', //fixing height of grid
            'lengthMenu' => [
                [10, 20, 40, 100],
                [10, 20, 40, 100]
            ],
            'fixedHeader' => true,
            "serverSide" => true,
            'columns' => array()
        );

        if (!$autoLoad) {
            $grid ['deferLoading'] = 1;
        }
        /**
         * ColumnObject $col
         */
        foreach ($columns as $col) {
            $column = array(
                'title' => $this->getTranslator()->trans($col->getHeader()),
                'data' => $col->getAlias(),
                'orderable' => $col->getSortable()

            );
            $extraParams = $col->getExtraParams();
            if (is_array($col->getExtraParams())) {
                foreach ($extraParams as $param => $value) {
                    $column[$param] = $value;
                }
            }

            $grid['columns'][] = $column;
        }

        return $grid;
    }

    public function getDefaultSort()
    {
        if (!empty($this->defaultSort)) {
            return $this->defaultSort;
        } else {
        return [[0, "asc"]];
        }
    }

    public function setDefaultSort($index, $sortType)
    {
        $this->defaultSort[] = [$index, $sortType];
    }

    /**
     * Automatic Mapping beetween row and gridColumn
     * @param $row
     * @param $gridColumns
     * @return array
     */
    public function mapAutomaticFields($row, $gridColumns)
    {
        $data = array();
        /**
         * @var ColumnObject $col
         */
        foreach ($gridColumns as $col) {
            if (isset($row[$col->getAlias()])) {
                //parse by field Types
                switch ($col->getDataType()) {

                    case TYPE::DATETIME:
                        $data[$col->getAlias()] = $this->parseDatetime($row[$col->getAlias()]);
                        break;
                    case Type::DATE:
                        $data[$col->getAlias()] = $this->parseDatetime($row[$col->getAlias()], 'd-m-Y');
                        break;
                    case Type::TIME:
                        $data[$col->getAlias()] = $this->parseDatetime($row[$col->getAlias()], 'H:i:s');
                        break;

                    default:
                        $data[$col->getAlias()] = $row[$col->getAlias()];
                        break;
                }

            } else {
                $data[$col->getAlias()] = '-';
            }
        }
        return $data;
    }

    /**
     * Return array object to be consumed by DataTables
     * @return array
     */
    public function getGridData()
    {
        if (!empty($this->getSqlQuery())) {

            /** @var \Doctrine\DBAL\Connection $conn */
            $conn = $this->getEntityManager()->getConnection();

            $sqlObj = $this->getSqlQuery();
            $sqlObj->setLimit($this->getRequest()->get(self::GRID_LIMIT_PARAM))
                ->setOffset($this->getRequest()->get(self::GRID_OFFSET_PARAM));

            $queryExec = $conn->executeQuery($this->getSqlQuery()->getSql());
            $data = $this->getFormattedData($queryExec->fetchAll());

            $totalRecords = ($conn->executeQuery($sqlObj->setIgnoreLimit(true)->getSql())->rowCount());

        } else {
            //generate a new QB for counting records
            $counterQb = clone($this->getQueryBuilder());

            //set grid limits and offset
            $this->getQueryBuilder()
                ->setMaxResults($this->getRequest()->get(self::GRID_LIMIT_PARAM))
                ->setFirstResult($this->getRequest()->get(self::GRID_OFFSET_PARAM));

            $rows = $this->getResults(Query::HYDRATE_ARRAY);
            $data = $this->getFormattedData($rows);

            //getTotal Records
            $alias = current($counterQb->getDQLPart('from'))->getAlias();
            $counterQb->resetDQLPart('groupBy'); //avoid error of multiple rows to getScalar
            $totalRecords = $counterQb->select(sprintf('count(%s.id)', $alias))
                ->getQuery()
                ->getSingleScalarResult();
        }

        return array(
            'data' => $data,
            'iTotalRecords' => $this->getRequest()->get(self::GRID_LIMIT_PARAM),
            'iTotalDisplayRecords' => $totalRecords
        );
    }

    /**
     * Set sort statement for query builder
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    public function setSortStatement(QueryBuilder $qb)
    {
        $cols = $this->getColumns();

        if ($this->getRequest()->has(self::GRID_SORT_COL_PARAM)
        ) {
            /**
             * ColumnObject $col
             */
            $col = $cols[$this->getRequest()->get(self::GRID_SORT_COL_PARAM)];
            if ($col->getIsCustomField()) {
                $sortableColumn = sprintf("%s.%s", $col->getTableAlias(), $col->getDbField());
            } else {
                $sortableColumn = sprintf("%s.%s", $col->getTableAlias(), $col->getAlias());
            }

            $qb->addOrderBy(
                $sortableColumn,
                $this->getRequest()->get(self::GRID_SORT_TYPE)
            );
        }

        // add sort statement
        if (count($this->extraSortStaments) > 0) {
            foreach ($this->extraSortStaments as $stmt) {

                $qb->addOrderBy(key($stmt), current($stmt));
            }
        }
        return $qb;
    }

    /**
     * Set Sort statement for SQL queries
     * @param \Ventureoak\DataGridBundle\Builder\QueryBuilder $qb
     * @return \Ventureoak\DataGridBundle\Builder\QueryBuilder
     */
    public function setSqlSortStatement(\Ventureoak\DataGridBundle\Builder\QueryBuilder $qb)
    {
        $cols = $this->getColumns();

        if ($this->getRequest()->has(self::GRID_SORT_COL_PARAM)
            && !empty($this->getRequest()->get(self::GRID_SORT_COL_PARAM))
        ) {
            /**
             * ColumnObject $col
             */
            $col = $cols[$this->getRequest()->get(self::GRID_SORT_COL_PARAM)];

            if ($col->getIsCustomField()) {
                if ($col->getTableAlias()) {
                    $sortableColumn = sprintf("%s.%s", $col->getTableAlias(), $col->getDbField());
                } else {
                    $sortableColumn = sprintf("%s", $col->getDbField());
                }
            } else {
                $sortableColumn = sprintf("%s.%s", $col->getTableAlias(), $col->getAlias());
            }

            $qb->order($sortableColumn, $this->getRequest()->get(self::GRID_SORT_TYPE));
        }
        return $qb;
    }

    /**
     * override this function inside your strategy
     * TODO: Implement generic function
     * @return array
     */
    public function exportData()
    {
        throw new NotImplementedException("Method [exportData] is not implemented");
    }

    /**
     * get data for grid
     * By default strategy tries to map all fields
     * If you need to add some logic override this function
     * @param $rows
     * @return array
     */
    public function getFormattedData($rows)
    {
        $data = [];
        foreach ($rows as $row) {
            $data = $this->mapAutomaticFields($row, $this->getColumns());
        }

        return $data;
    }

    /**
     * Translator object from Container
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    public function getRequest()
    {
        return $this->request->query;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param mixed $queryBuilder
     */
    public function setQueryBuilder($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return TwigEngine
     */
    public function getRender()
    {
        return $this->render;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return \Ventureoak\DataGridBundle\Builder\QueryBuilder
     */
    public function getSqlQuery()
    {
        return $this->sqlQuery;
    }

    /**
     * @param mixed $sqlQuery
     */
    public function setSqlQuery($sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    /**
     * Parse datetime value for grid
     * @param \Datetime $value
     * @param string $format
     * @return string
     */
    protected function parseDatetime($value, $format = 'Y-m-d H:i:s')
    {
        if (isset($value) &&
            !empty($value) &&
            $value->format('Y') != self::EMPTY_YEAR
        ) {
            return $value->format($format);
        } else {
            return '-';
        }
    }

    /**
     * @param ['field' => 'type] $statement
     */
    protected function addSortStatement($statement)
    {
        $this->extraSortStaments[] = $statement;
    }

}
