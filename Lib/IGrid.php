<?php
/**
 * User: rudi <rrocha@ventureoak.com>
 */

namespace Ventureoak\DataGridBundle\Lib;


use Doctrine\ORM\QueryBuilder;

interface IGrid
{

    /**
     * Set All grid Columns
     * This is your "grid Description"
     * @return mixed
     */
    public function defineGridColumns();

    /**
     * Returns Json Object with configs for dataTable Grid
     * @param string $ajaxSource
     * @param bool $autoLoad
     * @return mixed
     */
    public function getGridObject($ajaxSource = '', $autoLoad = true);

    /**
     * Returns JsonArray With data for Grid
     * @return mixed
     */
    public function getData();

    /**
     * Implement this function to set query filters
     * @param QueryBuilder | \Ventureoak\DataGridBundle\Builder\QueryBuilder $qb
     * @return QueryBuilder
     */
    public function setWhereStatement($qb);

    /**
     * GetData to be used at exports feature
     * @return mixed
     */
    public function exportData();
}