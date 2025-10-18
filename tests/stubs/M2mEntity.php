<?php

class M2mEntity extends \Illuminate\Database\Eloquent\Model
{
    use \AlexCrawford\Sortable\BelongsToSortedManyTrait;

    public function relatedEntities()
    {
        return $this->belongsToSortedMany('M2mRelatedEntity', 'm2m_related_entity_position');
    }
}
