<?php

class SortableEntityWithSpecificDatabase extends \Illuminate\Database\Eloquent\Model
{
    use AlexCrawford\Sortable\SortableTrait;

    protected $connection = 'other';
}
