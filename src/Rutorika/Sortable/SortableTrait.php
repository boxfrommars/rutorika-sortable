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
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @throws \Exception
     */
    public function moveAfter($entity)
    {
        /** @var \Illuminate\Database\Connection $connection */

        $connection = $this->getConnection();

        $this->_transaction(function() use($this, $connection, $entity){
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $connection->table($this->getTable());

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
        });
    }

    /**
     * moves $this model before $entity model (and rearrange all entities)
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @throws \Exception
     */
    public function moveBefore($entity)
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->getConnection();

        $this->_transaction(function() use($this, $connection, $entity){
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = $connection->table($this->getTable());

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
        });
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    protected function _transaction(\Closure $callback){
        return $this->getConnection()->transaction($callback);
    }

    /**
     * @param $query
     * @param $entity
     * @return mixed
     * @throws SortableException
     */
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

    /**
     * @return null
     */
    public static function getSortableGroupField()
    {
        return isset(static::$sortableGroupField) ? static::$sortableGroupField : null;
    }
}
