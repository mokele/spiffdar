<?php

function escape($str)
{
  return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
}

?><!DOCTYPE html 
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Spiffdar | Playlists for Playdar</title>
<!-- include prototype from where you have it available -->
<script type="text/javascript" src="/static/deps/prototype.js"></script>
<script type="text/javascript" src="/static/deps/playdar.js"></script>
<script type="text/javascript" src="/static/deps/soundmanager2-nodebug-jsmin.js"></script>
<script type="text/javascript" src="/static/spiffdar.js"></script>
<link rel="stylesheet" href="/static/main.css" type="text/css" />
<script type="text/javascript">

Event.observe(window, 'load', function() {
  <?php ///////////////////////////
  // super simple for now
  if(isset($_GET['spiff']) && preg_match('/^http/', $_GET['spiff']))
  {
    $contents = file_get_contents($_GET['spiff']);
    $xml = new SimpleXMLElement($contents);

    foreach($xml->trackList->track as $trackNode)
    {
      $artist = (string)$trackNode->creator;
      $track = (string)$trackNode->title;
      echo 'spiffdar.add_track("'.escape($artist).'", "'.escape($track).'");';
      echo "\n";
    }
  }
  ///////////////////////////////// ?>
});
</script>
</head>
<body>

<h1 class="spiffdar"><span class="spiff">Spiff</span>dar<!--där--></h1>
<div id="playdar_stat">Detecting Playdar</div>

<form id="add">
  <label for="artist" id="artist_label">Artist</label>
  <input type="text" id="artist" name="artist" value="" />
  <label for="track" id="track_label">Track</label>
  <input type="text" id="track" name="track" value="" />
  <input type="submit" value="Add" />
</form>

<table id="container">
  <tr>
    <td id="side">
      <ul id="lists">
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/2.0/?method=playlist.fetch&raw=true&raw=true&playlistURL=lastfm://playlist/2986550&api_key=b25b959554ed76058ac220b7b2e0a026'); ?>">Playlist #1</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/2.0/?method=playlist.fetch&raw=true&raw=true&playlistURL=lastfm://playlist/2808884&api_key=b25b959554ed76058ac220b7b2e0a026'); ?>">Radiohead - June 24th 2008</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/2.0/?method=playlist.fetch&raw=true&raw=true&playlistURL=lastfm://playlist/2813494&api_key=b25b959554ed76058ac220b7b2e0a026'); ?>">Radiohead - June 25th 2008</a></li>
        
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/post-rock/toptracks.xspf'); ?>">AS - Post-rock</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/pop/toptracks.xspf'); ?>">AS - Pop</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/indie/toptracks.xspf'); ?>">AS - Indie</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/metal/toptracks.xspf'); ?>">AS - Metal</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/progressive/toptracks.xspf'); ?>">AS - Progressive</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/singer-songwriter/toptracks.xspf'); ?>">AS - Singer-songwriter</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/female-vocalists/toptracks.xspf'); ?>">AS - Female-vocalists</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/japanese/toptracks.xspf'); ?>">AS - Japanese</a></li>
        <li><a href="?spiff=<?php echo urlencode('http://ws.audioscrobbler.com/1.0/tag/detroit' . urlencode('%20') . 'techno/toptracks.xspf'); ?>">AS - Detroit Techno</a></li>
        
       
        
      </ul>
      <a href="#" onclick="$('spiffform').toggle();$('spiff').focus();return false;">add XSPF</a>
      <form id="spiffform" style="display:none;">
        <label for="spiff">Enter an XSPF URL</label>
        <input type="text" name="spiff" id="spiff" value="http://" />
        <input type="submit" value="Add" id="addspiff" />
        <a href="#" onclick="$('spiffform').toggle();this.blur();return false;">cancel</a>
      </form>
    </td>
    <td id="main">
      <table id="list">
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Artist</th>
          <th>Time</th>
        </tr>
        <tr id="listitem_template">
          <td class="position"></td>
          <td class="track"></td>
          <td class="artist"></td>
          <td class="time"></td>
        </tr>
      </table>
    </td>
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
