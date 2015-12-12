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
    protected $orderColumn;

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
     * @param string  $orderColumn position column name
     */
    public function __construct(Builder $query, Model $parent, $table, $foreignKey, $otherKey, $relationName = null, $orderColumn)
    {
        $this->orderColumn = $orderColumn;
        parent::__construct($query, $parent, $table, $foreignKey, $otherKey, $relationName);
        $this->withPivot($orderColumn);
        $this->orderBy($orderColumn, 'ASC');
    }

    /**
     * Attach a model to the parent.
     *
     * @param mixed $id
     * @param array $attributes
     * @param bool  $touch
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        $attributes[$this->getOrderColumnName()] = $this->getNextPosition();

        parent::attach($id, $attributes, $touch);
    }

    /**
     * Moves $entity before $positionEntity.
     *
     * @param Model $entity         What to move
     * @param Model $positionEntity Where to move
     */
    public function moveBefore($entity, $positionEntity)
    {
        $positionColumn = $this->getOrderColumnName();
        $query = $this->newPivotQuery();

        $entityPosition = $entity->pivot->$positionColumn;
        $positionEntityPosition = $positionEntity->pivot->$positionColumn;

        if ($entityPosition > $positionEntityPosition) {
            $query
                ->where($positionColumn, '>=', $positionEntityPosition)
                ->where($positionColumn, '<', $entityPosition)
                ->increment($positionColumn);

            $entity->pivot->$positionColumn = $positionEntityPosition;
            $positionEntity->pivot->$positionColumn = $positionEntityPosition + 1;

            $entity->pivot->save();
            $positionEntity->pivot->save();
        } elseif ($entityPosition < $positionEntityPosition) {
            $query
                ->where($positionColumn, '<', $positionEntityPosition)
                ->where($positionColumn, '>', $entityPosition)
                ->decrement($positionColumn);

            $entity->pivot->$positionColumn = $positionEntityPosition - 1;
            $entity->pivot->save();
        }
    }

    /**
     * Moves $entity after $positionEntity.
     *
     * @param Model $entity         What to move
     * @param Model $positionEntity Where to move
     */
    public function moveAfter($entity, $positionEntity)
    {
        $positionColumn = $this->getOrderColumnName();
        $query = $this->newPivotQuery();

        $entityPosition = $entity->pivot->$positionColumn;
        $positionEntityPosition = $positionEntity->pivot->$positionColumn;

        if ($entity->pivot->$positionColumn > $positionEntityPosition) {
            $query
                ->where($positionColumn, '>', $positionEntityPosition)
                ->where($positionColumn, '<', $entityPosition)
                ->increment($positionColumn);

            $entity->pivot->$positionColumn = $positionEntityPosition + 1;

            $entity->pivot->save();
        } elseif ($entityPosition < $positionEntityPosition) {
            $query
                ->where($positionColumn, '<=', $positionEntityPosition)
                ->where($positionColumn, '>', $entityPosition)
                ->decrement($positionColumn);

            $entity->pivot->$positionColumn = $positionEntityPosition;
            $positionEntity->pivot->$positionColumn = $positionEntityPosition - 1;

            $entity->pivot->save();
            $positionEntity->pivot->save();
        }
    }

    /**
     * Get position of new relation.
     *
     * @return float
     */
    protected function getNextPosition()
    {
        return 1 + $this->newPivotQuery()->max($this->getOrderColumnName());
    }

    /**
     * get position column name.
     *
     * @return string
     */
    protected function getOrderColumnName()
    {
        return $this->orderColumn;
    }
}
