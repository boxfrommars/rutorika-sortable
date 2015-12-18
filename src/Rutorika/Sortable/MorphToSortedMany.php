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
     * @param  Builder $query
     * @param  Model   $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $orderColumn
     * @param  string  $relationName
     * @param  bool    $inverse
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $name, $table, $foreignKey, $otherKey, $orderColumn, $relationName = null, $inverse = false)
    {
        parent::__construct($query, $parent, $name, $table, $foreignKey, $otherKey, $relationName, $inverse);

        $this->setOrderColumn($orderColumn);
    }
}
