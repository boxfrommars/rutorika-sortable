<?php

class MorphToManyRelatedEntity extends \Illuminate\Database\Eloquent\Model
{
    use \AlexCrawford\Sortable\MorphToSortedManyTrait;

    public function entities()
    {
        return $this->morphedBySortedMany('MorphToManyEntityOne', 'morphable', 'morph_to_many_related_entity_position');
    }
}
