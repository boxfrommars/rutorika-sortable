<?php

namespace Rutorika\Sortable;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Many to many relation with sorting/ordering support.
 *
 * @method \Illuminate\Database\Query\Builder orderBy($column, $direction = 'asc')
 */
class MorphToSortedMany extends MorphToMany
{
    use ToSortedManyTrait;

    /**
     * Create a new morph to many relationship instance.
     *
     * @param Builder $query
     * @param Model   $parent
     * @param string  $name
     * @param string  $table
     * @param string  $foreignPivotKey
     * @param string  $relatedPivotKey
     * @param string  $parentKey
     * @param string  $relatedKey
     * @param string  $orderColumn
     * @param string  $relationName
     * @param bool    $inverse
     */
    public function __construct(Builder $query, Model $parent, $name, $table, $foreignPivotKey,
                                $relatedPivotKey, $parentKey, $relatedKey, $orderColumn, $relationName = null, $inverse = false)
    {
        parent::__construct($query, $parent, $name, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey, $relatedKey, $relationName = null, $inverse);
        $this->setOrderColumn($orderColumn);
    }
}
