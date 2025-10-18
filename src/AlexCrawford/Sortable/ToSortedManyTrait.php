<?php

namespace AlexCrawford\Sortable;

use AlexCrawford\LexoRank\Rank;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MorphToSortedManyTrait.
 *
 * @method Model orderBy($column, $direction = 'asc')
 * @traitUses Illuminate\Database\Eloquent\Model
 */
trait ToSortedManyTrait
{
    protected $orderColumn;

    protected function setOrderColumn($orderColumn)
    {
        $this->orderColumn = $orderColumn;
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
        
        // Guard: don't move an entity to itself
        if ($entity->pivot->$positionColumn === $positionEntity->pivot->$positionColumn) {
            return;
        }
        
        $entityPosition = $positionEntity->pivot->$positionColumn;

        if ($action === 'moveBefore') {
            $previous = optional($this->newPivotQuery()->where($positionColumn, '<', $entityPosition)->orderBy($positionColumn, 'DESC')->first())->$positionColumn;
            $next = $entityPosition;
        } else {
            $previous = $entityPosition;
            $next = optional($this->newPivotQuery()->where($positionColumn, '>', $entityPosition)->orderBy($positionColumn, 'ASC')->first())->$positionColumn;
        }

        $entity->pivot->$positionColumn = static::getNewPosition($previous, $next);
        $entity->pivot->save();
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
     * @return string
     */
    protected function getNextPosition()
    {
        $max = $this->newPivotQuery()->max($this->getOrderColumnName());
        if ($max === null) {
            return Rank::forEmptySequence()->get();
        }
        return Rank::after(Rank::fromString((string)$max))->get();
    }

    /**
     * @param string $prev
     * @param string $next
     * @return mixed
     */
    public static function getNewPosition($prev, $next = ''): string
    {
        if ($prev === null || $prev === '') {
            if ($next === null || $next === '') {
                return Rank::forEmptySequence()->get();
            }
            return Rank::before(Rank::fromString((string)$next))->get();
        }
        
        if ($next === null || $next === '') {
            return Rank::after(Rank::fromString((string)$prev))->get();
        }
        
        return Rank::betweenRanks(
            Rank::fromString((string)$prev),
            Rank::fromString((string)$next)
        )->get();
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

    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     *
     * @param \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|array $ids
     * @param bool                                                                          $detaching
     *
     * @return array
     */
    public function sync($ids, $detaching = true)
    {
        if ($detaching) {
            $this->detach();
        }

        return parent::sync($ids, $detaching);
    }

    /**
     * Set the columns on the pivot table to retrieve.
     *
     * @param array|mixed $columns
     *
     * @return $this
     */
    abstract public function withPivot($columns);

    /**
     * Create a new query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    abstract protected function newPivotQuery();

    /**
     * Detach models from the relationship.
     *
     * @param mixed $ids
     * @param bool  $touch
     *
     * @return int
     */
    abstract public function detach($ids = null, $touch = true);
}
