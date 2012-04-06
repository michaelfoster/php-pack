<?php

$test = __FILE__;

$image = 'example.png';

function display_image() {
	global $image, $test;
	
	header('Content-Type: image/png');
	echo file_get_contents(dirname($test) . '/' . $image);
}

