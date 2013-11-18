<?php

$autoloaders = array(
	dirname(__DIR__) . '/src/autoload.php',
	dirname(__DIR__) . '/vendor/autoload.php',
	__DIR__ . '/autoload.php',
);

foreach ($autoloaders as $autoloader) {
	if (file_exists($autoloader)) {
		include_once $autoloader;
	}
}
