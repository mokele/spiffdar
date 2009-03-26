<?php

/**
 * Represents the list of Spiffs that a specific Session should have listed.
 */
class SpiffList
{
    private $id, $identity_id, $session_id, $json;
    
    const VERSION = 0.1;
    private $spiffs = array();
    
    public function __construct(Session $session = null)
    {
        if($session)
        {
            global $dbconn;
            $sql = 'SELECT * FROM spifflist WHERE session_id = $1 LIMIT 1';
            $params = array($session->id);
            $result = pg_query_params($dbconn, $sql, $params);
            if($result && $row = pg_fetch_assoc($result))
            {
                $this->id = $row['id'];
                $this->identity_id = $row['identity_id'];
                $this->session_id = $row['session_id'];
                $this->json = json_decode($row['json'], true);
                if($this->json['version'] == self::VERSION)
                {
                    $this->spiffs = array();
                    foreach($this->json['spiffs'] as $spiff)
                    {
                        $this->spiffs[$spiff] = new Spiff($spiff);
                    }
                }
                else
                {
                    //upgrade
                }
            }
        }
    }
    public function __get($var)
    {
        return $this->{$var};
    }
    
    public function add(Spiff $spiff)
    {
        if(isset($this->spiffs[$spiff->id]))
        {
            return false;
        }
        $this->spiffs[$spiff->id] = $spiff;
        return true;
    }
    public function remove($spiff_id)
    {
        unset($this->spiffs[$spiff->id]);
    }
    
    public function toJSON()
    {
        $struct = array(
            'version' => self::VERSION,
            'spiffs' => array(),
        );
        foreach($this->spiffs as $spiff)
        {
            $struct['spiffs'][] = $spiff->id;
        }
        return json_encode($struct);
    }
    
    public function save(Session $session)
    {
        global $dbconn;
        
        //currently ignoring identities until they're implemented
        $identity = $session->getIdentity();
        
        if($this->identity_id && $this->identity_id != $identity->id)
        {
            throw new UnauthorizedException("This use can't save this SpiffList");
        }
        if($this->id)
        {
            $sql = 'UPDATE spifflist SET json = $1 WHERE id = $2';
            $params = array($this->toJSON(), $this->id);
        }
        else
        {
            $id_result = pg_query($dbconn, 'SELECT nextval(\'spifflist_id_seq\')');
            if(!$id_result)
            {
                throw new DBQueryException("Can't get nextval spifflist_id_seq");
            }
            $id = array_pop(pg_fetch_row($id_result));
            $sql = 'INSERT INTO spifflist (id, json, session_id)
                    VALUES ($1, $2, $3)';
            $params = array($id, $this->toJSON(), $session->id);
        }
        $result = pg_query_params($dbconn, $sql, $params);
        if(!$result)
        {
            throw new DBQueryException("Failed saving SpiffList");
        }
        $this->id = $id;
        return $this;
    }
}

?>