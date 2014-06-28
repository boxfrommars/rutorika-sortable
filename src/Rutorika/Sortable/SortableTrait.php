<?php

namespace Rutorika\Sortable;

/**
 * Class SortableTrait
 * @traitUses \Eloquent
 */
trait SortableTrait
{

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

    public function moveAfter($entity)
    {
        /** @var \Eloquent $this */
        $this->getConnection()->beginTransaction();

        if ($this->position > $entity->position) {
            $this->getConnection()->table($this->getTable())->where('position', '>', $entity->position)->where(
                'position',
                '<',
                $this->position
            )->increment('position');
            $this->position = $entity->position + 1;
        } else {
            if ($this->position < $entity->position) {
                $this->getConnection()->table($this->getTable())->where('position', '<=', $entity->position)->where(
                    'position',
                    '>',
                    $this->position
                )->decrement('position');
                $this->position = $entity->position;
            }
        }

        $this->save();

        $this->getConnection()->commit();
    }

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