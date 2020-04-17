<?php

namespace Stylemix\WPClockwork;

class Timeline extends \Clockwork\Request\Timeline
{

	protected $lastCheckpoint = null;

	public function startEvent($name, $description = null, $start = null, array $data = [])
	{
		$start = $start ?: microtime(true);

		if (!isset($this->data[$name])) {
			$this->data[$name] = [
				'start'       => $start,
				'end'         => null,
				'duration'    => null,
				'description' => $description ?: $name,
				'data'        => $data
			];
		}

		$this->data[$name]['last_start'] = $start;
	}

	public function endEvent($name)
	{
		if (! isset($this->data[$name])) {
			return false;
		}

		$this->data[$name]['end'] = microtime(true);

		if (is_numeric($this->data[$name]['last_start'])) {
			$this->data[$name]['duration'] = ($this->data[$name]['end'] - $this->data[$name]['last_start']) * 1000;
		}
	}

	public function checkpoint($name, $description)
	{
		$last = $this->lastCheckpoint ?: 'boot';
		$start = isset($this->data[$last]) ? $this->data[$last]['end'] : 'start';
		$this->startEvent($name, $description, $start, ['isCheckpoint' => true]);
		$this->endEvent($name);
		$this->lastCheckpoint = $name;
	}

}
