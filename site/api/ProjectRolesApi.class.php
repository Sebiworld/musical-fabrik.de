<?php
namespace ProcessWire;

class ProjectRolesApi {
	public static function getProjectRoles($data) {
		$twack = wire('modules')->get('Twack');
		if (!$twack) {
			throw new InternalServererrorException('Twack module not found');
		}

		if (empty($data->id)) {
			return $twack->getService('ProjectRolesService')->getProjectRoles();
		}

		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);
		$page = wire('pages')->get('id=' . $data->id);

		// A page the user may not view gets the same answer as a missing one,
		// so the response does not reveal that it exists.
		if (!($page instanceof Page) || !$page->id || !$page->viewable()) {
			throw new NotFoundException();
		}

		return $twack->getService('ProjectRolesService')->getProjectRoles($page);
	}

	public static function getProjectPortraits($data) {
		$twack = wire('modules')->get('Twack');
		if (!$twack) {
			throw new InternalServererrorException('Twack module not found');
		}

		if (!empty($data->id)) {
			throw new NotFoundException();
		}

		$ids = wire('sanitizer')->intArray($data->ids);

		return $twack->getService('ProjectRolesService')->getProjectPortraits($ids);
	}
}
