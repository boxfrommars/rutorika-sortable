<?php

class SortableEntityWithChangedField extends \Illuminate\Database\Eloquent\Model
{
    use AlexCrawford\Sortable\SortableTrait;

    protected static $sortableField = 'somefield';
}
