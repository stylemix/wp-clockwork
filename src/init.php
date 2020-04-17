<?php

/**
 * Cookie that will trigger debugging
 */
define('PROFILING_COOKIE', 'XDEBUG_PROFILE');

if (!isset($_COOKIE[PROFILING_COOKIE])) {
	return;
}

require_once __DIR__ . '/include.php';

global $stm_clockwork;
$stm_clockwork = \Stylemix\WPClockwork\Clockwork::init();
$stm_clockwork->start();
$stm_clockwork->startEvent('boot', 'Booting core');
