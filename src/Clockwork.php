<?php

namespace Stylemix\WPClockwork;

/**
 * Class Clockwork
 *
 * @method \Stylemix\WPClockwork\Timeline getTimeline()
 * @method endEvent($name)
 */
class Clockwork extends \Clockwork\Support\Vanilla\Clockwork
{

	public static function init($config = [])
	{
		$basePath = realpath(__DIR__ . '/../');
		$defaults = [
			'api' => basename($basePath) . '/clockwork.php?request=',
			'storage_files_path' => $basePath . '/clockwork',
		];

		/** @var \Stylemix\WPClockwork\Clockwork $clockwork */
		$clockwork = parent::init($config + $defaults);

		// Set own timeline
		$timeline = new Timeline();
		$timeline->data = $clockwork->getClockwork()->getTimeline()->data;
		$clockwork->getClockwork()->setTimeline($timeline);

		return $clockwork;
	}

	public function start()
	{
		$this->sendHeaders();
		$this->startEvent('boot', 'Booting core');
	}

	public function initHooks()
	{
		$stages = [
			['MU plugins', 'muplugins_loaded', 999999],
			['Plugins', 'plugins_loaded', -999999],
			['Plugins loaded', 'plugins_loaded', 999999],
			['Setup theme', 'after_setup_theme', 999999],
			['Init', 'init', 999999],
			['WP loaded', 'wp_loaded', 999999],
			['Admin init', 'admin_init', 999999],
			['WP query', 'wp', 999999],
			['Render', 'shutdown', -999999],
		];

		foreach ($stages as $stage) {
			list ($name, $hook, $priority) = $stage;
			add_action($hook, function () use ($name, $hook, $priority) {
				$this->checkpoint($name, $name);
			}, $priority);
		}

		// Finalize
		add_action('shutdown', function () {
			$this->checkpoint('shutdown', 'Shutdown');
			$this->collect();
			$this->requestProcessed();
		}, 999999);

		// Collect additional DB query data
		if (WP_CLOCKWORK_DATABASE) {
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
		if (WP_CLOCKWORK_HTTP) {
			add_filter('pre_http_request', function ($preempt, $args, $url) {
				stm_clock()->startHttp($url);

				return $args;
			}, 999999, 3);
			add_action('http_api_debug', function ($response, $type, $class, $parsed_args, $url) {
				stm_clock()->endHttp($url);
			}, 999999, 5);
		}

		// Debug shortcodes
		if (WP_CLOCKWORK_SHORTCODES) {
			add_filter('pre_do_shortcode_tag', function ($val, $tag) {
				global $shortcode_tags;
				$event = "[shortcode] {$tag}";
				$fn = _get_fn_hook($shortcode_tags[$tag]);
				stm_clock_event($event, $event . ' > ' . $fn);

				return $val;
			}, 999999, 2);
			add_filter('do_shortcode_tag', function ($val, $tag) {
				$event = "[shortcode] {$tag}";
				stm_clock_event_end($event);

				return $val;
			}, -999999, 2);
		}
	}

	public function startEvent($name, $description, $time = null, $data = [])
	{
		$this->getTimeline()->startEvent($name, $description, $time, $data);
	}

	public function checkpoint($name, $description)
	{
		$this->getTimeline()->checkpoint($name, $description);
	}

	public function startHttp($url, $data = [])
	{
		$id = md5($url);
		$this->getClockwork()->getTimeline()->startEvent($id, $url, null, $data);
	}

	public function endHttp($url)
	{
		$id = md5($url);
		$this->getClockwork()->getTimeline()->endEvent($id);
	}

	public function collect()
	{
		// Strip out timeline records that are too small to debug
		$timeline = &$this->getClockwork()->getTimeline()->data;
		foreach ($timeline as $name => $item) {
			if ($name == 'total' || !empty($item['data']['isCheckpoint'])) {
				continue;
			}
			if ($item['duration'] < WP_CLOCKWORK_TIME_THRESHOLD) {
				unset($timeline[$name]);
			}
		}

		global $wpdb;
		if (WP_CLOCKWORK_DATABASE) {
			foreach ($wpdb->queries as $query) {
				$data = [
					'file' => '',
					'line' => '',
				];
				if (isset($query[4])) {
					$data['file'] = $query[4]['calling_file'];
					$data['line'] = $query[4]['calling_line'];
				}
				$this->getClockwork()
					->addDatabaseQuery($query[0], [], round($query[1] * 1000, 2), $data);
			}
		}
	}
}
