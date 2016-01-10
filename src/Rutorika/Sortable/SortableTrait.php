<?php

namespace Rutorika\Sortable;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Class SortableTrait.
 *
 * @traitUses \Illuminate\Database\Eloquent\Model
 *
 * @property string $sortableGroupField
 *
 * @method null creating($callback)
 * @method QueryBuilder on($connection = null)
 * @method QueryBuilder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method float|int max($column)
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
                /* @var Model $model */
                $sortableField = static::getSortableField();
                $query = static::applySortableGroup(static::on(), $model);
                $model->setAttribute($sortableField, $query->max($sortableField) + 1);
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
        $sortableField = static::getSortableField();

        return $query->orderBy($sortableField);
    }

    /**
     * moves $this model after $entity model (and rearrange all entities).
     *
     * @param Model $entity
     *
     * @throws \Exception
     */
    public function moveAfter($entity)
    {
        $this->move('moveAfter', $entity);
    }

    /**
     * moves $this model before $entity model (and rearrange all entities).
     *
     * @param Model $entity
     *
     * @throws SortableException
     */
    public function moveBefore($entity)
    {
        $this->move('moveBefore', $entity);
    }

    /**
     * @param string $action moveAfter/moveBefore
     * @param Model  $entity
     *
     * @throws SortableException
     */
    public function move($action, $entity)
    {
        $sortableGroupField = static::getSortableGroupField();
        $this->checkSortableGroupField($sortableGroupField, $entity);

        $this->_transaction(function () use ($entity, $action) {
            $sortableField = static::getSortableField();
            $isMoveBefore = $action === 'moveBefore'; // otherwise moveAfter

            $oldPosition = $this->getAttribute($sortableField);
            $newPosition = $entity->getAttribute($sortableField);

            if ($oldPosition > $newPosition) {
                $this->queryBetween($newPosition, $oldPosition, $isMoveBefore, false)->increment($sortableField);
                $this->setAttribute($sortableField, $isMoveBefore ? $newPosition : $newPosition + 1);
            } elseif ($oldPosition < $newPosition) {
                $this->queryBetween($oldPosition, $newPosition, false, !$isMoveBefore)->decrement($sortableField);
                $this->setAttribute($sortableField, $isMoveBefore ? $newPosition - 1 : $newPosition);
            }

            /** @var Model $freshEntity */
            $freshEntity = $entity->fresh();
            $entity->setAttribute($sortableField, $freshEntity->getAttribute($sortableField));
            $this->save();
        });
    }

    /**
     * @param $left
     * @param $right
     * @param $leftIncluded
     * @param $rightIncluded
     *
     * @return QueryBuilder
     */
    protected function queryBetween($left, $right, $leftIncluded = false, $rightIncluded = false)
    {
        $sortableField = static::getSortableField();

        $leftOperator = $leftIncluded ? '>=' : '>';
        $rightOperator = $rightIncluded ? '<=' : '<';

        $query = static::applySortableGroup($this->newQuery(), $this);

        return $query->where($sortableField, $leftOperator, $left)->where($sortableField, $rightOperator, $right);
    }

    /**
     * @param int $limit
     *
     * @return QueryBuilder
     */
    public function previous($limit = 0)
    {
        return $this->siblings(false, $limit);
    }

    /**
     * @param int $limit
     *
     * @return QueryBuilder
     */
    public function next($limit = 0)
    {
        return $this->siblings(true, $limit);
    }

    /**
     * @param bool $isNext is next, otherwise before
     * @param int  $limit
     *
     * @return QueryBuilder
     */
    public function siblings($isNext, $limit = 0)
    {
        $sortableField = static::getSortableField();

        $query = static::applySortableGroup($this->newQuery(), $this);
        $query->where($sortableField, $isNext ? '>' : '<', $this->getAttribute($sortableField));
        $query->orderBy($sortableField, $isNext ? 'asc' : 'desc');
        if ($limit !== 0) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * @param int $limit
     *
     * @return Collection|static[]
     */
    public function getPrevious($limit = 0)
    {
        /** @var Collection $collection */
        $collection = $this->previous($limit)->get();

        return $collection->reverse();
    }

    /**
     * @param int $limit
     *
     * @return Collection
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
     * @param QueryBuilder        $query
     * @param Model|SortableTrait $model
     *
     * @return QueryBuilder
     */
    protected static function applySortableGroup($query, $model)
    {
        $sortableGroupField = static::getSortableGroupField();

        if (is_array($sortableGroupField)) {
            foreach ($sortableGroupField as $field) {
                $query = $query->where($field, $model->$field);
            }
        } elseif ($sortableGroupField !== null) {
            $query = $query->where($sortableGroupField, $model->$sortableGroupField);
        }

        return $query;
    }

    /**
     * @return string|null
     */
    public static function getSortableGroupField()
    {
        $sortableGroupField = isset(static::$sortableGroupField) ? static::$sortableGroupField : null;

        return $sortableGroupField;
    }

    /**
     * @return string
     */
    public static function getSortableField()
    {
        $sortableField = isset(static::$sortableField) ? static::$sortableField : 'position';

        return $sortableField;
    }

    /**
     * @param string|array $sortableGroupField
     * @param Model        $entity
     *
     * @throws SortableException
     */
    public function checkSortableGroupField($sortableGroupField, $entity)
    {
        if (is_array($sortableGroupField)) {
            foreach ($sortableGroupField as $field) {
                $this->checkFieldEquals($this, $entity, $field);
            }
        } else {
            $this->checkFieldEquals($this, $entity, $sortableGroupField);
        }
    }

    /**
     * @param Model|SortableTrait  $entity1
     * @param Model                $entity2
     * @param string               $field
     *
     * @throws SortableException
     */
    public function checkFieldEquals($entity1, $entity2, $field)
    {
        if ($field === null) {
            return;
        }

        if ($entity1->$field !== $entity2->$field) {
            throw new SortableException($entity1->$field, $entity2->$field);
        }
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Illuminate\Database\Query\Builder
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
     *
     * @return bool
     */
    abstract public function save(array $options = []);

    /**
     * Get an attribute from the model.
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract public function getAttribute($key);

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    abstract public function setAttribute($key, $value);
}
