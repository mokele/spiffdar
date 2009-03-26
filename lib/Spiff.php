<?php

/**
 * Represents an XSPF that was created by a Session.  If the Session has no Identity yet
 * then it will create one.  We're really not bothered about solving normalisation of the
 * lists at this time so we're just going to be storing a json string into a db field 
 * representing the list.
 */
class Spiff
{
    /**
     * If we update the format of $json then we can version control that and automatically
     * upgrade previous versions.
     */
    const VERSION = 0.1;
    /* persisted */
    private $id, $json, $identity_id;
    /* not persisted */
    private $title, $annotation, $tracks;
    
    public function __construct()
    {
    }
    
    /**
     * fetches the list from the store
     */
    public function load($id)
    {
        $this->id = $id;
        return $this;
    }
    public function loadFromURL($url)
    {
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
        $this->tracks = array();
        //copy values since we shouldn't trust the structure of $trackList
        //not having lots of other fields.
        foreach($trackList as $track)
        {
            $this->tracks[] = array(
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
            'title' => $this->title,
            'annotation' => $this->annotation,
            'trackList' => $this->tracks,
        );
        return json_encode($struct);
    }
    /**
     * Saves a Spiff against 
     */
    public function save(Session $session)
    {
        global $dbconn;
        
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
        $sql = 'INSERT INTO spiff (id, json, identity_id) VALUES ($1, $2, $3)';
        $params = array($id, $this->toJSON(), $identity->id);
        $result = pg_query_params($dbconn, $sql, $params);
        if(!$result)
        {
            throw new DBQueryException("Failed saving Spiff");
        }
        $this->id = $id;
        return $this;
    }
}

?>