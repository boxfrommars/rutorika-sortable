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
     * @param string  $orderColumn  position column name
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
        $this->move('moveBefore', $entity, $positionEntity);
    }

    /**
     * Moves $entity after $positionEntity.
     *
     * @param Model $entity         What to move
     * @param Model $positionEntity Where to move
     */
    public function moveAfter($entity, $positionEntity)
    {
        $this->move('moveAfter', $entity, $positionEntity);
    }

    /**
     * @param string $action
     * @param Model  $entity
     * @param Model  $positionEntity
     */
    public function move($action, $entity, $positionEntity)
    {
        $positionColumn = $this->getOrderColumnName();

        $oldPosition = $entity->pivot->$positionColumn;
        $newPosition = $positionEntity->pivot->$positionColumn;

        $isMoveBefore = $action === 'moveBefore'; // otherwise moveAfter

        if ($oldPosition > $newPosition) {
            $this->queryBetween($newPosition, $oldPosition, $isMoveBefore, false)->increment($positionColumn);
            $newEntityPosition = $newPosition;
            $newPositionEntityPosition = $newPosition + 1;
        } elseif ($oldPosition < $newPosition) {
            $this->queryBetween($oldPosition, $newPosition, false, !$isMoveBefore)->decrement($positionColumn);
            $newEntityPosition = $newPosition - 1;
            $newPositionEntityPosition = $newPosition;
        } else {
            return;
        }

        $entity->pivot->$positionColumn = $isMoveBefore ? $newEntityPosition : $newEntityPosition + 1;
        $positionEntity->pivot->$positionColumn = $isMoveBefore ? $newPositionEntityPosition : $newPositionEntityPosition - 1;


        $entity->pivot->save();
        $positionEntity->pivot->save();
    }

    /**
     * @param $left
     * @param $right
     * @param $leftIncluded
     * @param $rightIncluded
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function queryBetween($left, $right, $leftIncluded = false, $rightIncluded = false)
    {
        $positionColumn = $this->getOrderColumnName();

        $leftOperator = $leftIncluded ? '>=' : '>';
        $rightOperator = $rightIncluded ? '<=' : '<';

        $query = $this->newPivotQuery();

        return $query->where($positionColumn, $leftOperator, $left)->where($positionColumn, $rightOperator, $right);
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
