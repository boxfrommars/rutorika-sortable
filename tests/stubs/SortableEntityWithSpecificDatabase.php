<?php

class SortableEntityWithSpecificDatabase extends \Illuminate\Database\Eloquent\Model
{
    use Rutorika\Sortable\SortableTrait;

    protected $connection = 'other';
}
