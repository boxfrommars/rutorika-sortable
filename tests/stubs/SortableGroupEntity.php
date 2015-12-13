<?php

class SortableEntityGroup extends \Illuminate\Database\Eloquent\Model
{
    use Rutorika\Sortable\SortableTrait;

    protected $table = 'sortable_entities_group';

    protected static $sortableGroupField = 'category';
}
