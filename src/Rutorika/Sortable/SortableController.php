<?php

namespace Rutorika\Sortable;

class SortableController extends \Controller
{

    protected $_sortableTables = array('articles' => '\Article'); // @TODO think about this

    public function sort()
    {
        $validator = \Validator::make(\Input::all(), array(
            'type' => array('required', 'in:moveAfter,moveBefore'),
            'table' => array('required', 'in:' . implode(',', array_keys($this->_sortableTables))),
            'positionEntityId' => 'required|numeric',
            'id' => 'required|numeric',
        ));

        if ($validator->passes()) {
            /** @var \Eloquent $entityClass */
            $entityClass = $this->_sortableTables[\Input::get('table')];
            if (!class_exists($entityClass)) {
                return array(
                    'success' => false,
                    'errors' => ["Невозможно найти класс модели {$entityClass}, отвечающую данной таблице: " . \Input::get('table')],
                );
            }
            /** @var SortableTrait $entity */
            $entity = $entityClass::find(\Input::get('id'));
            $postionEntity = $entityClass::find(\Input::get('positionEntityId'));

            if ($entity === null || $postionEntity === null) {
                return array(
                    'success' => false,
                    'errors' => ['Одна из сущностей, участвующих в изменении порядка, не найдена: #' . \Input::get('id') . ' или #' . \Input::get('positionEntityId')],
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