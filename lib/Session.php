<?php

/**
 * Represents someone with an interface open to Spiffdar that is unique to the device they are using.
 * If an Identity has 2 computers then they will have 2 unique sessions.
 */
class Session
{
    private $key;
    private $identity;
    
    public function __construct()
    {
        
    }
    
    public function getIdentity()
    {
        return $this->identity;
    }
    public function save(Identity $identity)
    {
        $this->identity = $identity;
    }
}

?>