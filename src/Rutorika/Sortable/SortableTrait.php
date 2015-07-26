<?php

namespace Rutorika\Sortable;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class SortableTrait
 * @traitUses \Illuminate\Database\Eloquent\Model
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
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @throws \Exception
     */
    public function moveAfter($entity)
    {
        $sortableGroupField = $this->getSortableGroupField();
        if ($sortableGroupField && $this->$sortableGroupField !== $entity->$sortableGroupField) {
            throw new SortableException($this->$sortableGroupField, $entity->$sortableGroupField);
        }

        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->getConnection();

        $this->_transaction(function () use ($connection, $entity) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $connection->table($this->getTable());
            $query = $this->_applySortableGroup($query);

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
        });
    }

    /**
     * moves $this model before $entity model (and rearrange all entities)
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @throws \Exception
     * @throws SortableException
     */
    public function moveBefore($entity)
    {
        $sortableGroupField = $this->getSortableGroupField();
        if ($sortableGroupField && $this->$sortableGroupField !== $entity->$sortableGroupField) {
            throw new SortableException($this->$sortableGroupField, $entity->$sortableGroupField);
        }

        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->getConnection();

        $this->_transaction(function () use ($connection, $entity) {
            $query = $connection->table($this->getTable());
            $query = $this->_applySortableGroup($query);

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
        });
    }

    /**
     * @param int $limit
     * @return Builder
     */
    public function previous($limit = 0)
    {
        /** @var Builder $query */
        $query = $this->newQuery();
        $query = $this->_applySortableGroup($query);
        $query->where('position', '<', $this->position);
        $query->orderBy('position', 'desc');
        $query->limit($limit);

        return $query;
    }

    /**
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getPrevious($limit = 0)
    {
        return $this->previous($limit)->get()->reverse();
    }

    /**
     * @param int $limit
     * @return Builder
     */
    public function next($limit = 0)
    {
        /** @var Builder $query */
        $query = $this->newQuery();
        $query = $this->_applySortableGroup($query);
        $query->where('position', '>', $this->position);
        $query->orderBy('position', 'asc');
        $query->limit($limit);

        return $query;
    }

    /**
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getNext($limit = 0)
    {
        return $this->next($limit)->get();
    }

    /**
     * @param callable|\Closure $callback
     * @return mixed
     */
    protected function _transaction(\Closure $callback)
    {
        return $this->getConnection()->transaction($callback);
    }

    /**
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    protected function _applySortableGroup($query)
    {
        $sortableGroupField = $this->getSortableGroupField();
        if ($sortableGroupField) {
            $query->where($sortableGroupField, '=', $this->$sortableGroupField);
        }
        return $query;
    }

    /**
     * @return string|null
     */
    public static function getSortableGroupField()
    {
        return isset(static::$sortableGroupField) ? static::$sortableGroupField : null;
    }
}
