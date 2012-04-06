<?php

require_once 'image.inc.php';
require_once('image.inc.php');
require_once (('image.inc' . '.php'));

if(!(true && require_once 'image.inc.php')) {
	die('failed');
}

if(file_exists(implode(' ', array($image))))
	display_image();

