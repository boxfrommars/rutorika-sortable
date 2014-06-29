<?php

require_once 'stubs/SortableEntity.php';

class SortableTest extends Orchestra\Testbench\TestCase
{

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

        // fix for "Eloquent model events are not triggered when testing" https://github.com/laravel/framework/issues/1181
        SortableEntity::boot();
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
    }

    public function testPositionOnCreate()
    {
        $entity = new SortableEntity();
        $entity->save();
        $this->assertEquals(1, $entity->position);

        $entity2 = new SortableEntity();
        $entity2->save();
        $this->assertEquals(2, $entity2->position);
    }

    public function testPosition()
    {
        for ($i = 1; $i <= 30; $i++) {
            $entities[$i] = new SortableEntity();
            $entities[$i]->save();
            $this->assertEquals($i, $entities[$i]->id);
            $this->assertEquals($i, $entities[$i]->position);
        }
    }

    /**
     * @param
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityComesBeforeRelativeEntityProvider
     */
    public function testMoveAfterWhenMovedEntityComesBeforeRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {

        // interavls: [1 .. $entityId - 1], [$entityId], [$entityId + 1 .. $relativeEntityId], [$relativeEntityId .. $countTotal]

        /** @var SortableEntity[] $entities */
        $entities = array();
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntity();
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        $moveEntity->moveAfter($relyEntity);

        $this->assertEquals($relativeEntityId, $moveEntity->position);
        $this->assertEquals($relativeEntityId - 1, $relyEntity->position);

        // check [1 .. $entityId - 1] entities
        for ($id = 1; $id < $entityId; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id, $entity->position);
        }

        // check $entityId entity
        $entity = SortableEntity::find($entityId);
        $this->assertEquals($relativeEntityId, $entity->position);

        // check  [$entityId + 1 .. $relativeEntityId] entities
        for ($id = $entityId + 1; $id <= $relativeEntityId; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id - 1, $entity->position);
        }

        // check  [$relativeEntityId + 1 .. $countTotal] entities
        for ($id = $relativeEntityId + 1; $id <= $countTotal; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id, $entity->position);
        }
    }

    /**
     * @param
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityComesAfterRelativeEntityProvider
     */
    public function testMoveAfterWhenMovedEntityComesAfterRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {
        // interavls: [1 .. $relativeEntityId], , [$relativeEntityId + 1 .. $entityId - 1], [$entityId], [$entityId + 1 .. $countTotal]

        /** @var SortableEntity[] $entities */
        $entities = array();
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntity();
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        $moveEntity->moveAfter($relyEntity);
        $this->assertEquals($relativeEntityId + 1, $moveEntity->position);

        // check [1 .. $relativeEntityId] entities
        for ($id = 1; $id <= $relativeEntityId; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id, $entity->position);
        }

        // check  [$relativeEntityId + 1 .. $entityId - 1] entities
        for ($id = $relativeEntityId + 1; $id <= $entityId - 1; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id + 1, $entity->position);
        }

        // check $entityId entity
        $entity = SortableEntity::find($entityId);
        $this->assertEquals($relativeEntityId + 1, $entity->position);

        // check  [$entityId + 1 .. $countTotal] entities
        for ($id = $entityId + 1; $id <= $countTotal; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id, $entity->position);
        }
    }

    /**
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityIsRelativeEntityProvider
     */
    public function testMoveAfterWhenMovedEntityIsRelativeEntity($entityId, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = array();
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntity();
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $moveEntity->moveAfter($moveEntity);

        $this->assertEquals($entityId, $moveEntity->position);

        for ($i = 1; $i <= $countTotal; $i++) {
            $this->assertEquals($i, SortableEntity::find($i)->position);
        }
    }

    /**
     * @param
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityComesBeforeRelativeEntityProvider
     */
    public function testMoveBeforeWhenMovedEntityComesBeforeRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = array();
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntity();
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        $moveEntity->moveBefore($relyEntity);
        $this->assertEquals($relativeEntityId - 1, $moveEntity->position);

        // check [1 .. $entityId - 1] entities
        for ($id = 1; $id < $entityId; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id, $entity->position);
        }

        // check $entityId entity
        $entity = SortableEntity::find($entityId);
        $this->assertEquals($relativeEntityId - 1, $entity->position);

        // check  [$entityId + 1 .. $relativeEntityId] entities
        for ($id = $entityId + 1; $id <= $relativeEntityId - 1; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id - 1, $entity->position);
        }

        // check  [$relativeEntityId + 1 .. $countTotal] entities
        for ($id = $relativeEntityId; $id <= $countTotal; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id, $entity->position);
        }
    }

    /**
     * @param
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityComesAfterRelativeEntityProvider
     */
    public function testMoveBeforeWhenMovedEntityComesAfterRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = array();
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntity();
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        $moveEntity->moveBefore($relyEntity);
        $this->assertEquals($relativeEntityId, $moveEntity->position);
        $this->assertEquals($relativeEntityId + 1, $relyEntity->position);

        // check [1 .. $relativeEntityId] entities
        for ($id = 1; $id <= $relativeEntityId - 1; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id, $entity->position);
        }

        // check  [$relativeEntityId + 1 .. $entityId - 1] entities
        for ($id = $relativeEntityId; $id <= $entityId - 1; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id + 1, $entity->position);
        }

        // check $entityId entity
        $entity = SortableEntity::find($entityId);
        $this->assertEquals($relativeEntityId, $entity->position);

        // check  [$entityId + 1 .. $countTotal] entities
        for ($id = $entityId + 1; $id <= $countTotal; $id++) {
            $entity = SortableEntity::find($id);
            $this->assertEquals($id, $entity->position);
        }
    }

    /**
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityIsRelativeEntityProvider
     */
    public function testMoveBeforeWhenMovedEntityIsRelativeEntity($entityId, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = array();
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntity();
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $moveEntity->moveBefore($moveEntity);

        $this->assertEquals($entityId, $moveEntity->position);

        for ($i = 1; $i <= $countTotal; $i++) {
            $this->assertEquals($i, SortableEntity::find($i)->position);
        }
    }

    /**
     * @return array
     */
    public function moveWhenMovedEntityComesAfterRelativeEntityProvider()
    {
        return array(
            array(7, 1, 30),
            array(9, 7, 30),
            array(30, 15, 30),
        );
    }

    /**
     * @return array
     */
    public function moveWhenMovedEntityComesBeforeRelativeEntityProvider()
    {
        return array(
            array(1, 7, 30),
            array(7, 9, 30),
            array(15, 30, 30),
        );
    }

    /**
     * @return array
     */
    public function moveWhenMovedEntityIsRelativeEntityProvider()
    {
        return array(
            array(1, 30),
            array(7, 30),
            array(30, 30),
        );
    }
}