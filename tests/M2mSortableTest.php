<?php
use PHPUnit\Framework\Attributes\DataProvider;


require_once 'stubs/M2mEntity.php';
require_once 'stubs/M2mRelatedEntity.php';
require_once 'SortableTestBase.php';

class M2mSortableTest extends SortableTestBase
{
    public function setUp(): void
    {
        parent::setUp();

        // fix for "Eloquent model events are not triggered when testing" https://github.com/laravel/framework/issues/1181
        M2mRelatedEntity::boot();
    }

    public function testPositionOnSave()
    {
        $entity = new M2mEntity();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
        }

        $entity = new M2mEntity();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
        }

        $prevPosition = null;

        foreach ($entity->relatedEntities as $relatedEntity) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $relatedEntity->pivot->m2m_related_entity_position);
            }
            $prevPosition = $relatedEntity->pivot->m2m_related_entity_position;
        }
    }

    public function testPositionOnAttach()
    {
        $entity = new M2mEntity();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $relatedEntity->save();
            $entity->relatedEntities()->attach($relatedEntity->id);
        }

        $entity = new M2mEntity();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $relatedEntity->save();
            $entity->relatedEntities()->attach($relatedEntity->id);
        }

        $prevPosition = null;

        foreach ($entity->relatedEntities as $relatedEntity) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $relatedEntity->pivot->m2m_related_entity_position);
            }
            $prevPosition = $relatedEntity->pivot->m2m_related_entity_position;
        }
    }

    
    #[DataProvider('syncProvider')]
    public function testPositionOnSync($entitiesToSync)
    {
        $entity = new M2mEntity();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
        }

        $entity = new M2mEntity();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $relatedEntity->save();
        }

        $entity->relatedEntities()->sync($entitiesToSync);

        $prevPosition = null;
        $index = 0;

        foreach ($entity->relatedEntities as $relatedEntity) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $relatedEntity->pivot->m2m_related_entity_position);
            }
            $this->assertEquals($relatedEntity->id, $entitiesToSync[$index]);

            $prevPosition = $relatedEntity->pivot->m2m_related_entity_position;
            $index++;
        }
    }

    
    #[DataProvider('syncProvider')]
    public function testPositionOnSyncWithExistedRelations($entitiesToSync)
    {
        $entity = new M2mEntity();
        $entity->save();

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
        }

        for ($i = 1; $i < 10; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $relatedEntity->save();
        }
        $entity->relatedEntities()->sync($entitiesToSync);

        $prevPosition = null;
        $index = 0;

        foreach ($entity->relatedEntities as $relatedEntity) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $relatedEntity->pivot->m2m_related_entity_position);
            }
            $this->assertEquals($relatedEntity->id, $entitiesToSync[$index]);

            $prevPosition = $relatedEntity->pivot->m2m_related_entity_position;
            $index++;
        }
    }

    public function testmoveBeforeRePositionNotChangeNotRelated()
    {
        $entities = [];
        $relatedEntities = [];

        for ($i = 1; $i < 5; ++$i) {
            $entity = new M2mEntity();
            $entity->save();
            $entities[] = $entity;
        }

        for ($i = 1; $i < 50; ++$i) {
            $entityKey = array_rand($entities);
            /** @var M2mEntity $entity */
            $entity = $entities[$entityKey];

            if (empty($relatedEntities[$entity->id])) {
                $relatedEntities[$entity->id] = [];
            }

            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$entity->id][] = $relatedEntity;
        }

        foreach ($entities as $entity) {
            $prevPosition = null;

            foreach ($entity->relatedEntities as $relatedEntity) {
                if ($prevPosition !== null) {
                    $this->assertGreaterThan($prevPosition, $relatedEntity->pivot->m2m_related_entity_position);
                }
                $prevPosition = $relatedEntity->pivot->m2m_related_entity_position;
            }
        }

        $entity = array_pop($entities);

        $firstRelatedEntity = $entity->relatedEntities->first();
        $secondRelatedEntity = $entity->relatedEntities->last();

        $entity->relatedEntities()->moveBefore($firstRelatedEntity, $secondRelatedEntity);

        $prevPosition = null;
        foreach ($entity->relatedEntities()->get() as $reordered) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $reordered->pivot->m2m_related_entity_position);
            }
            $prevPosition = $reordered->pivot->m2m_related_entity_position;
        }

        foreach ($entities as $entity) {
            $prevPos = null;
            foreach ($entity->relatedEntities()->get() as $relatedEntity) {
                if ($prevPos !== null) {
                    $this->assertGreaterThan($prevPos, $relatedEntity->pivot->m2m_related_entity_position);
                }
                $prevPos = $relatedEntity->pivot->m2m_related_entity_position;
            }
        }
    }

    
    #[DataProvider('moveWhenMovedEntityComesBeforeRelativeEntityProvider')]
    public function testMoveAfterWhenMovedEntityComesBeforeRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {
        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $relyEntity = $entity->relatedEntities()->find($relatedEntities[$relativeEntityId]->id);
        $relativePosition = $relyEntity->pivot->m2m_related_entity_position;

        $entity->relatedEntities()->moveAfter($moveEntity, $relyEntity);

        // Verify moved entity's position is now after relative entity's original position
        $this->assertGreaterThan($relativePosition, $moveEntity->pivot->m2m_related_entity_position);

        // Verify relative entity's position is unchanged
        $relyEntity->refresh();
        $this->assertEquals($relativePosition, $relyEntity->pivot->m2m_related_entity_position);

        // Verify all entities are correctly sorted in the relationship
        $sortedRelatedEntities = $entity->relatedEntities()->get();
        $prevPosition = null;
        foreach ($sortedRelatedEntities as $related) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $related->pivot->m2m_related_entity_position);
            }
            $prevPosition = $related->pivot->m2m_related_entity_position;
        }
    }

    
    #[DataProvider('moveWhenMovedEntityComesAfterRelativeEntityProvider')]
    public function testMoveAfterWhenMovedEntityComesAfterRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {
        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $relyEntity = $entity->relatedEntities()->find($relatedEntities[$relativeEntityId]->id);
        $relativePosition = $relyEntity->pivot->m2m_related_entity_position;

        $entity->relatedEntities()->moveAfter($moveEntity, $relyEntity);

        // Verify moved entity's position is now after relative entity's original position
        $this->assertGreaterThan($relativePosition, $moveEntity->pivot->m2m_related_entity_position);

        // Verify relative entity's position is unchanged
        $relyEntity->refresh();
        $this->assertEquals($relativePosition, $relyEntity->pivot->m2m_related_entity_position);

        // Verify all entities are correctly sorted in the relationship
        $sortedRelatedEntities = $entity->relatedEntities()->get();
        $prevPosition = null;
        foreach ($sortedRelatedEntities as $related) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $related->pivot->m2m_related_entity_position);
            }
            $prevPosition = $related->pivot->m2m_related_entity_position;
        }
    }

    
    #[DataProvider('moveWhenMovedEntityComesAfterRelativeEntityProvider')]
    public function testMoveBeforeWhenMovedEntityComesAfterRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {
        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $relyEntity = $entity->relatedEntities()->find($relatedEntities[$relativeEntityId]->id);
        $relativePosition = $relyEntity->pivot->m2m_related_entity_position;

        $entity->relatedEntities()->moveBefore($moveEntity, $relyEntity);

        // Verify moved entity's position is now before relative entity's original position
        $this->assertLessThan($relativePosition, $moveEntity->pivot->m2m_related_entity_position);

        // Verify relative entity's position is unchanged
        $relyEntity->refresh();
        $this->assertEquals($relativePosition, $relyEntity->pivot->m2m_related_entity_position);

        // Verify all entities are correctly sorted in the relationship
        $sortedRelatedEntities = $entity->relatedEntities()->get();
        $prevPosition = null;
        foreach ($sortedRelatedEntities as $related) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $related->pivot->m2m_related_entity_position);
            }
            $prevPosition = $related->pivot->m2m_related_entity_position;
        }
    }

    
    #[DataProvider('moveWhenMovedEntityComesBeforeRelativeEntityProvider')]
    public function testMoveBeforeWhenMovedEntityComesBeforeRelativeEntity($entityId, $relativeEntityId, $countTotal)
    {
        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $relyEntity = $entity->relatedEntities()->find($relatedEntities[$relativeEntityId]->id);
        $relativePosition = $relyEntity->pivot->m2m_related_entity_position;

        $entity->relatedEntities()->moveBefore($moveEntity, $relyEntity);

        // Verify moved entity's position is now before relative entity's original position
        $this->assertLessThan($relativePosition, $moveEntity->pivot->m2m_related_entity_position);

        // Verify relative entity's position is unchanged
        $relyEntity->refresh();
        $this->assertEquals($relativePosition, $relyEntity->pivot->m2m_related_entity_position);

        // Verify all entities are correctly sorted in the relationship
        $sortedRelatedEntities = $entity->relatedEntities()->get();
        $prevPosition = null;
        foreach ($sortedRelatedEntities as $related) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $related->pivot->m2m_related_entity_position);
            }
            $prevPosition = $related->pivot->m2m_related_entity_position;
        }
    }

    
    #[DataProvider('moveWhenMovedEntityIsRelativeEntityProvider')]
    public function testMoveBeforeWhenMovedEntityIsRelativeEntity($entityId, $countTotal)
    {
        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $originalPosition = $moveEntity->pivot->m2m_related_entity_position;
        $entity->relatedEntities()->moveBefore($moveEntity, $moveEntity);

        // Moving entity before itself should not change anything
        $movedEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $this->assertEquals($originalPosition, $movedEntity->pivot->m2m_related_entity_position);

        // Verify all entities are still correctly sorted
        $prevPosition = null;
        foreach ($entity->relatedEntities()->get() as $related) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $related->pivot->m2m_related_entity_position);
            }
            $prevPosition = $related->pivot->m2m_related_entity_position;
        }
    }

    
    #[DataProvider('moveWhenMovedEntityIsRelativeEntityProvider')]
    public function testMoveAfterWhenMovedEntityIsRelativeEntity($entityId, $countTotal)
    {
        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];
        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $originalPosition = $moveEntity->pivot->m2m_related_entity_position;
        $entity->relatedEntities()->moveAfter($moveEntity, $moveEntity);

        // Moving entity after itself should not change anything
        $movedEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $this->assertEquals($originalPosition, $movedEntity->pivot->m2m_related_entity_position);

        // Verify all entities are still correctly sorted
        $prevPosition = null;
        foreach ($entity->relatedEntities()->get() as $related) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $related->pivot->m2m_related_entity_position);
            }
            $prevPosition = $related->pivot->m2m_related_entity_position;
        }
    }

    
    #[DataProvider('allProvider')]
    public function testMoveAfterOtherRelatedNotChanged($entityId, $relativeEntityId, $countTotal)
    {
        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];

        $otherEntity = new M2mEntity();
        $otherEntity->save();
        $otherRelatedEntities = [];

        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;

            $otherRelatedEntity = new M2mRelatedEntity();
            $otherEntity->relatedEntities()->save($otherRelatedEntity);
            $otherRelatedEntities[$i] = $otherRelatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $relyEntity = $entity->relatedEntities()->find($relatedEntities[$relativeEntityId]->id);

        $entity->relatedEntities()->moveAfter($moveEntity, $relyEntity);

        $prevPosition = null;
        foreach ($otherEntity->relatedEntities()->get() as $relatedEntity) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $relatedEntity->pivot->m2m_related_entity_position);
            }
            $prevPosition = $relatedEntity->pivot->m2m_related_entity_position;
        }
    }

    
    #[DataProvider('allProvider')]
    public function testMoveBeforeOtherRelatedNotChanged($entityId, $relativeEntityId, $countTotal)
    {
        $entity = new M2mEntity();
        $entity->save();
        $relatedEntities = [];

        $otherEntity = new M2mEntity();
        $otherEntity->save();
        $otherRelatedEntities = [];

        for ($i = 1; $i <= $countTotal; ++$i) {
            $relatedEntity = new M2mRelatedEntity();
            $entity->relatedEntities()->save($relatedEntity);
            $relatedEntities[$i] = $relatedEntity;

            $otherRelatedEntity = new M2mRelatedEntity();
            $otherEntity->relatedEntities()->save($otherRelatedEntity);
            $otherRelatedEntities[$i] = $otherRelatedEntity;
        }

        $moveEntity = $entity->relatedEntities()->find($relatedEntities[$entityId]->id);
        $relyEntity = $entity->relatedEntities()->find($relatedEntities[$relativeEntityId]->id);

        $entity->relatedEntities()->moveBefore($moveEntity, $relyEntity);

        $prevPosition = null;
        foreach ($otherEntity->relatedEntities()->get() as $relatedEntity) {
            if ($prevPosition !== null) {
                $this->assertGreaterThan($prevPosition, $relatedEntity->pivot->m2m_related_entity_position);
            }
            $prevPosition = $relatedEntity->pivot->m2m_related_entity_position;
        }
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
            [1, 30, 30],
            [1, 2, 30],
            [29, 30, 30],
        ];
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
            [30, 1, 30],
            [2, 1, 30],
            [30, 29, 30],
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
    public static function syncProvider()
    {
        return [
            [[12, 13, 16]],
            [[9, 6, 4]],
            [[16, 6, 13]],
            [[16, 6, 8]],
        ];
    }

    /**
     * @return array
     */
    public static function allProvider()
    {
        return array_merge(
            self::moveWhenMovedEntityComesAfterRelativeEntityProvider(),
            self::moveWhenMovedEntityComesBeforeRelativeEntityProvider(),
            [
                [1, 1, 30],
                [7, 7, 30],
                [30, 30, 30],
            ]
        );
    }
}
