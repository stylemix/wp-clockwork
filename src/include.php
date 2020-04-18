<?php
require __DIR__ . '/../vendor/autoload.php';

defined('WP_CLOCKWORK_DATABASE') || define('WP_CLOCKWORK_DATABASE', true);
defined('WP_CLOCKWORK_HTTP') || define('WP_CLOCKWORK_HTTP', true);
defined('WP_CLOCKWORK_PLUGINS') || define('WP_CLOCKWORK_PLUGINS', false);
defined('WP_CLOCKWORK_THEMES') || define('WP_CLOCKWORK_THEMES', false);
defined('WP_CLOCKWORK_ACTIONS') || define('WP_CLOCKWORK_ACTIONS', false);
defined('WP_CLOCKWORK_TEMPLATES') || define('WP_CLOCKWORK_TEMPLATES', false);
defined('WP_CLOCKWORK_SHORTCODES') || define('WP_CLOCKWORK_SHORTCODES', false);

/**
 * Value which determines minimum events execution time to strip out
 */
defined('WP_CLOCKWORK_TIME_THRESHOLD') || define('WP_CLOCKWORK_TIME_THRESHOLD', 10);

/**
 * Tell WordPress to collect DB queries
 */
defined('SAVEQUERIES') || define('SAVEQUERIES', true);

/** @var \Clockwork\Support\Vanilla\Clockwork $stm_clockwork */
global $stm_clockwork;

/**
 * @return \Stylemix\WPClockwork\Clockwork
 */
function stm_clock()
{
	return $GLOBALS['stm_clockwork'];
}

function stm_clock_hook()
{
	stm_clock()->initHooks();
}

function stm_clock_event($name, $description, $start = null)
{
	stm_clock()->startEvent($name, $description, $start);
}

function stm_clock_event_end($name)
{
	stm_clock()->endEvent($name);
}

function stm_clock_plugin($plugin)
{
	if (!WP_CLOCKWORK_PLUGINS) {
		return;
	}

	$name = '[plugin_include] ' . str_replace(WP_CONTENT_DIR . '/plugins/', '', $plugin);
	stm_clock()->startEvent($name, $name);
}

function stm_clock_plugin_end($plugin)
{
	$name = '[plugin_include] ' . str_replace(WP_CONTENT_DIR . '/plugins/', '', $plugin);
	stm_clock()->endEvent($name);
}

function stm_clock_theme($theme)
{
	if (!WP_CLOCKWORK_THEMES) {
		return;
	}

	$name = '[theme_include] ' . str_replace(WP_CONTENT_DIR . '/themes/', '', $theme);
	stm_clock()->startEvent($name, $name);
}

function stm_clock_theme_end($theme)
{
	$name = '[theme_include] ' . str_replace(WP_CONTENT_DIR . '/themes/', '', $theme);
	stm_clock()->endEvent($name);
}

function stm_clock_template($file)
{
	if (!WP_CLOCKWORK_TEMPLATES) {
		return;
	}

	$name = '[template] ' . str_replace(WP_CONTENT_DIR . '/', '', $file);
	stm_clock()->startEvent($name, $name);
}

function stm_clock_template_end($file)
{
	$name = '[template] ' . str_replace(WP_CONTENT_DIR . '/', '', $file);
	stm_clock()->endEvent($name);
}

function stm_clock_action($name, $function)
{
	if (!WP_CLOCKWORK_ACTIONS) {
		return;
	}
	$name = "[action] {$name} :: " . _get_fn_hook($function);
	stm_clock()->startEvent($name, $name);
}

function stm_clock_action_end($name, $function)
{
	$name = "[action] {$name} :: " . _get_fn_hook($function);
	stm_clock()->endEvent($name);
}

function _get_fn_hook($fn)
{
	$hook = '';

	if (is_string($fn)) {
		$hook = $fn;
	}
	elseif ($fn instanceof Closure) {
		$r = new ReflectionFunction($fn);
		$hook = str_replace(ABSPATH, '', $r->getFileName()) . ':' . $r->getStartLine();
	}
	elseif (is_array($fn)) {
		$class = is_object($fn[0]) ? get_class($fn[0]) : $fn[0];
		$hook = $class . '->' . $fn[1];
	}

	return $hook;
}
