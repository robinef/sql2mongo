<?php

error_reporting(E_ALL | E_STRICT);

// Set the default timezone. While this doesn't cause any tests to fail, PHP
// complains if it is not set in 'date.timezone' of php.ini.
date_default_timezone_set('UTC');

// Include the composer autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if(is_file($autoloader)) {
	require_once($autoloader);
} else {
	require_once('../../autoload.php');
}