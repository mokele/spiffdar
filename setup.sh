#!/bin/bash

# A quick script to load the dependancies required for Spiffdar

echo "Downloading Playday.js..."
curl -s "http://playdar.org/static/playdar.js" > www/static/playdar.js
echo "Done";
echo "";

echo "Downloading Sound Manager...";
curl -s "http://www.schillmania.com/projects/soundmanager2/download/soundmanagerv294a-20090206.zip" > soundmanager;

echo "Installing..."
unzip -q soundmanager 
mv soundmanagerv294a-20090206/swf/soundmanager2_flash9.swf www/static
mv soundmanagerv294a-20090206/script/soundmanager2-nodebug-jsmin.js www/static

rm soundmanager
rm -rf soundmanagerv294a-20090206

echo "Done";
echo "";

echo "Generateing Private Key..."
mkdir -p etc/
echo '<?php $private_key = "'`md5 -qs $RANDOM`'";' > etc/private_key.php

echo "Done";
echo "";