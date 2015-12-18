<?php

class MorphToManyEntityTwo extends \Illuminate\Database\Eloquent\Model
{
    use \Rutorika\Sortable\MorphToSortedManyTrait;

    public function relatedEntities()
    {
        return $this->morphToSortedMany('MorphToManyRelatedEntity', 'morphable', 'morph_to_many_related_entity_position');
    }
}
