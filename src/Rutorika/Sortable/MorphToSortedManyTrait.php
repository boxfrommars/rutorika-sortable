<?php

namespace Rutorika\Sortable;

use Illuminate\Support\Str;

/**
 * Class MorphToSortedManyTrait.
 *
 * @traitUses Illuminate\Database\Eloquent\Model
 */
trait MorphToSortedManyTrait
{
    /**
     * Returns new MorphToSortedMany relation.
     * Notice that signature of this method is different than ->belongsToMany: second param is pivot position column name.
     * Other params is the same as ->belongsToMany has.
     *
     * Just copy of belongsToMany except last line where we return new BelongsToSortedMany instance with additional orderColumn param
     *
     * @param        $related
     * @param string $orderColumn
     * @param string $table
     * @param string $foreignKey
     * @param string $otherKey
     * @param string $relation
     *
     * @return BelongsToSortedMany
     */
    public function morphToSortedMany($related, $name, $orderColumn = 'position', $table = null, $foreignKey = null, $otherKey = null, $inverse = false)
    {
        $caller = $this->getBelongsToManyCaller();
        
        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        $foreignKey = $foreignKey ?: $name.'_id';
        
        $instance = new $related;
        
        $otherKey = $otherKey ?: $instance->getForeignKey();
        
        // Now we're ready to create a new query builder for this related model and
        // the relationship instances for this relation. This relations will set
        // appropriate query constraints then entirely manages the hydrations.
        $query = $instance->newQuery();
        
        $table = $table ?: Str::plural($name);
        
        return new MorphToSortedMany($query, $this, $name, $table, $foreignKey, $otherKey, $orderColumn);
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    abstract public function getForeignKey();

    /**
     * Get the relationship name of the belongs to many.
     *
     * @return string
     */
    abstract protected function getBelongsToManyCaller();
}
