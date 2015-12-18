<?php

namespace Rutorika\Sortable;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Many to many relation with sorting/ordering support.
 *
 * @method \Illuminate\Database\Query\Builder orderBy($column, $direction = 'asc')
 */
class BelongsToSortedMany extends BelongsToMany
{
    use ToSortedManyTrait;

    /**
     * Create a new belongs to many relationship instance.
     * Sets default ordering by $orderColumn column.
     *
     * @param Builder $query
     * @param Model   $parent
     * @param string  $table
     * @param string  $foreignKey
     * @param string  $otherKey
     * @param string  $relationName
     * @param string  $orderColumn  position column name
     */
    public function __construct(Builder $query, Model $parent, $table, $foreignKey, $otherKey, $relationName = null, $orderColumn)
    {
        parent::__construct($query, $parent, $table, $foreignKey, $otherKey, $relationName);

        $this->setOrderColumn($orderColumn);
    }
}
