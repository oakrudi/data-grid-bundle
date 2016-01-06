<?php
/**
 * @Author: Rúdi Rocha <rrocha@ventureoak.com>
 * @Company: VentureOak
 * Date: 7/23/15
 * Time: 10:39 AM
 */

namespace Ventureoak\DataGridBundle\Lib\Objects;

use Doctrine\DBAL\Types\Type;

class ColumnObject
{

    private $header;
    private $tableAlias;
    private $alias;
    private $extraParams = array();
    private $dbField;
    private $isCustomField = false;
    private $sortable;
    private $dataType;

    public function __construct($header, $tableAlias, $alias)
    {
        $this->header = $header;
        $this->tableAlias = $tableAlias;
        $this->alias = $alias;
        $this->sortable = true;
        $this->setDataType();
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param mixed $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * @return mixed
     */
    public function getTableAlias()
    {
        return $this->tableAlias;
    }

    /**
     * @param mixed $tableAlias
     */
    public function setTableAlias($tableAlias)
    {
        $this->tableAlias = $tableAlias;
    }

    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param mixed $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Extra Params should be used only for grid details like column with and classes , etc...
     * @return mixed
     */
    public function getExtraParams()
    {
        return $this->extraParams;

    }

    /**
     * @param array $params
     * @return $this
     */
    public function setExtraParams($params = array())
    {
        $this->extraParams = $params;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDbField()
    {
        return $this->dbField;
    }

    /**
     * @param mixed $dbField
     * @return $this
     */
    public function setDbField($dbField)
    {
        $this->dbField = $dbField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsCustomField()
    {
        return $this->isCustomField;
    }

    /**
     * @param bool $isCustomField
     * @return $this
     */
    public function setIsCustomField($isCustomField = true)
    {
        $this->isCustomField = $isCustomField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSortable()
    {
        return $this->sortable;
    }

    /**
     * @param mixed $sortable
     * @return $this
     */
    public function setSortable($sortable)
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param mixed $dataType
     *
     * @return $this
     */
    public function setDataType($dataType = Type::TEXT)
    {
        $this->dataType = $dataType;
        return $this;
    }


}
