<?php

/**
 * Represents an XSPF that was created by a Session.  If the Session has no Identity yet
 * then it will create one.  We're really not bothered about solving normalisation of the
 * lists at this time so we're just going to be storing a json string into a db field 
 * representing the list (why not a serialized xspf? I dunno, maybe it'll change to that!).
 */
class Spiff
{
    /**
     * If we update the format of $json then we can version control that and automatically
     * upgrade previous versions.
     */
    const VERSION = 0.1;
    /* persisted */
    private $id, $json, $identity_id, $url, $title, $annotation;
    /* not persisted */
    private $trackList;

    private $loaded = false;
    
    public function __construct()
    {
    }
    
    /**
     * fetches the list from the store
     */
    public function load($id)
    {
        if($this->loaded) return;
        
        //fetch from db
        global $dbconn;
        $sql = 'SELECT * FROM spiff WHERE id = $1 LIMIT 1';
        $params = array($id);
        $result = pg_query_params($dbconn, $sql, $params);
        if($result && $row = pg_fetch_assoc($result))
        {
            $this->loadRow($row);
        }
        $this->loaded = true;
        return $this;
    }
    private function loadRow($row)
    {
        $this->id = $row['id'];
        $this->derived_from = $row['derived_from'];
        $this->url = $row['url'];
        $this->session_id = $row['session_id'];
        $this->setTitle($json['title']);
        $this->setAnnotation($json['annotation']);
        $json = json_decode($row['json'], true);
        if($json['version'] != self::VERSION)
        {
            //upgrade
        }
        else
        {
            $this->setTrackList($json['trackList']);
        }
    }
    public function loadFromURL($url, Session $session)
    {
        if($this->loaded) return;
        
        $exists = false;
        
        //get from db
        global $dbconn;
        $sql = 'SELECT * FROM spiff WHERE url = $1 LIMIT 1';
        $params = array($url);
        $result = pg_query_params($dbconn, $sql, $params);
        if($result && $row = pg_fetch_assoc($result))
        {
            $this->loadRow($row);
            $exists = true;
        }
        $this->url = $url;
        
        $contents = file_get_contents($url);
        if($contents)
        {
            $xml = new SimpleXMLElement($contents);
            $shifting = array();
            $this->setTitle((string)$xml->title);
            $this->setAnnotation((string)$xml->annotation);
            $tracks = array();
            foreach($xml->trackList->track as $trackNode)
            {
                $tracks[] = array(
                    'creator' => (string)$trackNode->creator,
                    'track' => (string)$trackNode->title,
                );
            }
            $this->setTrackList($tracks);
        }
        else
        {
            //load tracks and data from db
        }
        if(!$exists)
        {
            $this->save($session);
        }
        $this->loaded = true;
        return $this;
    }
    public function __get($var)
    {
        return $this->{$var};
    }
    public function setTitle($title)
    {
        $this->title = $title;
    }
    public function setAnnotation($annotation)
    {
        $this->annotation = $annotation;
    }
    /**
     * saves a list of track and creator pairs to this Spiff
     */
    public function setTrackList($trackList)
    {
        $this->trackList = array();
        //copy values since we shouldn't trust the structure of $trackList
        //not having lots of other fields.
        foreach($trackList as $track)
        {
            $this->trackList[] = array(
                'track' => $track['track'],
                'creator' => $track['creator'],
            );
        }
        return $this;
    }
    public function toJSON()
    {
        $struct = array(
            'version' => self::VERSION,
            'trackList' => $this->trackList,
        );
        return json_encode($struct);
    }
    /**
     * Saves a Spiff against a Session's Identity
     */
    public function save(Session $session)
    {
        global $dbconn;
        
        //currently ignoring identities until they're implemented
        $identity = $session->getIdentity();
        
        if($this->identity_id && $this->identity_id != $identity->id)
        {
            throw new UnauthorizedException("This use can't save this Spiff");
        }
        $id_result = pg_query($dbconn, 'SELECT nextval(\'spiff_id_seq\')');
        if(!$id_result)
        {
            throw new DBQueryException("Can't get nextval spiff_id_seq");
        }
        $id = array_pop(pg_fetch_row($id_result));
        $sql = 'INSERT INTO spiff (id, title, annotation, json, session_id, url, derived_from)
                VALUES ($1, $2, $3, $4, $5, $6, $7)';
        $params = array($id, $this->title, $this->annotation, $this->toJSON(), $session->id, $this->url, $this->derived_from);
        $result = pg_query_params($dbconn, $sql, $params);
        if(!$result)
        {
            throw new DBQueryException("Failed saving Spiff");
        }
        $this->id = $id;
        return $this;
    }
    
    public function __clone()
    {
        $this->derived_from = $this->id;
        $this->id = null;
        $this->url = null;
    }
}

?>