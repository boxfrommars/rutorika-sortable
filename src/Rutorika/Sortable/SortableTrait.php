<?php

namespace Rutorika\Sortable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Class SortableTrait.
 *
 * @traitUses \Illuminate\Database\Eloquent\Model
 */
trait SortableTrait
{
    /**
     * Adds position to model on creating event.
     */
    public static function bootSortableTrait()
    {
        static::creating(
            function ($model) {
                $sortableGroupField = $model->getSortableGroupField();

                if ($sortableGroupField) {
                    if (is_array($sortableGroupField)) {
                        $query = static::on();
                        foreach ($sortableGroupField as $field) {
                            $query = $query->where($field, $model->$field);
                        }
                        $maxPosition = $query->max('position');
                    } else {
                        $maxPosition = static::where($sortableGroupField, $model->$sortableGroupField)->max('position');
                    }
                } else {
                    $maxPosition = static::max('position');
                }

                $model->position = $maxPosition + 1;
            }
        );
    }

    /**
     * @param QueryBuilder $query
     *
     * @return QueryBuilder
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('position');
    }

    /**
     * moves $this model after $entity model (and rearrange all entities).
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     *
     * @throws \Exception
     */
    public function moveAfter($entity)
    {
        $sortableGroupField = $this->getSortableGroupField();
        if ($sortableGroupField) {
            if (is_array($sortableGroupField)) {
                foreach ($sortableGroupField as $field) {
                    if ($this->$field !== $entity->$field) {
                        throw new SortableException($this->$field, $entity->$field);
                    }
                }
            } elseif ($this->$sortableGroupField !== $entity->$sortableGroupField) {
                throw new SortableException($this->$sortableGroupField, $entity->$sortableGroupField);
            }
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
     * moves $this model before $entity model (and rearrange all entities).
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     *
     * @throws SortableException
     */
    public function moveBefore($entity)
    {
        $sortableGroupField = $this->getSortableGroupField();
        if ($sortableGroupField) {
            if (is_array($sortableGroupField)) {
                foreach ($sortableGroupField as $field) {
                    if ($this->$field !== $entity->$field) {
                        throw new SortableException($this->$field, $entity->$field);
                    }
                }
            } elseif ($this->$sortableGroupField !== $entity->$sortableGroupField) {
                throw new SortableException($this->$sortableGroupField, $entity->$sortableGroupField);
            }
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
     *
     * @return Builder
     */
    public function previous($limit = 0)
    {
        $query = $this->newQuery();
        $query = $this->_applySortableGroup($query);
        $query->where('position', '<', $this->position);
        $query->orderBy('position', 'desc');
        if ($limit !== 0) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * @param int $limit
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getPrevious($limit = 0)
    {
        return $this->previous($limit)->get()->reverse();
    }

    /**
     * @param int $limit
     *
     * @return Builder
     */
    public function next($limit = 0)
    {
        $query = $this->newQuery();
        $query = $this->_applySortableGroup($query);
        $query->where('position', '>', $this->position);
        $query->orderBy('position', 'asc');
        if ($limit !== 0) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * @param int $limit
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNext($limit = 0)
    {
        return $this->next($limit)->get();
    }

    /**
     * @param \Closure $callback
     *
     * @return mixed
     */
    protected function _transaction(\Closure $callback)
    {
        return $this->getConnection()->transaction($callback);
    }

    /**
     * @param QueryBuilder|\Illuminate\Database\Eloquent\Builder $query
     *
     * @return QueryBuilder|\Illuminate\Database\Eloquent\Builder
     */
    protected function _applySortableGroup($query)
    {
        $sortableGroupField = $this->getSortableGroupField();
        if ($sortableGroupField) {
            if (is_array($sortableGroupField)) {
                foreach ($sortableGroupField as $field) {
                    $query->where($field, $this->$field);
                }
            } else {
                $query->where($sortableGroupField, $this->$sortableGroupField);
            }
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

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    abstract public function newQuery();

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    abstract public function getConnection();

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    abstract public function getTable();

    /**
     * Save the model to the database.
     *
     * @param array $options
     * @return bool
     */
    abstract public function save(array $options = []);
}
