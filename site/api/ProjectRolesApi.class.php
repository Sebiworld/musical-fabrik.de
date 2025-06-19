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

		if (!($page instanceof Page) || !$page->id) {
			throw new NotFoundException();
		} elseif (!$page->viewable()) {
			throw new ForbiddenException();
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
