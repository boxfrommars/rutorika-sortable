<?php

require_once 'stubs/SortableEntityWithSpecificDatabase.php';
require_once 'stubs/M2mEntity.php';
require_once 'stubs/M2mRelatedEntity.php';

class SortableControllerSpecificDatabaseTest extends Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom([
            '--database' => 'testbench',
            '--path' => realpath(__DIR__ . '/migrations'),
        ]);

        for ($i = 1; $i <= 30; ++$i) {
            $entities[$i] = new SortableEntityWithSpecificDatabase();
            $entities[$i]->save();
        }

        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= 30; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= 30; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['path.base'] = __DIR__ . '/../src';

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('app.debug', true);
        $app['config']->set(
            'database.connections.testbench',
            [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]
        );
        $app['config']->set(
            'database.connections.other',
            [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]
        );

        $app['config']->set(
            'sortable.entities',
            [
                'sortable_entity' => '\SortableEntityWithSpecificDatabase',
                'sortable_entity_full_config' => ['entity' => '\SortableEntityWithSpecificDatabase'],
                'sortable_entity_m2m' => ['entity' => '\M2mEntity', 'relation' => 'relatedEntities'],
                'sortable_entity_without_class' => '\SortableEntityNotExist',

                'sortable_entity_m2m_without_relation' => ['entity' => '\M2mEntity'],
                'sortable_entity_m2m_with_invalid_relation' => ['entity' => '\M2mEntity'],
            ]
        );

        $app['router']->post('sort', '\Rutorika\Sortable\SortableController@sort');
    }

    public function testOK()
    {
        $this->assertEquals(1, 1);
    }

    public function testControllerWithoutParams()
    {
        $response = $this->call('POST', 'sort');
        $response->assertStatus(200);
        $responseData = $this->parseJSON($response);

        $this->assertObjectHasAttribute('errors', $responseData);
        $this->assertObjectHasAttribute('success', $responseData);

        $this->assertFalse($responseData->success);

        $failed = $responseData->failed;

        $this->assertObjectHasAttribute('type', $failed);
        $this->assertObjectHasAttribute('entityName', $failed);
        $this->assertObjectHasAttribute('positionEntityId', $failed);
        $this->assertObjectHasAttribute('id', $failed);

        $this->assertObjectHasAttribute('Required', $failed->type);
        $this->assertObjectHasAttribute('Required', $failed->entityName);
        $this->assertObjectHasAttribute('Required', $failed->positionEntityId);
        $this->assertObjectHasAttribute('Required', $failed->id);
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
     * @dataProvider validParamsM2mProvider
     */
    public function testControllerM2mWithValidParams($parameters)
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
        $failed = $responseData->failed;

        $this->assertContains(
            $error,
            ['invalidEntityId', 'invalidPositionEntityId', 'invalidEntityName', 'invalidType', 'invalidEntityClass']
        );

        switch ($error) {
            case 'invalidEntityId':
                $this->assertObjectHasAttribute('Exists', $failed->id);
                break;

            case 'invalidPositionEntityId':
                $this->assertObjectHasAttribute('Exists', $failed->positionEntityId);
                break;

            case 'invalidEntityName':
                $this->assertObjectHasAttribute('In', $failed->entityName);
                $this->assertObjectHasAttribute('Required', $failed->entityClass);
                break;

            case 'invalidEntityClass':
                $this->assertObjectHasAttribute('Required', $failed->entityClass);
                break;

            case 'invalidType':
                $this->assertObjectHasAttribute('In', $failed->type);
                break;
        }
    }

    /**
     * @param
     * @param
     * @dataProvider invalidM2mParamsProvider
     */
    public function testM2mControllerWithInvalidParams($parameters, $error)
    {
        $response = $this->call('POST', 'sort', $parameters);
        $responseData = $this->parseJSON($response);
        $this->assertFalse($responseData->success);

        $this->assertObjectHasAttribute('errors', $responseData);
        $failed = $responseData->failed;

        $this->assertContains(
            $error,
            ['invalidEntityId', 'invalidPositionEntityId', 'invalidEntityName', 'invalidType', 'invalidEntityClass', 'parentEntityId']
        );

        switch ($error) {
            case 'invalidEntityId':
                $this->assertObjectHasAttribute('Exists', $failed->id);
                break;

            case 'invalidPositionEntityId':
                $this->assertObjectHasAttribute('Exists', $failed->positionEntityId);
                break;

            case 'parentEntityId':
                $this->assertObjectHasAttribute('Exists', $failed->parentId);
                break;

            case 'invalidEntityName':
                $this->assertObjectHasAttribute('In', $failed->entityName);
                $this->assertObjectHasAttribute('Required', $failed->entityClass);
                break;

            case 'invalidEntityClass':
                $this->assertObjectHasAttribute('Required', $failed->entityClass);
                break;

            case 'invalidType':
                $this->assertObjectHasAttribute('In', $failed->type);
                break;
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return mixed
     */
    protected function parseJSON($response)
    {
        return json_decode($response->getContent());
    }

    public function validParamsProvider()
    {
        return [
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 4,
                    'id' => 13,
                ],
            ],
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 10,
                    'id' => 5,
                ],
            ],
            [
                [
                    'type' => 'moveBefore',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 1,
                    'id' => 30,
                ],
            ],
            [
                [
                    'type' => 'moveBefore',
                    'entityName' => 'sortable_entity_full_config',
                    'positionEntityId' => 1,
                    'id' => 30,
                ],
            ],
        ];
    }

    public function validParamsM2mProvider()
    {
        return [
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity_m2m',
                    'parentId' => 1,
                    'positionEntityId' => 4,
                    'id' => 13,
                ],
            ],
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity_m2m',
                    'parentId' => 1,
                    'positionEntityId' => 10,
                    'id' => 5,
                ],
            ],
            [
                [
                    'type' => 'moveBefore',
                    'entityName' => 'sortable_entity_m2m',
                    'parentId' => 1,
                    'positionEntityId' => 1,
                    'id' => 30,
                ],
            ],
        ];
    }

    public function invalidParamsProvider()
    {
        return [
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 4,
                    'id' => 50,
                ],
                'invalidEntityId',
            ],
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 50,
                    'id' => 4,
                ],
                'invalidPositionEntityId',
            ],
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'invalid_entity',
                    'positionEntityId' => 10,
                    'id' => 5,
                ],
                'invalidEntityName',
            ],
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity_without_class',
                    'positionEntityId' => 10,
                    'id' => 5,
                ],
                'invalidEntityClass',
            ],
            [
                [
                    'type' => 'moveSomewher',
                    'entityName' => 'sortable_entity',
                    'positionEntityId' => 1,
                    'id' => 30,
                ],
                'invalidType',
            ],
        ];
    }

    public function invalidM2mParamsProvider()
    {
        return [
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity_m2m',
                    'parentId' => 1,
                    'positionEntityId' => 4,
                    'id' => 50,
                ],
                'invalidEntityId',
            ],
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity_m2m',
                    'parentId' => 1,
                    'positionEntityId' => 50,
                    'id' => 1,
                ],
                'invalidPositionEntityId',
            ],
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity_m2m',
                    'parentId' => 3,
                    'positionEntityId' => 4,
                    'id' => 6,
                ],
                'parentEntityId',
            ],
            [
                [
                    'type' => 'moveAfter',
                    'entityName' => 'sortable_entity_m2m_failed',
                    'parentId' => 3,
                    'positionEntityId' => 4,
                    'id' => 1,
                ],
                'invalidEntityName',
            ],
        ];
    }
}
