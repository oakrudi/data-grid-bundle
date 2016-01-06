<?php

/**
 * User: Rúdi Rocha <rrocha@ventureoak.com>
 */

namespace Ventureoak\DataGridBundle;

class DataGridFactory
{
    /**
     * @var array
     */
    private $grids = [];

    /**
     * @param $grid
     * @param $alias
     */
    public function addGrid($grid, $alias)
    {
        $this->grids[$alias] = $grid;
    }

    /**
     * Returns GridObject Strategy
     * @param $name
     * @return IGrid
     * @throws \Exception
     */
    public function getGrid($name)
    {

        if (!isset($this->grids[$name])) {
            throw new \Exception("Grid $name does not exist.");
        }

        /** @var IGrid $grid */
        $grid = $this->grids[$name];
        $grid->defineGridColumns();

        return $grid;
    }

}