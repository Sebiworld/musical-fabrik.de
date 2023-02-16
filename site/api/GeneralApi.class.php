<?php
namespace ProcessWire;

class GeneralApi {
	public static function currentUser($data) {
		$output = [
			'id' => wire('user')->id,
			'name' => wire('user')->name,
			'loggedIn' => wire('user')->isLoggedIn(),
			'nickname' => wire('user')->short_description,
			'roles' => []
		];

		foreach (wire('user')->roles as $item) {
			$output['roles'][] = [
				'id' => $item['id'],
				'name' => $item['name'],
				'title' => $item['title']
			];
		}

		return $output;
	}
}
