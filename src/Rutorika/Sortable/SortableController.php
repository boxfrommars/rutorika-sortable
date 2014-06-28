<?php

namespace Rutorika\Sortable;

class SortableController extends \Controller
{

    public function sort()
    {
        $sortableEntities = \Config::get('sortable::entities');

        \Log::debug($sortableEntities);

        $validator = \Validator::make(\Input::all(), array(
            'type' => array('required', 'in:moveAfter,moveBefore'),
            'entityName' => array('required', 'in:' . implode(',', array_keys($sortableEntities))),
            'positionEntityId' => 'required|numeric',
            'id' => 'required|numeric',
        ));

        if ($validator->passes()) {
            /** @var \Eloquent $entityClass */
            $entityClass = $sortableEntities[\Input::get('table')];
            if (!class_exists($entityClass)) {
                return array(
                    'success' => false,
                    'errors' => ["Class {$entityClass} with sortable entity type " . \Input::get('table') . ', not found'],
                );
            }
            /** @var SortableTrait $entity */
            $entity = $entityClass::find(\Input::get('id'));
            $postionEntity = $entityClass::find(\Input::get('positionEntityId'));

            if ($entity === null || $postionEntity === null) {
                return array(
                    'success' => false,
                    'errors' => ['Entity with id #' . \Input::get('id') . ' or #' . \Input::get('positionEntityId') . ' not found'],
                );
            }

            switch (\Input::get('type')) {
                case 'moveAfter':
                    $entity->moveAfter($postionEntity);
                    break;
                case 'moveBefore':
                    $entity->moveBefore($postionEntity);
                    break;
            }

            return array(
                'success' => true
            );
        } else {
            return array(
                'success' => false,
                'errors' => $validator->errors(),
            );
        }
    }
}