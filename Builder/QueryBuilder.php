<?php
namespace Ventureoak\DataGridBundle\Builder;
/**
 * @author     Jorge Meireles
 * @copyright  (c) 2015 VentureOak
 */
class QueryBuilder
{
    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @var string
     */
    protected $from = null;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $select = [];

    /**
     * @var array
     */
    protected $joins = [];

    /**
     * @var array
     */
    protected $groupBy = [];

    /**
     * @var array
     */
    protected $wheres = [];

    /**
     * @var array
     */
    protected $having = [];

    /**
     * @var array
     */
    protected $orders = [];

    /**
     * @var array
     */
    protected $joinMapper = [];

    /**
     * @var bool
     */
    protected $ignoreJoins = false;

    /**
     * @var bool
     */
    protected $ignoreWheres = false;

    /**
     * @var bool
     */
    protected $ignoreGroupBy = false;

    /**
     * @var bool
     */
    protected $ignoreHaving = false;

    /**
     * @var bool
     */
    protected $ignoreOrderBy = false;

    /**
     * @var int
     */
    protected $limit = 10;

    /**
     * @var int
     */
    protected $offset = 0;

    protected $ignoreLimit = false;
    protected $ignoreOffset = false;

    /**
     * @param $from
     * @return $this
     */
    public function from($from)
    {
        $this->validate($from);

        $this->from = $from;

        return $this;
    }

    /**
     * @param $value
     * @throws \InvalidArgumentException
     */
    private function validate($value)
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Needs to be a string and " . gettype($value) . " has given.");
        }
    }

    /**
     * @param $select
     * @return $this
     */
    public function select($select)
    {
        $this->validate($select);

        $this->select[] = $select;

        return $this;
    }

    /**
     * @param $where
     * @return $this
     */
    public function where($where)
    {
        $this->validate($where);

        $this->wheres[] = $where;

        return $this;
    }

    /**
     * @param $join
     * @return $this
     */
    public function join($join)
    {
        $this->validate($join);
        $this->joins[] = $join;

        return $this;
    }

    public function groupBy($group)
    {
        $this->validate($group);
        $this->groupBy[] = $group;
        return $this;
    }

    /**
     * @param $order
     * @param string $sort
     * @return $this
     */
    public function order($order, $sort = "DESC")
    {
        $this->orders[] = [
            'sort' => $sort,
            'order' => $order
        ];

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSql()
    {
        $query = 'SELECT ';

        if (!$this->select || !$this->from) {
            throw new \RuntimeException();
        }

        $ait = new \ArrayIterator($this->select);
        $cit = new \CachingIterator($ait);

        foreach ($cit as $select) {
            $query .= " " . $select;

            if ($cit->hasNext()) {
                $query .= ", ";
            }
        }

        $query .= " FROM " . $this->from . " ";

        if (!$this->ignoreJoins) {
            foreach ($this->joins as $join) {
                $query .= " " . $join;
            }
        }

        if ($this->wheres && !$this->ignoreWheres) {
            $query .= " WHERE ";

            $ait = new \ArrayIterator($this->wheres);
            $cit = new \CachingIterator($ait);

            foreach ($cit as $where) {
                $query .= " " . $where;
                if ($cit->hasNext()) {
                    $query .= " AND ";
                }
            }
        }
        if ($this->groupBy && !$this->ignoreGroupBy) {
            $query .= " GROUP BY ";
            $query .=  implode(',', $this->groupBy );
        }

        if ($this->having && !$this->ignoreHaving)
        {
            $query.= " HAVING ";
            $query.= implode(',', $this->having);
        }

        if ($this->orders && !$this->ignoreOrderBy) {
            $query .= " ORDER BY ";
            $count = count($this->orders);
            $i = 1;

            foreach ($this->orders as $key => $order) {
                $query .= " " . $order['order'] . " " . $order['sort'];
                if ($count > $i) {
                    $query .= ", ";
                }
                $i++;
            }
        }

        if ($this->getLimit() && !$this->getIgnoreLimit())
        {
            $query .= " LIMIT ".$this->getLimit();
            if ($this->getOffset() && !$this->getIgnoreOffset()) {
                $query .= " OFFSET ". $this->getOffset();
            }
        }

        return $query;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param $alias
     */
    public function addAlias($alias)
    {
        if (!in_array($alias, $this->aliases)) {
            $this->aliases[$alias] = $alias;
        }

    }

    /**
     * @param $havingStmt
     * @return $this
     */
    public function having($havingStmt)
    {
        $this->validate($havingStmt);
        $this->having[] = $havingStmt;

        return $this;
    }

    /**
     * @param boolean $ignoreJoins
     */
    public function setIgnoreJoins($ignoreJoins)
    {
        $this->ignoreJoins = $ignoreJoins;
    }

    /**
     * @param boolean $ignoreWheres
     */
    public function setIgnoreWheres($ignoreWheres)
    {
        $this->ignoreWheres = $ignoreWheres;
    }

    /**
     * @param boolean $ignoreGroupBy
     */
    public function setIgnoreGroupBy($ignoreGroupBy)
    {
        $this->ignoreGroupBy = $ignoreGroupBy;
    }

    /**
     * @param boolean $ignoreOrderBy
     */
    public function setIgnoreOrderBy($ignoreOrderBy)
    {
        $this->ignoreOrderBy = $ignoreOrderBy;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIgnoreLimit()
    {
        return $this->ignoreLimit;
    }

    /**
     * @param boolean $ignoreLimit
     * @return $this
     */
    public function setIgnoreLimit($ignoreLimit)
    {
        $this->ignoreLimit = $ignoreLimit;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIgnoreOffset()
    {
        return $this->ignoreOffset;
    }

    /**
     * @param boolean $ignoreOffset
     * @return $this
     */
    public function setIgnoreOffset($ignoreOffset)
    {
        $this->ignoreOffset = $ignoreOffset;
        return $this;
    }


}