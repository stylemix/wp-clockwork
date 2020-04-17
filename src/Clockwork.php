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
	public $buffer = '';

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
		ob_start(function ($buffer) {
			$this->buffer = $buffer;

			return '';
		});
	}

	public function lifecycleHooks()
	{
		$stages = [
			'muplugins_loaded' => 'MU plugins',
			'plugins_loaded' => 'Plugins',
			'after_setup_theme' => 'Setup theme',
			'init' => 'Init',
			'wp_loaded' => 'WP loaded',
			'admin_init' => 'Admin init',
			'wp' => 'WP query',
		];

		foreach ($stages as $hook => $name) {
			add_action($hook, function () use ($hook, $name) {
				$this->checkpoint($hook, $name);
			}, 999999);
		}

		// Render end
		add_action('shutdown', function () {
			$this->checkpoint('render', 'Render');
		}, -999999);

		// Finalize
		add_action('shutdown', function () {
			$this->checkpoint('shutdown', 'Shutdown');
			$this->collect(_stm_clock_option('database'));
			$this->requestProcessed();
			echo $this->buffer;
		}, 999999);
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

	public function collect($database = false)
	{
		// Strip out timeline records that are too small to debug
		$timeline = &$this->getClockwork()->getTimeline()->data;
		foreach ($timeline as $name => $item) {
			if ($name == 'total' || !empty($item['data']['isCheckpoint'])) {
				continue;
			}
			if ($item['duration'] < PROFILING_TIME_THRESHOLD * 1000) {
				unset($timeline[$name]);
			}
		}

		global $wpdb;
		if ($database) {
			foreach ($wpdb->queries as $query) {
				$data = [
					'file' => 'wpdb.php',
					'line' => '513',
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
