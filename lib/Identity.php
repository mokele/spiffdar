<?php

/**
 * Represents a single person across many Sessions.
 */
class Identity
{
    private $id;
    public function __construct($id)
    {
        $this->id = $id;
    }
    public function __get($var)
    {
        return $this->{$var};
    }
}

?>