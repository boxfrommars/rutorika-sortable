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
     * Define a polymorphic many-to-many relationship.
     * Notice that signature of this method is different than ->belongsToMany: second param is pivot position column name.
     * Other params is the same as ->belongsToMany has.
     *
     * Just copy of belongsToMany except last line where we return new BelongsToSortedMany instance with additional orderColumn param
     *
     * @param string $related
     * @param string $name
     * @param string $orderColumn
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param bool   $inverse
     *
     * @return MorphToSortedMany
     */
    public function morphToSortedMany($related, $name, $orderColumn = 'position', $table = null, $foreignPivotKey = null,
                                $relatedPivotKey = null, $parentKey = null,
                                $relatedKey = null, $inverse = false)
    {
        $caller = $this->guessBelongsToManyRelation();

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name . '_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // Now we're ready to create a new query builder for this related model and
        // the relationship instances for this relation. This relations will set
        // appropriate query constraints then entirely manages the hydrations.
        $table = $table ?: Str::plural($name);

        return new MorphToSortedMany(
            $instance->newQuery(), $this, $name, $table,
            $foreignPivotKey, $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $orderColumn, $caller, $inverse
        );
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string $orderColumn
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function morphedBySortedMany($related, $name, $orderColumn = 'position', $table = null, $foreignPivotKey = null,
                                  $relatedPivotKey = null, $parentKey = null, $relatedKey = null)
    {
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        // For the inverse of the polymorphic many-to-many relations, we will change
        // the way we determine the foreign and other keys, as it is the opposite
        // of the morph-to-many method since we're figuring out these inverses.
        $relatedPivotKey = $relatedPivotKey ?: $name . '_id';

        return $this->morphToSortedMany(
            $related, $name, $orderColumn, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey, $relatedKey, true
        );
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
    abstract protected function getRelations();

    /**
     * Get the relationship name of the belongs to many.
     *
     * @return string
     */
    abstract protected function guessBelongsToManyRelation();

    /**
     * Create a new model instance for a related model.
     *
     * @param string $class
     *
     * @return mixed
     */
    abstract protected function newRelatedInstance($class);

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    abstract public function getKeyName();
}
