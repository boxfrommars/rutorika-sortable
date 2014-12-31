<?php

class SortableEntityGroup extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'sortable_entities_group';

    protected static $sortableGroupField = 'category';

    use Rutorika\Sortable\SortableTrait;
}
