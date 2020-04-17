<?php
require __DIR__ . '/src/include.php';

global $clockwork;
$clockwork = \Stylemix\WPClockwork\Clockwork::init();
$clockwork->returnMetadata();
