<?php

class SortableEntityWithChangedField extends \Illuminate\Database\Eloquent\Model
{
    use Rutorika\Sortable\SortableTrait;

    protected static $sortableField = 'somefield';
}
