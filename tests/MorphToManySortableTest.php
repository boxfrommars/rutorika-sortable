<?php

require_once 'stubs/MorphToManyEntityOne.php';
require_once 'stubs/MorphToManyEntityTwo.php';
require_once 'stubs/MorphToManyRelatedEntity.php';
require_once 'SortableTestBase.php';

class MorphToManySortableTest extends SortableTestBase
{
    public function setUp(): void
    {
        parent::setUp();

        // fix for "Eloquent model events are not triggered when testing" https://github.com/laravel/framework/issues/1181
        MorphToManyRelatedEntity::boot();
    }

    public function testPositionOnSave()
    {
        $entity = new MorphToManyEntityOne();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
        }

        $entity = new MorphTomanyEntityTwo();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
        }

        $currentAssertedPosition = 1;

        foreach ($entity->relatedEntities as $relatedEntity) {
            $this->assertEquals($currentAssertedPosition, $relatedEntity->pivot->morph_to_many_related_entity_position);
            ++$currentAssertedPosition;
        }
    }

    public function testPositionOnAttach()
    {
        $entity = new MorphToManyEntityOne();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $relatedEntity->save();
            $entity->relatedEntities()->attach($relatedEntity->id);
        }

        $entity = new MorphToManyEntityTwo();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $relatedEntity->save();
            $entity->relatedEntities()->attach($relatedEntity->id);
        }

        $currentAssertedPosition = 1;

        foreach ($entity->relatedEntities as $relatedEntity) {
            $this->assertEquals($currentAssertedPosition, $relatedEntity->pivot->morph_to_many_related_entity_position);
            ++$currentAssertedPosition;
        }
    }

    public function testPositionOnSync()
    {
        $entity = new MorphToManyEntityOne();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
        }

        $entity = new MorphToManyEntityOne();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $relatedEntity->save();
        }

        $entitiesToSync = [12, 13, 16];

        $entity->relatedEntities()->sync($entitiesToSync);

        $currentAssertedPosition = 1;

        foreach ($entity->relatedEntities as $relatedEntity) {
            $this->assertEquals($currentAssertedPosition, $relatedEntity->pivot->morph_to_many_related_entity_position);
            $this->assertEquals($relatedEntity->id, $entitiesToSync[$currentAssertedPosition - 1]);

            ++$currentAssertedPosition;
        }
    }

    public function testmoveBeforeRePositionNotChangeNotRelated()
    {
        $entities = [];
        $relatedEntities = [];

        for ($i = 1; $i < 5; ++$i) {
            $entity = new MorphToManyEntityOne();
            $entity->save();
            $entities[] = $entity;
        }

        for ($i = 1; $i < 50; ++$i) {
            $entityKey = array_rand($entities);
            /** @var MorphToManyEntityOne $entity */
            $entity = $entities[$entityKey];

            if (empty($relatedEntities[$entity->id])) {
                $relatedEntities[$entity->id] = [];
            }

            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$entity->id][] = $relatedEntity;
        }

        foreach ($entities as $entity) {
            $currentAssertedPosition = 1;

            foreach ($entity->relatedEntities as $relatedEntity) {
                $this->assertEquals($currentAssertedPosition, $relatedEntity->pivot->morph_to_many_related_entity_position);
                ++$currentAssertedPosition;
            }
        }

        $entity = array_pop($entities);

        $firstRelatedEntity = $entity->relatedEntities->first();
        $secondRelatedEntity = $entity->relatedEntities->last();

        $entity->relatedEntities()->moveBefore($firstRelatedEntity, $secondRelatedEntity);

        $currentAssertedPosition = 1;
        foreach ($entity->relatedEntities()->get() as $reordered) {
            $this->assertEquals($currentAssertedPosition, $reordered->pivot->morph_to_many_related_entity_position);
            ++$currentAssertedPosition;
        }

        // check other related has not changed

        foreach ($entities as $entity) {
            $currentAssertedPosition = 1;
            foreach ($entity->relatedEntities()->get() as $relatedEntity) {
                $this->assertEquals($currentAssertedPosition, $relatedEntity->pivot->morph_to_many_related_entity_position);
                $this->assertEquals($relatedEntities[$entity->id][$currentAssertedPosition - 1]->id, $relatedEntity->id);
                ++$currentAssertedPosition;
            }
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
        $entity = new MorphToManyEntityOne();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($entityId);
        $relyEntity = $entity->relatedEntities()->find($relativeEntityId);

        $entity->relatedEntities()->moveAfter($moveEntity, $relyEntity);

        $this->assertEquals($relativeEntityId, $moveEntity->pivot->morph_to_many_related_entity_position);
        $this->assertEquals($relativeEntityId - 1, $relyEntity->pivot->morph_to_many_related_entity_position);

        // check [1 .. $entityId - 1] entities
        for ($id = 1; $id < $entityId; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }

        $relatedEntity = $entity->relatedEntities()->find($entityId);
        $this->assertEquals($relativeEntityId, $relatedEntity->pivot->morph_to_many_related_entity_position);

        // check [1 .. $entityId - 1] entities
        for ($id = $entityId + 1; $id <= $relativeEntityId; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id - 1, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }

        // check [1 .. $entityId - 1] entities
        for ($id = $relativeEntityId + 1; $id <= $countTotal; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
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
        $entity = new MorphToManyEntityOne();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($entityId);
        $relyEntity = $entity->relatedEntities()->find($relativeEntityId);

        $entity->relatedEntities()->moveAfter($moveEntity, $relyEntity);

        $this->assertEquals($relativeEntityId, $relyEntity->pivot->morph_to_many_related_entity_position);
        $this->assertEquals($relativeEntityId + 1, $moveEntity->pivot->morph_to_many_related_entity_position);

        // check [1 .. $entityId - 1] entities
        for ($id = 1; $id <= $relativeEntityId; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }

        // check [1 .. $entityId - 1] entities
        for ($id = $relativeEntityId + 1; $id <= $entityId - 1; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id + 1, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }

        // check $entityId entity
        $relatedEntity = $entity->relatedEntities()->find($entityId);
        $this->assertEquals($relativeEntityId + 1, $relatedEntity->pivot->morph_to_many_related_entity_position);

        // check [1 .. $entityId - 1] entities
        for ($id = $entityId + 1; $id <= $countTotal; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
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
        $entity = new MorphToManyEntityOne();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($entityId);
        $relyEntity = $entity->relatedEntities()->find($relativeEntityId);

        $entity->relatedEntities()->moveBefore($moveEntity, $relyEntity);

        $this->assertEquals($relativeEntityId, $moveEntity->pivot->morph_to_many_related_entity_position);
        $this->assertEquals($relativeEntityId + 1, $relyEntity->pivot->morph_to_many_related_entity_position);

        // check [1 .. $entityId - 1] entities
        for ($id = 1; $id < $relativeEntityId - 1; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }

        // check [1 .. $entityId - 1] entities
        for ($id = $relativeEntityId; $id < $entityId - 1; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id + 1, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }

        // check $entityId entity
        $relatedEntity = $entity->relatedEntities()->find($entityId);
        $this->assertEquals($relativeEntityId, $relatedEntity->pivot->morph_to_many_related_entity_position);

        // check [1 .. $entityId - 1] entities
        for ($id = $entityId + 1; $id <= $countTotal; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
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
        $entity = new MorphToManyEntityOne();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($entityId);
        $relyEntity = $entity->relatedEntities()->find($relativeEntityId);

        $entity->relatedEntities()->moveBefore($moveEntity, $relyEntity);

        $this->assertEquals($relativeEntityId - 1, $moveEntity->pivot->morph_to_many_related_entity_position);
        $this->assertEquals($relativeEntityId, $relyEntity->pivot->morph_to_many_related_entity_position);

        // check [1 .. $entityId - 1] entities
        for ($id = 1; $id < $entityId; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }

        $relatedEntity = $entity->relatedEntities()->find($entityId);
        $this->assertEquals($relativeEntityId - 1, $relatedEntity->pivot->morph_to_many_related_entity_position);

        // check [1 .. $entityId - 1] entities
        for ($id = $entityId + 1; $id < $relativeEntityId; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id - 1, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }

        // check [1 .. $entityId - 1] entities
        for ($id = $relativeEntityId; $id <= $countTotal; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }
    }

    /**
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityIsRelativeEntityProvider
     */
    public function testMoveBeforeWhenMovedEntityIsRelativeEntity($entityId, $countTotal)
    {
        $entity = new MorphToManyEntityOne();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($entityId);
        $entity->relatedEntities()->moveBefore($moveEntity, $moveEntity);

        $this->assertEquals($entityId, $moveEntity->pivot->morph_to_many_related_entity_position);

        for ($id = 1; $id <= $countTotal; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }
    }

    /**
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityIsRelativeEntityProvider
     */
    public function testMoveAfterWhenMovedEntityIsRelativeEntity($entityId, $countTotal)
    {
        $entity = new MorphToManyEntityOne();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($entityId);
        $entity->relatedEntities()->moveAfter($moveEntity, $moveEntity);

        $this->assertEquals($entityId, $moveEntity->pivot->morph_to_many_related_entity_position);

        for ($id = 1; $id <= $countTotal; ++$id) {
            $relatedEntity = $entity->relatedEntities()->find($id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }
    }

    /**
     * @param
     * @param
     * @param
     * @dataProvider allProvider
     */
    public function testMoveAfterOtherRelatedNotChanged($entityId, $relativeEntityId, $countTotal)
    {
        $entity = new MorphToManyEntityOne();
        $entity->save();
        $relatedEntities = [];

        $otherEntity = new MorphToManyEntityTwo();
        $otherEntity->save();
        $otherRelatedEntities = [];

        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;

            $otherRelatedEntity = new MorphToManyRelatedEntity();
            $otherEntity->relatedEntities()->save($otherRelatedEntity);
            $otherRelatedEntities[$i] = $otherRelatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $relyEntity = $entity->relatedEntities()->find($relatedEntities[$relativeEntityId]->id);

        $entity->relatedEntities()->moveAfter($moveEntity, $relyEntity);

        for ($id = 1; $id <= $countTotal; ++$id) {
            $relatedEntity = $otherEntity->relatedEntities()->find($otherRelatedEntities[$id]->id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }
    }

    /**
     * @param
     * @param
     * @param
     * @dataProvider allProvider
     */
    public function testMoveBeforeOtherRelatedNotChanged($entityId, $relativeEntityId, $countTotal)
    {
        $entity = new MorphToManyEntityOne();
        $entity->save();
        $relatedEntities = [];

        $otherEntity = new MorphToManyEntityTwo();
        $otherEntity->save();
        $otherRelatedEntities = [];

        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new MorphToManyRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;

            $otherRelatedEntity = new MorphToManyRelatedEntity();
            $otherEntity->relatedEntities()->save($otherRelatedEntity);
            $otherRelatedEntities[$i] = $otherRelatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $relyEntity = $entity->relatedEntities()->find($relatedEntities[$relativeEntityId]->id);

        $entity->relatedEntities()->moveBefore($moveEntity, $relyEntity);

        for ($id = 1; $id <= $countTotal; ++$id) {
            $relatedEntity = $otherEntity->relatedEntities()->find($otherRelatedEntities[$id]->id);
            $this->assertEquals($id, $relatedEntity->pivot->morph_to_many_related_entity_position);
        }
    }

    /**
     * @return array
     */
    public function moveWhenMovedEntityComesBeforeRelativeEntityProvider()
    {
        return [
            [1, 7, 30],
            [7, 9, 30],
            [15, 30, 30],
            [1, 30, 30],
            [1, 2, 30],
            [29, 30, 30],
        ];
    }

    /**
     * @return array
     */
    public function moveWhenMovedEntityComesAfterRelativeEntityProvider()
    {
        return [
            [7, 1, 30],
            [9, 7, 30],
            [30, 15, 30],
            [30, 1, 30],
            [2, 1, 30],
            [30, 29, 30],
        ];
    }

    /**
     * @return array
     */
    public function moveWhenMovedEntityIsRelativeEntityProvider()
    {
        return [
            [1, 30],
            [7, 30],
            [30, 30],
        ];
    }

    /**
     * @return array
     */
    public function allProvider()
    {
        return array_merge(
            $this->moveWhenMovedEntityComesAfterRelativeEntityProvider(),
            $this->moveWhenMovedEntityComesBeforeRelativeEntityProvider(),
            [
                [1, 1, 30],
                [7, 7, 30],
                [30, 30, 30],
            ]
        );
    }
}
