<?php
namespace ProcessWire;

class GeneralApi {
	public static function currentUser($data) {
		error_reporting(0);
		$output = [
			'id' => wire('user')->id,
			'name' => wire('user')->name,
			'loggedIn' => wire('user')->isLoggedIn(),
			'nickname' => wire('user')->short_description,
			'roles' => [],
			'permissions' => [],
			'projects' => []
		];

		foreach (wire('user')->roles as $item) {
			if ($item->template->hasField('api_visible') && !$item->api_visible) {
				continue;
			}
			$output['roles'][] = [
				'id' => $item->id,
				'name' => $item->name,
				'title' => $item->title,
				'description' => $item->short_description
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

		foreach (wire('pages')->find('template=project') as $item) {
			if (!($item instanceof Page) || !$item->id || !$item->viewable()) {
				continue;
			}
			$project = [
				'id' => $item->id,
				'name' => $item->name,
				'title' => $item->title,
				'created' => $item->created,
				'modified' => $item->modified,
				'url' => $item->url,
				'httpUrl' => $item->httpUrl,
				'theme' => [],
				'logo' => AppApi::getAjaxOf($item->logo_square)
			];

			foreach ($item->theme_vars as $var) {
				if (empty($var->name)) {
					continue;
				}
				$project['theme'][$var->name] = $var->value;
			}

			$output['projects'][$project['id']] = $project;
		}

		return $output;
	}
}
