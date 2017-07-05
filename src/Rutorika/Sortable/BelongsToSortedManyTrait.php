<?php

namespace Rutorika\Sortable;

/**
 * Class SortableTrait.
 *
 * @traitUses Illuminate\Database\Eloquent\Model
 */
trait BelongsToSortedManyTrait
{
    /**
     * Returns new BelongsToSortedMany relation.
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
    public function belongsToSortedMany($related, $orderColumn = 'position', $table = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->getRelations();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related();

        $otherKey = $otherKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        // Now we're ready to create a new query builder for the related model and
        // the relationship instances for the relation. The relations will set
        // appropriate query constraint and entirely manages the hydrations.
        $query = $instance->newQuery();

        return new BelongsToSortedMany($query, $this, $table, $foreignKey, $otherKey, $relation, $orderColumn);
    }

    /**
     * Get the joining table name for a many-to-many relation.
     *
     * @param string $related
     *
     * @return string
     */
    abstract public function joiningTable($related);

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
    abstract protected function getRelations();
}
