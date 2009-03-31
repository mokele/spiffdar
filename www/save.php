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
$spiffData = json_decode($_POST['spiff'], true);

$spiff = new Spiff();
$spiff->setTitle($spiffData['title']);
$spiff->setTrackList($spiffData['trackList']);
$spiff->save($session);
$spiffList->add($spiff);
$spiffList->save($session);
echo $spiff->getURL();

?>