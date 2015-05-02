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

            /** @var \Eloquent $entityClass */
            $entityClass = $sortableEntities[Request::get('entityName')];

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

        if (array_key_exists(Request::get('entityName'), $sortableEntities)) {
            $entityClass = $sortableEntities[Request::get('entityName')];
        }

        if (!class_exists($entityClass)) {
            $rules['entityClass'] = 'required'; // fake rule for not exist field
        } else {
            $tableName = with(new $entityClass)->getTable();
            $rules['id'] .= '|exists:' . $tableName . ',id';
            $rules['positionEntityId'] .= '|exists:' . $tableName . ',id';
        }

        return $validator->make(Request::all(), $rules);
    }
}
