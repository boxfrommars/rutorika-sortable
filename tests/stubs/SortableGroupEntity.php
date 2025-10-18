<?php

class SortableEntityGroup extends \Illuminate\Database\Eloquent\Model
{
    use AlexCrawford\Sortable\SortableTrait;

    protected $table = 'sortable_entities_group';

    protected static $sortableGroupField = 'category';
}
