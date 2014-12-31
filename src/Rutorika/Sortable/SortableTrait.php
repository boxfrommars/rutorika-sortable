<?php

namespace Rutorika\Sortable;

/**
 * Class SortableTrait
 * @traitUses \Eloquent
 */
trait SortableTrait
{

    /**
     * Adds position to model on creating event
     */
    public static function bootSortableTrait()
    {
        static::creating(
            function ($model) {

                $sortableGroupField = $model->getSortableGroupField();

                if ($sortableGroupField) {
                    $maxPosition = static::where($sortableGroupField, $model->$sortableGroupField)->max('position');
                } else {
                    $maxPosition = static::max('position');
                }

                $model->position = $maxPosition + 1;
            }
        );
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @return mixed
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('position');
    }

    /**
     * moves $this model after $entity model (and rearrange all entities)
     *
     * @param \Eloquent $entity
     * @throws \Exception
     */
    public function moveAfter($entity)
    {
        $this->getConnection()->beginTransaction();

        $query = $this->getConnection()->table($this->getTable());
        $query = $this->_applySortableGroup($query, $entity);

        if ($this->position > $entity->position) {

            $query
                ->where('position', '>', $entity->position)
                ->where('position', '<', $this->position)
                ->increment('position');

            $this->position = $entity->position + 1;

        } elseif ($this->position < $entity->position) {

            $query
                ->where('position', '<=', $entity->position)
                ->where('position', '>', $this->position)
                ->decrement('position');

            $this->position = $entity->position;
            $entity->position = $entity->position - 1;
        }

        $this->save();

        $this->getConnection()->commit();
    }

    /**
     * moves $this model before $entity model (and rearrange all entities)
     *
     * @param \Eloquent $entity
     * @throws \Exception
     */
    public function moveBefore($entity)
    {
        $this->getConnection()->beginTransaction();

        $query = $this->getConnection()->table($this->getTable());
        $query = $this->_applySortableGroup($query, $entity);

        if ($this->position > $entity->position) {
            $query
                ->where('position', '>=', $entity->position)
                ->where('position', '<', $this->position)
                ->increment('position');

            $this->position = $entity->position;

            $entity->position = $entity->position + 1;

        } elseif ($this->position < $entity->position) {
            $query
                ->where('position', '<', $entity->position)
                ->where('position', '>', $this->position)
                ->decrement('position');

            $this->position = $entity->position - 1;
        }

        $this->save();

        $this->getConnection()->commit();
    }

    protected function _applySortableGroup($query, $entity)
    {
        $sortableGroupField = $this->getSortableGroupField();
        if ($sortableGroupField) {
            if ($this->$sortableGroupField !== $entity->$sortableGroupField) {
                throw new SortableException($this->$sortableGroupField, $entity->$sortableGroupField);
            }

            $query->where($sortableGroupField, '=', $this->$sortableGroupField);
        }
        return $query;
    }

    public static function getSortableGroupField()
    {
        return isset(static::$sortableGroupField) ? static::$sortableGroupField : null;
    }
}
