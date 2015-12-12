<?php

namespace Rutorika\Sortable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class SortableController extends Controller
{
    public function sort(Request $request)
    {
        $sortableEntities = app('config')->get('sortable.entities', []);

        $validator = $this->getValidator($sortableEntities, $request);

        if ($validator->passes()) {
            /** @var Model|bool $entityClass */
            list($entityClass, $relation) = $this->getEntityInfo($sortableEntities, $request->input('entityName'));

            if (!$relation) {
                /** @var SortableTrait $entity */
                $entity = $entityClass::find($request->input('id'));
                $postionEntity = $entityClass::find($request->input('positionEntityId'));
                switch ($request->input('type')) {
                    case 'moveAfter':
                        $entity->moveAfter($postionEntity);
                        break;
                    case 'moveBefore':
                        $entity->moveBefore($postionEntity);
                        break;
                }
            } else {
                $parentEntity = $entityClass::find($request->input('parentId'));

                $entity = $parentEntity->$relation()->find($request->input('id'));
                $postionEntity = $parentEntity->$relation()->find($request->input('positionEntityId'));

                switch ($request->input('type')) {
                    case 'moveAfter':
                        $parentEntity->$relation()->moveAfter($entity, $postionEntity);
                        break;
                    case 'moveBefore':
                        $parentEntity->$relation()->moveBefore($entity, $postionEntity);
                        break;
                }
            }

            return [
                'success' => true,
            ];
        } else {
            return [
                'success' => false,
                'errors' => $validator->errors(),
                'failed' => $validator->failed(),
            ];
        }
    }

    /**
     * @param array   $sortableEntities
     * @param Request $request
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function getValidator($sortableEntities, $request)
    {
        /** @var  \Illuminate\Validation\Factory $validator */
        $validator = app('validator');

        $rules = [
            'type' => ['required', 'in:moveAfter,moveBefore'],
            'entityName' => ['required', 'in:' . implode(',', array_keys($sortableEntities))],
            'id' => 'required',
            'positionEntityId' => 'required',
        ];

        /** @var Model|bool $entityClass */
        list($entityClass, $relation) = $this->getEntityInfo($sortableEntities, $request->input('entityName'));

        if ($relation) {
            $rules['parentId'] = 'required';
        }

        if (!class_exists($entityClass)) {
            $rules['entityClass'] = 'required'; // fake rule for not exist field
        } else {
            $tableName = with(new $entityClass())->getTable();
            $primaryKey = with(new $entityClass())->getKeyName();

            if (!$relation) {
                $rules['id'] .= '|exists:' . $tableName . ',' . $primaryKey;
                $rules['positionEntityId'] .= '|exists:' . $tableName . ',' . $primaryKey;
            } else {
                $rules['parentId'] .= '|exists:' . $tableName . ',' . $primaryKey;

                /** @var BelongsToSortedMany $relationObject */
                $relationObject = with(new $entityClass())->$relation();
                $pivotTable = $relationObject->getTable();

                $rules['id'] .= '|exists:' . $pivotTable . ',' . $relationObject->getOtherKey() . ',' . $relationObject->getForeignKey() . ',' . $request->input('parentId');
                $rules['positionEntityId'] .= '|exists:' . $pivotTable . ',' . $relationObject->getOtherKey() . ',' . $relationObject->getForeignKey() . ',' . $request->input('parentId');
            }
        }

        return $validator->make($request->all(), $rules);
    }

    protected function getEntityInfo($sortableEntities, $entityName)
    {
        $entityClass = false;
        $relation = false;

        if (array_key_exists($entityName, $sortableEntities)) {
            $entityConfig = $sortableEntities[$entityName];

            if (is_array($entityConfig)) {
                $entityClass = $entityConfig['entity'];
                $relation = !empty($entityConfig['relation']) ? $entityConfig['relation'] : false;
            } else {
                $entityClass = $entityConfig;
            }
        }

        return [$entityClass, $relation];
    }
}
