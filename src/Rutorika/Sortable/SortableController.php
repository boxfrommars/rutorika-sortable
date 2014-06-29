<?php

namespace Rutorika\Sortable;

class SortableController extends \Controller
{

    public function sort()
    {

        $sortableEntities = \Config::get('sortable::entities');


        $validator = $this->getValidator($sortableEntities);

        if ($validator->passes()) {

            /** @var \Eloquent $entityClass */
            $entityClass = $sortableEntities[\Input::get('entityName')];

            /** @var SortableTrait $entity */
            $entity = $entityClass::find(\Input::get('id'));
            $postionEntity = $entityClass::find(\Input::get('positionEntityId'));

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

    /**
     * @param $sortableEntities
     * @return mixed
     */
    protected function getValidator($sortableEntities){

        $rules = array(
            'type' => array('required', 'in:moveAfter,moveBefore'),
            'entityName' => array('required', 'in:' . implode(',', array_keys($sortableEntities))),
            'id' => 'required|numeric',
            'positionEntityId' => 'required|numeric',
        );

        /** @var \Eloquent $entityClass */
        $entityClass = array_key_exists(\Input::get('entityName'), $sortableEntities) ? $sortableEntities[\Input::get('entityName')] : false;

        if (!$entityClass || !class_exists($entityClass)) {
            $rules['entityClass'] = 'required'; // fake rule for not exist field
        } else {
            $tableName = with(new $entityClass)->getTable();
            $rules['id'] .= '|exists:' . $tableName . ',id';
            $rules['positionEntityId'] .= '|exists:' . $tableName . ',id';
        }

        return \Validator::make(\Input::all(), $rules);
    }
}