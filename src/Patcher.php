<?php

namespace Stylemix\WPClockwork;

use DiffMatchPatch\DiffMatchPatch;

define('BASE_DIR', realpath(__DIR__ . '/../../') . '/');


class Patcher
{

	public function apply()
	{
		require_once BASE_DIR . 'wp-includes/version.php';
		/** @var $wp_version $files */

		$files = include __DIR__ . '/patch-files.php';
		$dmp = new DiffMatchPatch();

		foreach ($files as $file) {
			$backupFile = $file . '.back';
			$origCode = file_get_contents(BASE_DIR . $file);

			if (file_exists(BASE_DIR . $backupFile)) {
				echo "restoring original code from <code>$backupFile</code><br>";
				$origCode = file_get_contents(BASE_DIR . $backupFile);
			}
			else {
				$result = file_put_contents(BASE_DIR . $backupFile, $origCode) ? 'ok' : 'failed';
				echo "backing up original to <code>$backupFile</code> ...$result<br>";
				if ($result != 'ok') {
					continue;
				}
			}

			$patchFile = __DIR__ . '/patches/' . $file . '.patch';

			// check existence of patch file for WP v4.x
			if (substr($wp_version, 0, 1) === '4') {
				$patchFile4 = __DIR__ . '/patches4/' . $file . '.patch';
				$patchFile = file_exists($patchFile4) ? $patchFile4 : $patchFile;
			}
			$patches = $dmp->patch_fromText(file_get_contents($patchFile));
			$result = $dmp->patch_apply($patches, $origCode);

			if (!file_put_contents(BASE_DIR . $file, $result[0])) {
				$this->outputFailed($file, $patches);
				continue;
			}

			if (array_sum($result[1]) == count($result[1])) {
				echo "<span style='color:green'>successfully patched <code>$file</code></span><br>";
			}
			else {
				$this->outputFailed($file, $patches, $result[1]);
			}
		}
	}

	protected function outputFailed($file, array $patches, array $result = [])
	{
		echo "<span style='color:red'>failed to patch <code>$file</code>, patch it manually:</span><br>";
		foreach ($patches as $i => $p) {
			if (!isset($result[$i]) || !$result[$i]) {
				$pf = $this->formatPatchPart($p);
				echo "<pre style='background:#eee;padding:.5rem;font-size:smaller'>{$pf}</pre>";
			}
		}
	}

	protected function formatPatchPart($text)
	{
		$symbols = [
			"\n" => '',
			'%0D%0A' => "\n",
			'%0A' => "\n",
			'%09' => "\t",
			'%3C' => "<",
			'%5B' => "[",
			'%5D' => "]",
			'%7B' => "{",
			'%7D' => "}",
		];
		$lines = explode("\n", $text);
		foreach ($lines as &$line) {
			if (strpos($line, '@@') === 0) {
				$line .= "\n";
				continue;
			}
			$op = substr($line, 0, 1);
			$line = substr($line, 1);
			$line = strtr($line, $symbols);
			if ($op === '-') {
				$line = "<span style='text-decoration: line-through; background: #dbb; color: #600'>$line</span>";
			}
			if ($op === '+') {
				$line = "<span style='background:#bdb; color: #060'>$line</span>";
			}
		}

		return join('', $lines);
	}

	public function create()
	{
		$files = include __DIR__ . '/patch-files.php';
		$dmp = new DiffMatchPatch();

		echo "<pre>";
		foreach ($files as $file) {
			$newFile = str_replace('.php', '', $file) . '.new.php';
			if (!file_exists(BASE_DIR . $newFile)) {
				echo "$file ...failed, create $newFile first\n";
				continue;
			}

			$origCode = file_get_contents(BASE_DIR . $file);
			$newCode = file_get_contents(BASE_DIR . $newFile);
			$patch = $dmp->patch_make($origCode, $newCode);
			$patch = $dmp->patch_toText($patch);
			if (file_put_contents(__DIR__ . '/patches/' . $file . '.patch', $patch)) {
				echo "$file ...ok\n";
			}
		}
		echo "</pre>";
	}

	public function rollback()
	{
		$files = include __DIR__ . '/patch-files.php';
		foreach ($files as $file) {
			$backupFile = $file . '.back';
			if (!file_exists(BASE_DIR . $backupFile)) {
				continue;
			}

			$origCode = file_get_contents(BASE_DIR . $backupFile);
			$result = file_put_contents(BASE_DIR . $file, $origCode) ? 'ok' : 'failed';
			echo "restoring original file from <code>$backupFile</code> ...$result<br>";
			if ($result == 'ok') {
				unlink(BASE_DIR . $backupFile);
			}
		}
	}
}
