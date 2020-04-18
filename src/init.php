<?php
if (!defined('WP_CLOCKWORK') || !WP_CLOCKWORK) {
	return;
}

require_once __DIR__ . '/include.php';

global $stm_clockwork;
$stm_clockwork = \Stylemix\WPClockwork\Clockwork::init();
$stm_clockwork->start();
