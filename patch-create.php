<?php
require __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', 'On');

(new \Stylemix\WPClockwork\Patcher())->create();
