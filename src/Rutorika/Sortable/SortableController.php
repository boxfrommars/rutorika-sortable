<?php

namespace Rutorika\Sortable;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SortableController extends Controller
{
    protected $request;

    public function __construct(Request $request){
        $this->request = $request;
    }

    public function sort(Config $config)
    {

        $sortableEntities = $config->get('sortable.entities');

        $validator = $this->getValidator($sortableEntities);

        if ($validator->passes()) {

            /** @var \Eloquent $entityClass */
            $entityClass = $sortableEntities[$this->request->get('entityName')];

            /** @var SortableTrait $entity */
            $entity = $entityClass::find($this->request->get('id'));
            $postionEntity = $entityClass::find($this->request->get('positionEntityId'));

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

        if (array_key_exists($this->request->get('entityName'), $sortableEntities)) {
            $entityClass = $sortableEntities[$this->request->get('entityName')];
        }

        if (!class_exists($entityClass)) {
            $rules['entityClass'] = 'required'; // fake rule for not exist field
        } else {
            $tableName = with(new $entityClass)->getTable();
            $rules['id'] .= '|exists:' . $tableName . ',id';
            $rules['positionEntityId'] .= '|exists:' . $tableName . ',id';
        }

        return $validator->make($this->request->all(), $rules);
    }
}
