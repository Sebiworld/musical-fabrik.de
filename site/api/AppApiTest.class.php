<?php
namespace ProcessWire;

class AppApiTest {
	public static function getCategory($data) {
		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['slug|pageName']);
		var_dump($data->slug);
		die();
	}
}
