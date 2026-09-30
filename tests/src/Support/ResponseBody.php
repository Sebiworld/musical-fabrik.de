<?php

declare(strict_types=1);
namespace Tests\Support;

/**
 * Decodes API response bodies. Output printed before the JSON (for example
 * PHP notices) is tolerated, but reported as a PHPUnit warning that names the
 * request and the length of the extra output, never its content.
 */
final class ResponseBody {
	/**
	 * @return mixed decoded JSON, or null when the body holds no JSON
	 */
	public static function decode(string $raw, string $context) {
		if ($raw === '') {
			return null;
		}

		$decoded = json_decode($raw, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			return $decoded;
		}

		$start = strpos($raw, '{');
		if ($start === false) {
			return null;
		}

		$decoded = json_decode(substr($raw, $start), true);
		if ($decoded === null) {
			return null;
		}

		if ($start > 0) {
			trigger_error(
				'Unexpected output before the JSON body (' . $start . ' bytes) for ' . $context
				. '. The body was decoded anyway.',
				E_USER_WARNING
			);
		}

		return $decoded;
	}
}
