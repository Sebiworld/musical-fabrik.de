<?php

namespace ProcessWire;

class AppApiTest {
	public static function test($data) {
		return [
			'data' => $data,
			'test' => true,
			'success' => 'YEAH!',
			'responseCode' => 202
		];
	}
}
