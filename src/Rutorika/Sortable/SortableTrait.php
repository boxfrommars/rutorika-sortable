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
                $maxPosition = static::max('position');
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
     */
    public function moveAfter($entity)
    {
        /** @var \Eloquent $this */
        $this->getConnection()->beginTransaction();

        if ($this->position > $entity->position) {

            $this->getConnection()->table($this->getTable())
                ->where('position', '>', $entity->position)
                ->where('position', '<', $this->position)
                ->increment('position');

            $this->position = $entity->position + 1;
        } else {
            if ($this->position < $entity->position) {

                $this->getConnection()->table($this->getTable())
                    ->where('position', '<=', $entity->position)
                    ->where('position', '>', $this->position)
                    ->decrement('position');

                $this->position = $entity->position;
                $entity->position = $entity->position - 1;
            }
        }

        $this->save();

        $this->getConnection()->commit();
    }

    /**
     * moves $this model before $entity model (and rearrange all entities)
     *
     * @param \Eloquent $entity
     */
    public function moveBefore($entity)
    {
        /** @var \Eloquent $this */
        $this->getConnection()->beginTransaction();

        if ($this->position > $entity->position) {
            $this->getConnection()->table($this->getTable())->where('position', '>=', $entity->position)->where(
                'position',
                '<',
                $this->position
            )->increment('position');
            $this->position = $entity->position;

            $entity->position = $entity->position + 1;
        } else {
            if ($this->position < $entity->position) {
                $this->getConnection()->table($this->getTable())->where('position', '<', $entity->position)->where(
                    'position',
                    '>',
                    $this->position
                )->decrement('position');
                $this->position = $entity->position - 1;
            }
        }

        $this->save();

        $this->getConnection()->commit();
    }
}
