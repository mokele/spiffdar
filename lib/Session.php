<?php

/**
 * Represents someone with an interface open to Spiffdar that is unique to the device they are using.
 * If an Identity has 2 computers then they will have 2 unique sessions.
 */
class Session
{
    private $id;
    private $encoded;
    private $identity;
    
    public function __construct()
    {
        $valid = false;
        if(isset($_COOKIE['SID']) && $this->genEncoded($_COOKIE['SID']) == $_COOKIE['ENC'])
        {
            $this->id = $_COOKIE['SID'];
            $this->encoded = $_COOKIE['ENC'];
        }
        else
        {
            $time = time();
            $rand = mt_rand();
            $this->id = sha1($time . '-' . $rand);
            $this->encoded = $this->genEncoded($this->id);
            $expire = $time+60*60*24*365;
            $path = '/';
            $domain = '.spiffdar.org';
            setcookie('SID', $this->id, $expire, $path, $domain);
            setcookie('ENC', $this->encoded, $expire, $path, $domain);
        }
    }
    private function genEncoded($id)
    {
        global $private_key;
        return crypt($id, $private_key);
    }
    public function __get($var)
    {
        return $this->{$var};
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