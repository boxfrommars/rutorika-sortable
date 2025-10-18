<?php

use PHPUnit\Framework\Attributes\DataProvider;

require_once 'stubs/SortableEntityWithChangedField.php';
require_once 'SortableTestBase.php';

class SortableTraitWithChangedFieldTest extends SortableTestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        // fix for "Eloquent model events are not triggered when testing" https://github.com/laravel/framework/issues/1181
        SortableEntityWithChangedField::boot();
    }

    public function test_position_on_create()
    {
        $entity = new SortableEntityWithChangedField;
        $entity->save();
        $this->assertEquals('U', $entity->somefield);

        $entity2 = new SortableEntityWithChangedField;
        $entity2->save();
        $this->assertEquals('V', $entity2->somefield);
    }

    public function test_position()
    {

        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= 30; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
            $this->assertEquals($i, $entities[$i]->id);
        }

        // Verify entities can be sorted by somefield and come back in creation order
        $sorted = SortableEntityWithChangedField::sorted()->get();
        $this->assertEquals(30, $sorted->count());

        foreach ($sorted as $index => $entity) {
            $this->assertEquals($index + 1, $entity->id);
        }
    }

    #[DataProvider('moveWhenMovedEntityComesBeforeRelativeEntityProvider')]
    public function test_move_after_when_moved_entity_comes_before_relative_entity($entityId, $relativeEntityId, $countTotal)
    {

        // intervals: [1 .. $entityId - 1], [$entityId], [$entityId + 1 .. $relativeEntityId], [$relativeEntityId .. $countTotal]
        // After moveAfter: [1 .. $entityId - 1], [$entityId + 1 .. $relativeEntityId], [$relativeEntityId], [moved $entityId], [$relativeEntityId + 1 .. $countTotal]

        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];
        $relativePosition = $relyEntity->somefield;

        $moveEntity->moveAfter($relyEntity);

        // Verify moved entity's position is now after relative entity's original position
        $this->assertGreaterThan($relativePosition, $moveEntity->somefield);

        // Verify relative entity's position is unchanged
        $relyEntity->refresh();
        $this->assertEquals($relativePosition, $relyEntity->somefield);

        // Verify all entities still exist with positions
        $allEntities = SortableEntityWithChangedField::sorted()->get();
        $this->assertEquals($countTotal, $allEntities->count());

        // Verify the moved entity appears after the relative entity in sorted order
        $entityIds = $allEntities->pluck('id')->toArray();
        $movedIndex = array_search($entityId, $entityIds);
        $relativeIndex = array_search($relativeEntityId, $entityIds);

        $this->assertGreaterThan($relativeIndex, $movedIndex);
    }

    #[DataProvider('moveWhenMovedEntityComesAfterRelativeEntityProvider')]
    public function test_move_after_when_moved_entity_comes_after_relative_entity($entityId, $relativeEntityId, $countTotal)
    {
        // interavls: [1 .. $relativeEntityId], , [$relativeEntityId + 1 .. $entityId - 1], [$entityId], [$entityId + 1 .. $countTotal]

        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];
        $relativePosition = $relyEntity->somefield;

        $moveEntity->moveAfter($relyEntity);

        // Verify moved entity's position is now after relative entity's original position
        $this->assertGreaterThan($relativePosition, $moveEntity->somefield);

        // Verify relative entity's position is unchanged
        $relyEntity->refresh();
        $this->assertEquals($relativePosition, $relyEntity->somefield);

        // Verify all entities still exist with positions
        $allEntities = SortableEntityWithChangedField::sorted()->get();
        $this->assertEquals($countTotal, $allEntities->count());

        // Verify the moved entity appears after the relative entity in sorted order
        $entityIds = $allEntities->pluck('id')->toArray();
        $movedIndex = array_search($entityId, $entityIds);
        $relativeIndex = array_search($relativeEntityId, $entityIds);

        $this->assertGreaterThan($relativeIndex, $movedIndex);
    }

    #[DataProvider('moveWhenMovedEntityIsRelativeEntityProvider')]
    public function test_move_after_when_moved_entity_is_relative_entity($entityId, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $originalPosition = $moveEntity->somefield;

        // Moving entity after itself should not change anything
        $moveEntity->moveAfter($moveEntity);

        // Position should remain the same
        $moveEntity->refresh();
        $this->assertEquals($originalPosition, $moveEntity->somefield);

        // Verify all entities still exist with unchanged positions
        $allEntities = SortableEntityWithChangedField::sorted()->get();
        $this->assertEquals($countTotal, $allEntities->count());
    }

    #[DataProvider('moveWhenMovedEntityComesBeforeRelativeEntityProvider')]
    public function test_move_before_when_moved_entity_comes_before_relative_entity($entityId, $relativeEntityId, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];
        $relativePosition = $relyEntity->somefield;

        $moveEntity->moveBefore($relyEntity);

        // Verify moved entity's position is now before relative entity's original position
        $this->assertLessThan($relativePosition, $moveEntity->somefield);

        // Verify relative entity's position is unchanged
        $relyEntity->refresh();
        $this->assertEquals($relativePosition, $relyEntity->somefield);

        // Verify all entities still exist with positions
        $allEntities = SortableEntityWithChangedField::sorted()->get();
        $this->assertEquals($countTotal, $allEntities->count());

        // Verify the moved entity appears before the relative entity in sorted order
        $entityIds = $allEntities->pluck('id')->toArray();
        $movedIndex = array_search($entityId, $entityIds);
        $relativeIndex = array_search($relativeEntityId, $entityIds);

        $this->assertLessThan($relativeIndex, $movedIndex);
    }

    #[DataProvider('moveWhenMovedEntityComesAfterRelativeEntityProvider')]
    public function test_move_before_when_moved_entity_comes_after_relative_entity($entityId, $relativeEntityId, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];
        $relativePosition = $relyEntity->somefield;

        $moveEntity->moveBefore($relyEntity);

        // Verify moved entity's position is now before relative entity's original position
        $this->assertLessThan($relativePosition, $moveEntity->somefield);

        // Verify relative entity's position is unchanged
        $relyEntity->refresh();
        $this->assertEquals($relativePosition, $relyEntity->somefield);

        // Verify all entities still exist with positions
        $allEntities = SortableEntityWithChangedField::sorted()->get();
        $this->assertEquals($countTotal, $allEntities->count());

        // Verify the moved entity appears before the relative entity in sorted order
        $entityIds = $allEntities->pluck('id')->toArray();
        $movedIndex = array_search($entityId, $entityIds);
        $relativeIndex = array_search($relativeEntityId, $entityIds);

        $this->assertLessThan($relativeIndex, $movedIndex);
    }

    #[DataProvider('moveWhenMovedEntityIsRelativeEntityProvider')]
    public function test_move_before_when_moved_entity_is_relative_entity($entityId, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= $countTotal; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $originalPosition = $moveEntity->somefield;

        // Moving entity before itself should not change anything
        $moveEntity->moveBefore($moveEntity);

        // Position should remain the same
        $moveEntity->refresh();
        $this->assertEquals($originalPosition, $moveEntity->somefield);

        // Verify all entities still exist with unchanged positions
        $allEntities = SortableEntityWithChangedField::sorted()->get();
        $this->assertEquals($countTotal, $allEntities->count());
    }

    public function test_sorted_scope()
    {
        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= 30; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }

        $entities[7]->moveAfter($entities[9]);
        $entities[6]->moveAfter($entities[12]);
        $entities[5]->moveBefore($entities[2]);

        $sortedEntities = SortableEntityWithChangedField::sorted()->get();

        $prevEntityPosition = null;

        foreach ($sortedEntities as $sortedEntity) {
            if ($prevEntityPosition !== null) {
                $this->assertGreaterThan($prevEntityPosition, $sortedEntity->somefield);
            }
            $prevEntityPosition = $sortedEntity->somefield;
        }
    }

    #[DataProvider('getPreviousNextEntityProvider')]
    public function test_get_previous($entityId, $limit)
    {
        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= 30; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }
        /** @var SortableEntity $entity */
        $entity = $entities[$entityId];

        $previous = $entity->getPrevious($limit);

        $expectedCount = $limit ? min($limit, $entityId - 1) : $entityId - 1;
        $this->assertEquals($expectedCount, $previous->count());

        /** @var SortableEntity|null $curr */
        $curr = null;

        $startId = $entityId - $expectedCount;

        foreach ($previous as $prev) {
            $this->assertEquals($startId, $prev->id);
            $curr = $prev;
            $startId++;
        }
    }

    #[DataProvider('getPreviousNextEntityProvider')]
    public function test_get_next($entityId, $limit)
    {
        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= 30; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }
        /** @var SortableEntity $entity */
        $entity = $entities[$entityId];

        $next = $entity->getNext($limit);

        $totalNext = count($entities) - $entityId;
        $expectedCount = $limit ? min($totalNext, $limit) : $totalNext;
        $this->assertEquals($expectedCount, $next->count());

        /** @var SortableEntity|null $curr */
        $curr = null;

        $startId = $entityId + 1;

        foreach ($next as $ent) {
            $this->assertEquals($startId, $ent->id);
            $curr = $ent;
            $startId++;
        }
    }

    #[DataProvider('getPreviousNextEntityProvider')]
    public function test_defaults_previous($entityId, $limit)
    {
        $entities = [];
        for ($i = 1; $i <= 30; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }
        /** @var SortableEntity $entity */
        $entity = $entities[$entityId];

        $expectedEntities = $entity->getPrevious(0);
        $previous = $entity->getPrevious();
        $this->assertEquals($expectedEntities->count(), $previous->count());
        for ($i = 0; $i < $previous->count(); $i++) {
            $this->assertEquals($expectedEntities->offsetGet($i)->id, $previous->offsetGet($i)->id);
        }
    }

    #[DataProvider('getPreviousNextEntityProvider')]
    public function test_defaults_next($entityId, $limit)
    {
        $entities = [];
        for ($i = 1; $i <= 30; $i++) {
            $entities[$i] = new SortableEntityWithChangedField;
            $entities[$i]->save();
        }
        /** @var SortableEntity $entity */
        $entity = $entities[$entityId];

        $expectedEntities = $entity->getNext(0);
        $next = $entity->getNext();
        $this->assertEquals($expectedEntities->count(), $next->count());
        for ($i = 0; $i < $next->count(); $i++) {
            $this->assertEquals($expectedEntities->offsetGet($i)->id, $next->offsetGet($i)->id);
        }
    }

    /**
     * @return array
     */
    public static function moveWhenMovedEntityComesAfterRelativeEntityProvider()
    {
        return [
            [7, 1, 30],
            [9, 7, 30],
            [30, 15, 30],
        ];
    }

    /**
     * @return array
     */
    public static function moveWhenMovedEntityComesBeforeRelativeEntityProvider()
    {
        return [
            [1, 7, 30],
            [7, 9, 30],
            [15, 30, 30],
        ];
    }

    /**
     * @return array
     */
    public static function moveWhenMovedEntityIsRelativeEntityProvider()
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
    public static function getPreviousNextEntityProvider()
    {
        return [
            [5, 0],
            [5, 1],
            [1, 1],
            [10, 1],
            [30, 1],
            [5, 12],
            [1, 10],
            [10, 4],
            [30, 4],
        ];
    }
}
