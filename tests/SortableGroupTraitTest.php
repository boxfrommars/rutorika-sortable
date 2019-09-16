<?php

require_once 'stubs/SortableGroupEntity.php';
require_once 'SortableTestBase.php';

class SortableGroupTraitTest extends SortableTestBase
{
    public function setUp(): void
    {
        parent::setUp();

        // fix for "Eloquent model events are not triggered when testing" https://github.com/laravel/framework/issues/1181
        SortableEntityGroup::boot();
    }

    public function testGroupName()
    {
        $this->assertEquals('category', SortableEntityGroup::getSortableGroupField());
    }

    public function testSortableGroupField()
    {
        $entity = new SortableEntityGroup();
        $entity->category = 'some_category';
        $entity->save();

        $sortableGroupField = SortableEntityGroup::getSortableGroupField();

        $this->assertEquals('some_category', $entity->$sortableGroupField);
    }

    public function testPosition()
    {
        $categories = ['first', 'second', 'third'];

        /** @var SortableEntity[] $entities */
        $entities = [];
        for ($i = 0; $i < 30; ++$i) {
            $category = $categories[array_rand($categories)];
            $entity = new SortableEntityGroup();
            $entity->category = $category;
            $entity->save();

            $entities[$category][] = $entity;
        }

        foreach ($entities as $entityCategoryName => $entityCategoryEntities) {
            foreach ($entityCategoryEntities as $key => $entityCategoryEntity) {
                $this->assertEquals($key + 1, $entityCategoryEntity->position);
            }
        }
    }

    /**
     * @param
     * @param
     * @param
     * @param
     * @dataProvider fixedEntitiesProvider
     */
    public function testFixedEntities($entityId, $relativeEntityId, $method, $countTotal)
    {

        /** @var SortableEntity[] $entities */
        $entities = [];
        $fixedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $entities[$i] = new SortableEntityGroup();
            $entities[$i]->category = 'first_category';
            $entities[$i]->save();

            $fixedEntities[$i] = new SortableEntityGroup();
            $fixedEntities[$i]->category = 'second_category';
            $fixedEntities[$i]->save();
        }

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        switch ($method) {
            case 'after':
                $moveEntity->moveAfter($relyEntity);
                break;
            case 'before':
                $moveEntity->moveBefore($relyEntity);
                break;

        }

        $left = min($entityId, $relativeEntityId);
        $right = max($entityId, $relativeEntityId);

        for ($id = 1; $id < $left; ++$id) {
            $entity = SortableEntityGroup::find($entities[$id]->id);
            $this->assertEquals($id, $entity->position);
        }

        for ($id = $right + 1; $id <= $countTotal; ++$id) {
            $entity = SortableEntityGroup::find($entities[$id]->id);
            $this->assertEquals($id, $entity->position);
        }

        foreach ($fixedEntities as $expextedPosition => $entity) {
            $entity = SortableEntityGroup::find($entity->id);
            $this->assertEquals($expextedPosition, $entity->position);
        }
    }

    public function generateEntities($count)
    {

        /** @var SortableEntity[] $entities */
        $entities = [];
        $fixedEntities = [];
        for ($i = 1; $i <= $count; ++$i) {
            $entities[$i] = new SortableEntityGroup();
            $entities[$i]->category = 'first_category';
            $entities[$i]->save();

            $fixedEntities[$i] = new SortableEntityGroup();
            $fixedEntities[$i]->category = 'second_category';
            $fixedEntities[$i]->save();
        }

        return $entities;
    }

    /**
     * @param
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityComesBeforeRelativeEntityProvider
     */
    public function testMoveAfterWhenMovedEntityComesBeforeRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {
        /** @var SortableEntity[] $entities */
        $entities = $this->generateEntities($countTotal);

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        $moveEntity->moveAfter($relyEntity);

        $this->assertEquals($relativeEntityId, $moveEntity->position);
        $this->assertEquals($relativeEntityId - 1, $relyEntity->position);

        for ($id = $entityId + 1; $id < $relativeEntityId; ++$id) {
            $entity = SortableEntityGroup::find($entities[$id]->id);
            $this->assertEquals($id - 1, $entity->position);
        }

        $entity = SortableEntityGroup::find($moveEntity->id);
        $this->assertEquals($relativeEntityId, $entity->position);

        $entity = SortableEntityGroup::find($relyEntity->id);
        $this->assertEquals($relativeEntityId - 1, $entity->position);
    }

    /**
     * @param
     * @param
     * @param
     * @dataProvider moveWhenMovedEntityComesAfterRelativeEntityProvider
     */
    public function testMoveAfterWhenMovedEntityComesAfterRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {
        /* @var SortableEntityGroup[] $entity */
        /** @var SortableEntityGroup[] $entities */
        $entities = $this->generateEntities($countTotal);

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        $moveEntity->moveAfter($relyEntity);

        $this->assertEquals($relativeEntityId + 1, $moveEntity->position);
        $this->assertEquals($relativeEntityId, $relyEntity->position);

        for ($id = $relativeEntityId + 1; $id < $entityId; ++$id) {
            $entity = SortableEntityGroup::find($entities[$id]->id);
            $this->assertEquals($id + 1, $entity->position);
        }

        $entity = SortableEntityGroup::find($moveEntity->id);
        $this->assertEquals($relativeEntityId + 1, $entity->position);

        $entity = SortableEntityGroup::find($relyEntity->id);
        $this->assertEquals($relativeEntityId, $entity->position);
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
        $entities = $this->generateEntities($countTotal);

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        $moveEntity->moveBefore($relyEntity);

        $this->assertEquals($relativeEntityId - 1, $moveEntity->position);
        $this->assertEquals($relativeEntityId, $relyEntity->position);

        for ($id = $entityId + 1; $id < $relativeEntityId; ++$id) {
            $entity = SortableEntityGroup::find($entities[$id]->id);
            $this->assertEquals($id - 1, $entity->position);
        }

        $entity = SortableEntityGroup::find($moveEntity->id);
        $this->assertEquals($relativeEntityId - 1, $entity->position);

        $entity = SortableEntityGroup::find($relyEntity->id);
        $this->assertEquals($relativeEntityId, $entity->position);
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
        $entities = $this->generateEntities($countTotal);

        $moveEntity = $entities[$entityId];
        $relyEntity = $entities[$relativeEntityId];

        $moveEntity->moveBefore($relyEntity);

        $this->assertEquals($relativeEntityId, $moveEntity->position);
        $this->assertEquals($relativeEntityId + 1, $relyEntity->position);

        for ($id = $relativeEntityId + 1; $id < $entityId; ++$id) {
            $entity = SortableEntityGroup::find($entities[$id]->id);
            $this->assertEquals($id + 1, $entity->position);
        }

        $entity = SortableEntityGroup::find($moveEntity->id);
        $this->assertEquals($relativeEntityId, $entity->position);

        $entity = SortableEntityGroup::find($relyEntity->id);
        $this->assertEquals($relativeEntityId + 1, $entity->position);
    }

    public function testInvalidAfterMove()
    {
        $entity1 = new SortableEntityGroup();
        $entity1->category = 'first_category';
        $entity1->save();

        $entity2 = new SortableEntityGroup();
        $entity2->category = 'second_category';
        $entity2->save();

        $this->expectException(\Rutorika\Sortable\SortableException::class);
        $entity1->moveAfter($entity2);
    }

    public function testInvalidBeforeMove()
    {
        $entity1 = new SortableEntityGroup();
        $entity1->category = 'first_category';
        $entity1->save();

        $entity2 = new SortableEntityGroup();
        $entity2->category = 'second_category';
        $entity2->save();

        $this->expectException(\Rutorika\Sortable\SortableException::class);
        $entity1->moveBefore($entity2);
    }

    /**
     * @return array
     */
    public function fixedEntitiesProvider()
    {
        return [
            [2, 5, 'before', 30],
            [1, 6, 'before', 30],
            [1, 7, 'before', 30],
            [3, 5, 'before', 30],
            [5, 10, 'before', 30],
            [7, 9, 'before', 30],
            [15, 30, 'before', 30],
            [16, 30, 'before', 30],
            [7, 1, 'before', 30],
            [6, 1, 'before', 30],
            [5, 3, 'before', 30],
            [9, 7, 'before', 30],
            [10, 5, 'before', 30],
            [30, 15, 'before', 30],
            [1, 1, 'before', 30],
            [7, 7, 'before', 30],
            [30, 30, 'before', 30],

            [2, 5, 'after', 30],
            [1, 6, 'after', 30],
            [1, 7, 'after', 30],
            [3, 5, 'after', 30],
            [5, 10, 'after', 30],
            [7, 9, 'after', 30],
            [15, 30, 'after', 30],
            [16, 30, 'after', 30],
            [7, 1, 'after', 30],
            [6, 1, 'after', 30],
            [5, 3, 'after', 30],
            [9, 7, 'after', 30],
            [10, 5, 'after', 30],
            [30, 15, 'after', 30],
            [1, 1, 'after', 30],
            [7, 7, 'after', 30],
            [30, 30, 'after', 30],
        ];
    }

    /**
     * @return array
     */
    public function moveWhenMovedEntityComesAfterRelativeEntityProvider()
    {
        return [
            [7, 1, 30],
            [6, 1, 30],
            [5, 3, 30],
            [9, 7, 30],
            [10, 5, 30],
            [30, 15, 30],
        ];
    }

    /**
     * @return array
     */
    public function moveWhenMovedEntityComesBeforeRelativeEntityProvider()
    {
        return [
            [2, 5, 30],
            [1, 6, 30],
            [1, 7, 30],
            [3, 5, 30],
            [5, 10, 30],
            [7, 9, 30],
            [15, 30, 30],
            [16, 30, 30],
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
}
