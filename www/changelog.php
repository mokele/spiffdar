<!DOCTYPE html 
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link href="/changelog.xml" rel="alternate" title="Spiffdar Change Log" type="application/rss+xml" />
<?php include 'inc/head.php'; ?>
</head>
<body>
<?php include 'inc/header.php'; ?>


<?php

$xml = new SimpleXMLElement(file_get_contents('changelog.xml'));
echo '<h2>' . (string)$xml->channel->title . '</h2>';

foreach($xml->channel->item as $item)
{
    echo '<h3>' . $item->title . '</h3>';
    echo '<span class="pubDate">' . date('l dS \o\f F Y h:i A', strtotime($item->pubDate)) . '</span>';
    echo '<div class="description">' . $item->description . '</div>';
}

?>

</body>
</html>