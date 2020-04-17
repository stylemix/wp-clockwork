<?php
require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', 'On');

/**
 * Cookie that will enable profiling plugins loading
 */
define('PROFILE_PLUGINS_COOKIE', 'PROFILE_PLUGINS');

/**
 * Cookie that will enable profiling themes loading
 */
define('PROFILE_THEMES_COOKIE', 'PROFILE_THEMES');

/**
 * Cookie that will enable profiling action functions
 */
define('PROFILE_ACTIONS_COOKIE', 'PROFILE_ACTIONS');

/**
 * Cookie that will enable profiling templates loading
 */
define('PROFILE_TEMPLATES_COOKIE', 'PROFILE_TEMPLATES');

/**
 * Cookie that will enable profiling shortcodes execution
 */
define('PROFILE_SHORTCODES_COOKIE', 'PROFILE_SHORTCODES');

/**
 * Cookie that will enable profiling database queries
 */
define('PROFILE_DATABASE_COOKIE', 'PROFILE_DATABASE');

/**
 * Cookie that will enable profiling HTTP requests
 */
define('PROFILE_HTTP_COOKIE', 'PROFILE_HTTP');

/**
 * Value which determines minimum events execution time to strip out
 */
define('PROFILING_TIME_THRESHOLD', .005);

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
	stm_clock()->lifecycleHooks();

	// Collect additional DB query data
	if (_stm_clock_option('database')) {
		add_filter('log_query_custom_data', function ($data) {
			$data['calling_file'] = '';
			$data['calling_line'] = '';
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			foreach ($backtrace as $item) {
				if (isset($item['file'])) {
					$file = str_replace(ABSPATH, '', $item['file']);
					$ignoredPaths = [
						'wp-admin/',
						'wp-includes/',
						'wp-settings.php',
						'wp-config.php',
						'wp-load.php',
						'wp-blog-header.php',
						'index.php',
					];
					foreach ($ignoredPaths as $path) {
						if (strpos($file, $path) === 0) {
							continue 2;
						}
					}
					$data['calling_file'] = $file;
					$data['calling_line'] = $item['line'];
					break;
				}
			}

			return $data;
		});
	}

	// Debug HTTP requests
	if (_stm_clock_option('http_requests')) {
		add_filter('pre_http_request', function ($preempt, $args, $url) {
			stm_clock()->startHttp($url);

			return $args;
		}, 10000, 3);
		add_action('http_api_debug', function ($response, $type, $class, $parsed_args, $url) {
			stm_clock()->endHttp($url);
		}, 10000, 5);
	}

	// Debug shortcodes
	if (_stm_clock_option('shortcodes')) {
		add_filter('pre_do_shortcode_tag', function ($val, $tag) {
			global $shortcode_tags;
			$event = "[shortcode] {$tag}";
			$fn = _get_fn_hook($shortcode_tags[$tag]);
			stm_clock_event($event, $event . ' > ' . $fn);

			return $val;
		}, 10000, 2);
		add_filter('do_shortcode_tag', function ($val, $tag) {
			$event = "[shortcode] {$tag}";
			stm_clock_event_end($event);

			return $val;
		}, -10000, 2);
	}
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
	if (!_stm_clock_option('plugins')) {
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
	if (!_stm_clock_option('themes')) {
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
	if (!_stm_clock_option('templates')) {
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
	if (!_stm_clock_option('actions')) {
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

function _stm_clock_option($option)
{
	switch ($option) {
	case 'plugins':
		return !empty($_COOKIE[PROFILE_PLUGINS_COOKIE]);
	case 'themes':
		return !empty($_COOKIE[PROFILE_THEMES_COOKIE]);
	case 'actions':
		return !empty($_COOKIE[PROFILE_ACTIONS_COOKIE]);
	case 'templates':
		return !empty($_COOKIE[PROFILE_TEMPLATES_COOKIE]);
	case 'shortcodes':
		return !empty($_COOKIE[PROFILE_SHORTCODES_COOKIE]);
	case 'database':
		return !empty($_COOKIE[PROFILE_DATABASE_COOKIE]);
	case 'http_requests':
		return !empty($_COOKIE[PROFILE_HTTP_COOKIE]);
	}

	return false;
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
