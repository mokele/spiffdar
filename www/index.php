<?php

ini_set('include_path', ini_get('include_path') . ':../lib');

require_once 'exceptions/UnauthorizedException.php';
require_once 'exceptions/DBQueryException.php';
require_once 'Spiff.php';
require_once 'SpiffList.php';
require_once 'Identity.php';
require_once 'Session.php';

require_once '../etc/private_key.php';

$port = $_SERVER['SERVER_PORT'];
$host = $_SERVER['HTTP_HOST'];
if($host=='www.spiffdar.org')
{
    header("Status: 301 Moved Permanently");
    header("Location: http://spiffdar.org" . $_SERVER['REQUEST_URI']);
    exit;
}
$site = 'http://' . $host . ($port==80?'':":$port") . '/';

//we only allow local connections to this db
$dbconn = pg_pconnect("host=localhost port=5432 dbname=mokele user=mokele password=mokele");
$session = new Session();

$spiffList = new SpiffList($session->isNew ? null : $session);

function escape($str, $nl = true)
{
    $str = htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
    return $nl
        ? addcslashes($str, "\n")
        : $str;
}

?><!DOCTYPE html 
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Spiffdar | Playlists for Playdar</title>
<script type="text/javascript" src="/static/deps/prototype.js"></script>
<script type="text/javascript" src="/static/deps/playdar.js"></script>
<script type="text/javascript" src="/static/deps/soundmanager2-nodebug-jsmin.js"></script>
<script type="text/javascript" src="/static/spiffdar.js"></script>
<link rel="stylesheet" href="/static/main.css" type="text/css" />
<script type="text/javascript">
<?php
//////////////////////////////////
// super simple for now
$_spiff = false;
if(isset($_GET['spiff']))
{

    $_spiff = new Spiff();
    $loaded = false;
    if(is_numeric($_GET['spiff']))
    {
        $_spiff->load($_GET['spiff']);
        $loaded = true;
    }
    elseif(preg_match('/^http/', $_GET['spiff']))
    {
        $_spiff->loadFromURL($_GET['spiff'], $session);
        $loaded = true;
    }
    if($loaded)
    {
        if($spiffList->add($_spiff))
        {
            $spiffList->save($session);
        }
        $title = $_spiff->title;
        $annotation = $_spiff->annotation;
        echo 'spiffdar.setTitle("'.escape($title).'");';
        echo "\n";
        echo 'spiffdar.setAnnotation("'.escape($annotation).'");';
        echo "\n";
        foreach($_spiff->trackList as $track)
        {
            echo 'spiffdar.add_track("'.escape($track['creator']).'", "'.escape($track['track']).'")';
            echo "\n";
        }
    }
}
/////////////////////////////////
?>
</script>
</head>
<body>

<h1><a href="/" class="spiffdar"><span class="spiff">Spiff</span>dar<!--där--></a></h1>
<div id="playdar_stat">Detecting Playdar</div>

<form id="add">
  <label for="artist" id="artist_label">Artist</label>
  <input type="text" id="artist" name="artist" value="" />
  <label for="track" id="track_label">Track</label>
  <input type="text" id="track" name="track" value="" />
  <input type="submit" value="Add" />
</form>

<table id="container" width="100%" height="100%">
  <tr>
    <td id="side">
      
      <ul id="lists">
        <?php foreach($spiffList->spiffs as $spiff) { ?>
            <li<?php if($_spiff && $_spiff->id==$spiff->id) { ?> class="selected"<?php } ?>><a href="<?php echo $spiff->getURL(); ?>" title="<?php echo escape($spiff->title); ?><?php if($spiff->annotation) echo ' | '; ?><?php echo escape($spiff->annotation, false); ?>"><span><?php echo escape($spiff->title); ?></span></a></li>
        <?php } ?>
        <li style="margin-top: 15px"><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/2.0/?method=playlist.fetch&raw=true&playlistURL=lastfm://playlist/2986550&api_key=b25b959554ed76058ac220b7b2e0a026'); ?>"><span>Test Playlist</span></a></li>
        <?php /*><li><a href="?spiff=<?php echo urlencode($site . 'static/the-way-I-do.xspf'); ?>">Embrace - The Way I Do</a></li>
        <li><a href="?spiff=<?php echo urlencode($site . 'static/hey-everyone.xspf'); ?>">Dananananaykroyd - Hey Everyone!</a></li>
        */ ?>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/2.0/?method=playlist.fetch&raw=true&playlistURL=lastfm://playlist/2808884&api_key=b25b959554ed76058ac220b7b2e0a026'); ?>"><span>Radiohead - June 24th 2008</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/2.0/?method=playlist.fetch&raw=true&playlistURL=lastfm://playlist/2813494&api_key=b25b959554ed76058ac220b7b2e0a026'); ?>"><span>Radiohead - June 25th 2008</span></a></li>
        
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/post-rock/toptracks.xspf'); ?>"><span>AS - Post-rock</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/pop/toptracks.xspf'); ?>"><span>AS - Pop</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/indie/toptracks.xspf'); ?>"><span>AS - Indie</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/metal/toptracks.xspf'); ?>"><span>AS - Metal</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/progressive/toptracks.xspf'); ?>"><span>AS - Progressive</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/singer-songwriter/toptracks.xspf'); ?>"><span>AS - Singer-songwriter</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/female-vocalists/toptracks.xspf'); ?>"><span>AS - Female-vocalists</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/japanese/toptracks.xspf'); ?>"><span>AS - Japanese</span></a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/detroit' . urlencode('%20') . 'techno/toptracks.xspf'); ?>"><span>AS - Detroit Techno</span></a></li>
      </ul>
    </td>
    <td id="main"><ol id="list">
        <li id="listitem_template">
          <div class="time"></div>
          <div class="track"></div>
          <div class="artist"></div>
        </li>
      </ol>
      <?php if($_spiff) { ?>
        <div id="loading">Loading Spiff...</div>
      <?php } else { ?>
      <div id="emptylist">
        <p>Enter artist and track names above to add them to a new list in this area, or enter a URL to a hosted XSPF to view it in Spiffdar.</p>
        
        <form>
          <label for="spiff_main">Enter an XSPF URL</label>
          <input type="text" name="spiff" id="spiff_main" value="http://" />
          <input type="submit" value="View" id="addspiff_main" />
        </form>
      </div><?php } ?></td>
  </tr>
</table>

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-4560275-4");
pageTracker._trackPageview();
} catch(err) {}</script>
</body>
</html>
