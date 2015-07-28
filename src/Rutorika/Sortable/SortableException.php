<?php

namespace Rutorika\Sortable;

/**
 * Define a custom exception class.
 */
class SortableException extends \Exception
{
    public function __construct($field1, $field2)
    {
        parent::__construct(sprintf('You can\'t move entities with different sortable group: %s %s', $field1, $field2));
    }
}
