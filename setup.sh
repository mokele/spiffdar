#!/bin/bash

# A quick script to load the dependancies required for Spiffdar

mkdir -p www/static/deps

echo "Downloading Playday.js..."
curl -s "http://www.playdar.org/static/playdar.js" > www/static/deps/playdar.js
echo "Done";
echo "";

echo "Downloading Sound Manager...";
curl -s "http://www.schillmania.com/projects/soundmanager2/download/soundmanagerv294a-20090206.zip" > soundmanager;

echo "Installing..."
unzip -q soundmanager 
mv soundmanagerv294a-20090206/swf/soundmanager2_flash9.swf www/static/deps
mv soundmanagerv294a-20090206/script/soundmanager2-nodebug-jsmin.js www/static/deps

rm soundmanager
rm -rf soundmanagerv294a-20090206

echo "Done";
echo "";

echo "Generateing Private Key..."
mkdir -p etc/
echo '<?php $private_key = "'`md5 -qs $RANDOM`'";' > etc/private_key.php

echo "Done";
echo "";