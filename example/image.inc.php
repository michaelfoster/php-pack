<?php

$image = dirname(__FILE__) . '/example.png';

function display_image() {
	global $image;
	
	header('Content-Type: image/png');
	echo file_get_contents($image);
}

