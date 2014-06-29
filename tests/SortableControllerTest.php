<?php

require_once 'stubs/SortableEntity.php';

class SortableControllerTest extends Orchestra\Testbench\TestCase {

    public function setUp()
    {
        parent::setUp();
        $artisan = $this->app->make('artisan');
        $artisan->call(
            'migrate',
            array(
                '--database' => 'testbench',
                '--path' => '../tests/migrations',
            )
        );

        for ($i = 1; $i <= 30; $i++) {
            $entities[$i] = new SortableEntity();
            $entities[$i]->save();
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['path.base'] = __DIR__ . '/../src';

        $app['config']->set('database.default', 'testbench');
        $app['config']->set(
            'database.connections.testbench',
            array(
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            )
        );

        $app['config']->set('sortable::entities', array(
            'sortable_entity' => '\SortableEntity',
            'sortable_entity_without_class' => '\SortableEntityNotExist'
        ));

        $app['router']->post('sort', '\Rutorika\Sortable\SortableController@sort');
    }


    public function testOK()
    {
        $this->assertEquals(1, 1);
    }

    public function testControllerWithoutParams()
    {
        $response = $this->call('POST', 'sort');
        $this->assertResponseOk();
        $responseData = $this->parseJSON($response);

        $this->assertObjectHasAttribute('errors', $responseData);
        $this->assertObjectHasAttribute('success', $responseData);

        $this->assertFalse($responseData->success);

        $errors = $responseData->errors;

        $this->assertObjectHasAttribute('type', $errors);
        $this->assertObjectHasAttribute('entityName', $errors);
        $this->assertObjectHasAttribute('positionEntityId', $errors);
        $this->assertObjectHasAttribute('id', $errors);

        $this->assertContains('validation.required', $errors->type);
        $this->assertContains('validation.required', $errors->entityName);
        $this->assertContains('validation.required', $errors->positionEntityId);
        $this->assertContains('validation.required', $errors->id);
    }


    /**
     * @param
     * @dataProvider validParamsProvider
     */
    public function testControllerWithValidParams($parameters)
    {
        $response = $this->call('POST', 'sort', $parameters);
        $responseData = $this->parseJSON($response);


        $this->assertTrue($responseData->success);
    }

    /**
     * @param
     * @param
     * @dataProvider invalidParamsProvider
     */
    public function testControllerWithInvalidParams($parameters, $error)
    {
        $response = $this->call('POST', 'sort', $parameters);
        $responseData = $this->parseJSON($response);
        $this->assertFalse($responseData->success);

        $this->assertObjectHasAttribute('errors', $responseData);
        $errors = $responseData->errors;

        $this->assertContains($error, ['invalidEntityId', 'invalidPositionEntityId', 'invalidEntityName', 'invalidType', 'invalidEntityClass']);

        switch ($error) {
            case 'invalidEntityId':
                $this->assertContains('validation.exists', $errors->id);
                break;

            case 'invalidPositionEntityId':
                $this->assertContains('validation.exists', $errors->positionEntityId);
                break;

            case 'invalidEntityName':
                $this->assertContains('validation.in', $errors->entityName);
                $this->assertContains('validation.required', $errors->entityClass);
                break;

            case 'invalidEntityClass':
                $this->assertContains('validation.required', $errors->entityClass);
                break;

            case 'invalidType':
                $this->assertContains('validation.in', $errors->type);
                break;

        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return mixed
     */
    protected function parseJSON($response){
        return json_decode($response->getContent());
    }

    public function validParamsProvider() {

        return array(
            array(
                array(
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 4,
                    'id' => 13,
                )
            ),
            array(
                array(
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 10,
                    'id' => 5,
                )
            ),
            array(
                array(
                    'type' => 'moveBefore',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 1,
                    'id' => 30,
                )
            ),
        );
    }



    public function invalidParamsProvider() {

        return array(
            array(
                array(
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 4,
                    'id' => 50,
                ),
                'invalidEntityId'
            ),
            array(
                array(
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 50,
                    'id' => 4,
                ),
                'invalidPositionEntityId'
            ),
            array(
                array(
                    'type' => 'moveAfter',
                    'entityName' => 'invalid_entity',
                    'positionEntityId' => 10,
                    'id' => 5,
                ),
                'invalidEntityName'
            ),
            array(
                array(
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity_without_class',
                    'positionEntityId' => 10,
                    'id' => 5,
                ),
                'invalidEntityClass'
            ),
            array(
                array(
                    'type' => 'moveSomewher',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 1,
                    'id' => 30,
                ),
                'invalidType'
            ),
        );
    }

}