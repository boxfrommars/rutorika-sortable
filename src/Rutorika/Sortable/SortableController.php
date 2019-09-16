<?php

namespace Rutorika\Sortable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SortableController extends Controller
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function sort(Request $request)
    {
        $sortableEntities = app('config')->get('sortable.entities', []);
        $validator = $this->getValidator($sortableEntities, $request);

        if (!$validator->passes()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
                'failed' => $validator->failed(),
            ];
        }

        /** @var Model|bool $entityClass */
        list($entityClass, $relation) = $this->getEntityInfo($sortableEntities, (string) $request->input('entityName'));
        $method = ($request->input('type') === 'moveAfter') ? 'moveAfter' : 'moveBefore';

        if (!$relation) {
            /** @var SortableTrait $entity */
            $entity = $entityClass::find($request->input('id'));
            $postionEntity = $entityClass::find($request->input('positionEntityId'));
            $entity->$method($postionEntity);
        } else {
            $parentEntity = $entityClass::find($request->input('parentId'));
            $entity = $parentEntity->$relation()->find($request->input('id'));
            $postionEntity = $parentEntity->$relation()->find($request->input('positionEntityId'));
            $parentEntity->$relation()->$method($entity, $postionEntity);
        }

        return ['success' => true];
    }

    /**
     * @param array   $sortableEntities
     * @param Request $request
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function getValidator($sortableEntities, $request)
    {
        /** @var \Illuminate\Validation\Factory $validator */
        $validator = app('validator');

        $rules = [
            'type' => ['required', 'in:moveAfter,moveBefore'],
            'entityName' => ['required', 'in:' . implode(',', array_keys($sortableEntities))],
            'id' => 'required',
            'positionEntityId' => 'required',
        ];

        /** @var Model|bool $entityClass */
        list($entityClass, $relation) = $this->getEntityInfo($sortableEntities, (string) $request->input('entityName'));

        if (!class_exists($entityClass)) {
            $rules['entityClass'] = 'required'; // fake rule for not exist field
            return $validator->make($request->all(), $rules);
        }

        $connectionName = with(new $entityClass())->getConnectionName();
        $tableName = with(new $entityClass())->getTable();
        $primaryKey = with(new $entityClass())->getKeyName();

        if (!empty($connectionName)) {
            $tableName = $connectionName . '.' . $tableName;
        }

        if (!$relation) {
            $rules['id'] .= '|exists:' . $tableName . ',' . $primaryKey;
            $rules['positionEntityId'] .= '|exists:' . $tableName . ',' . $primaryKey;
        } else {
            /** @var BelongsToSortedMany $relationObject */
            $relationObject = with(new $entityClass())->$relation();
            $pivotTable = $relationObject->getTable();

            $rules['parentId'] = 'required|exists:' . $tableName . ',' . $primaryKey;
            $rules['id'] .= '|exists:' . $pivotTable . ',' . $relationObject->getRelatedKey() . ',' . $relationObject->getForeignKey() . ',' . $request->input('parentId');
            $rules['positionEntityId'] .= '|exists:' . $pivotTable . ',' . $relationObject->getRelatedKey() . ',' . $relationObject->getForeignKey() . ',' . $request->input('parentId');
        }

        return $validator->make($request->all(), $rules);
    }

    /**
     * @param array  $sortableEntities
     * @param string $entityName
     *
     * @return array
     */
    protected function getEntityInfo($sortableEntities, $entityName)
    {
        $relation = false;

        $entityConfig = $entityName ? Arr::get($sortableEntities, $entityName, false) : false;

        if (is_array($entityConfig)) {
            $entityClass = $entityConfig['entity'];
            $relation = !empty($entityConfig['relation']) ? $entityConfig['relation'] : false;
        } else {
            $entityClass = $entityConfig;
        }

        return [$entityClass, $relation];
    }
}
