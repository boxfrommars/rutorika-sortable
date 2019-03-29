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
     * @param string  $foreignPivotKey
     * @param string  $relatedPivotKey
     * @param string  $parentKey
     * @param string  $relatedKey
     * @param string  $relationName
     * @param string  $orderColumn     position column name
     */
    public function __construct(Builder $query, Model $parent, $table, $foreignPivotKey,
                                $relatedPivotKey, $parentKey, $relatedKey, $relationName = null, $orderColumn = null)
    {
        parent::__construct($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null);

        $this->setOrderColumn($orderColumn);
    }

    public function getRelatedKey()
    {
        return $this->relatedPivotKey;
    }

    public function getForeignKey()
    {
        return $this->foreignPivotKey;
    }
}
