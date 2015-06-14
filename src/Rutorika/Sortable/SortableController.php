<?php

namespace Rutorika\Sortable;

use Illuminate\Routing\Controller;
use Request;

class SortableController extends Controller
{

    public function sort()
    {
        $sortableEntities = app('config')->get('sortable.entities', []);

        $validator = $this->getValidator($sortableEntities);

        if ($validator->passes()) {
            $relation = false;

            /** @var \Eloquent $entityClass */
            $entityConfig = $sortableEntities[Request::get('entityName')];

            if (is_array($entityConfig)) {
                $entityClass = $entityConfig['entity'];
                $relation = !empty($entityConfig['relation']) ? $entityConfig['relation'] : false;
            } else {
                $entityClass = $entityConfig;
            }


            if (!$relation) {
                /** @var SortableTrait $entity */
                $entity = $entityClass::find(Request::get('id'));
                $postionEntity = $entityClass::find(Request::get('positionEntityId'));
                switch (\Input::get('type')) {
                    case 'moveAfter':
                        $entity->moveAfter($postionEntity);
                        break;
                    case 'moveBefore':
                        $entity->moveBefore($postionEntity);
                        break;
                }
            } else {
                $parentEntity = $entityClass::find(Request::get('parentId'));

                $entity = $parentEntity->$relation()->find(Request::get('id'));
                $postionEntity = $parentEntity->$relation()->find(Request::get('positionEntityId'));

                switch (\Input::get('type')) {
                    case 'moveAfter':
                        $parentEntity->$relation()->moveAfter($entity, $postionEntity);
                        break;
                    case 'moveBefore':
                        $parentEntity->$relation()->moveBefore($entity, $postionEntity);
                        break;
                }
            }


            return [
                'success' => true
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
     * @param array $sortableEntities
     * @return mixed
     */
    protected function getValidator($sortableEntities)
    {
        /** @var  \Illuminate\Validation\Factory $validator */
        $validator = app('validator');

        $rules = [
            'type' => ['required', 'in:moveAfter,moveBefore'],
            'entityName' => ['required', 'in:' . implode(',', array_keys($sortableEntities))],
            'id' => 'required|numeric',
            'positionEntityId' => 'required|numeric',
        ];

        /** @var \Eloquent|bool $entityClass */
        $entityClass = false;
        $relation = false;

        if (array_key_exists(Request::get('entityName'), $sortableEntities)) {
            $entityConfig = $sortableEntities[Request::get('entityName')];

            if (is_array($entityConfig)) {
                $entityClass = $entityConfig['entity'];
                $relation = !empty($entityConfig['relation']) ? $entityConfig['relation'] : false;
            } else {
                $entityClass = $entityConfig;
            }
        }

        if ($relation) {
            $rules['parentId'] = 'required|numeric';
        }

        if (!class_exists($entityClass)) {
            $rules['entityClass'] = 'required'; // fake rule for not exist field
        } else {
            $tableName = with(new $entityClass)->getTable();

            if (!$relation) {
                $rules['id'] .= '|exists:' . $tableName . ',id';
                $rules['positionEntityId'] .= '|exists:' . $tableName . ',id';
            } else {
                $rules['parentId'] .= '|exists:' . $tableName . ',id';

                /** @var BelongsToSortedMany $relationObject */
                $relationObject = with(new $entityClass)->$relation();
                $pivotTable = $relationObject->getTable();

                $rules['id'] .= '|exists:' . $pivotTable . ',' . $relationObject->getOtherKey() . ',' . $relationObject->getForeignKey() . ',' . Request::get('parentId');
                $rules['positionEntityId'] .= '|exists:' . $pivotTable . ',' . $relationObject->getOtherKey() . ',' . $relationObject->getForeignKey() . ',' . Request::get('parentId');

            }
        }

        return $validator->make(Request::all(), $rules);
    }
}
