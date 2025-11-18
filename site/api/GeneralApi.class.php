<?php
namespace ProcessWire;

class GeneralApi {
	public static function currentUser($data) {
		$output = [
			'id' => wire('user')->id,
			'name' => wire('user')->name,
			'contact_email' => wire('user')->contact_email,
			'isLoggedIn' => wire('user')->isLoggedIn(),
		  'first_name' => wire('user')->first_name,
			'last_name' => wire('user')->last_name,
			'nickname' => wire('user')->short_description,
			'birthdate' => wire('user')->birthdate,
			'description' => wire('user')->description_text,
			'roles' => [],
			'permissions' => []
		];

		foreach (wire('user')->roles as $item) {
			if ($item->template->hasField('api_visible') && !$item->api_visible) {
				continue;
			}
			$output['roles'][] = [
				'id' => $item->id,
				'name' => $item->name,
				'title' => $item->title,
				'description' => $item->short_description,
				'project_id' => !empty($item->project->id) ? $item->project->id : null
			];
		}

		foreach (wire('user')->getPermissions() as $item) {
			if ($item->template->hasField('api_visible') && !$item->api_visible) {
				continue;
			}
			$output['permissions'][] = [
				'id' => $item->id,
				'name' => $item->name,
				'title' => $item->title
			];
		}

		$output['hash'] = md5(json_encode($output));
		if (!empty($data->hash) && $output['hash'] === $data->hash) {
			throw new AppApiException('No new contents', 204, ['errorcode' => 'no_new_contents']);
		}

		return $output;
	}

	public static function errorTest($data) {
		throw new TestException('This is only an error test.');
	}
}
