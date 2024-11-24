<?php
namespace ProcessWire;

class ProjectApi {
	public static function getProjects($data) {
		$output = ['projects' => []];

		$twack = wire('modules')->get('Twack');
		if (!$twack) {
			return $output;
		}

		foreach (wire('pages')->find('template=project') as $item) {
			if (!($item instanceof Page) || !$item->id || !$item->viewable()) {
				continue;
			}

			$project = $twack->getService('ProjectService')->getProjectAjax($item);

			if (empty($project)) {
				continue;
			}

			$output['projects'][$project['id']] = $project;
		}

		$output['hash'] = md5(json_encode($output));

		if (!empty($data->hash) && $output['hash'] === $data->hash) {
			throw new AppApiException('No new contents', 204, ['errorcode' => 'no_new_contents']);
		}

		return $output;
	}

	public static function getProjectDetail($data) {
		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);
		$page = wire('pages')->get('id=' . $data->id);

		if (!($page instanceof Page) || !$page->id) {
			throw new NotFoundException();
		} elseif (!$page->viewable()) {
			throw new ForbiddenException();
		}

		$twack = wire('modules')->get('Twack');
		if (!$twack) {
			throw new InternalServererrorException('Twack module not found');
		}

		return $twack->getService('ProjectService')->getProjectDetailAjax($page);
	}

	private static function getProjectData($projectPage) {
		if (!($projectPage instanceof Page) || !$projectPage->id) {
			return null;
		}

		$project = AppApi::getAjaxOf($projectPage);
		if ($projectPage->template->hasField('main_image') && $projectPage->main_image) {
			$project['main_image'] = AppApi::getAjaxOf($projectPage->main_image);
		}

		if ($projectPage->template->hasField('logo_square') && $projectPage->logo_square) {
			$project['logo_square'] = AppApi::getAjaxOf($projectPage->logo_square);
		}

		if ($projectPage->template->hasField('theme_vars') && $projectPage->theme_vars) {
			$project['theme_vars'] = AppApi::getAjaxOf($projectPage->theme_vars);
		}

		if ($projectPage->template->hasField('color') && $projectPage->color) {
			$project['color'] = $projectPage->color;
		}

		$project['data'] = [];

		$generalData = self::getGeneralData($projectPage);
		if (!empty($generalData)) {
			$project['data']['general'] = $generalData;
		}

		$imagesData = self::getImagesData($projectPage);
		if (!empty($imagesData)) {
			$project['data']['images'] = $imagesData;
		}


		return $project;
	}

	private static function getGeneralData($projectPage) {
		if (!($projectPage instanceof Page) || !$projectPage->id) {
			return null;
		}

		$output = [];

		if ($projectPage->template->hasField('infos') && $projectPage->infos->count > 0) {
			foreach ($projectPage->infos as $info) {
				$output[] = [
					'id' => $info->id,
					'type' => 'info',
					'name' => $info->short_html,
					'value' => $info->short_html2,
					'link' => $info->link,
					'linktitle' => $info->title,
					'depth' => $info->depth
				];
			}
		}

		// Number of participants:
		$projectRolesPage = $projectPage->get('template.name=project_roles_container');

		if ($projectRolesPage instanceof Page && $projectRolesPage->id && $projectRolesPage->viewable()) {
			// Of all roles for which no fixed number has been specified, the corresponding portraits are counted:
			$numberOfParticipants = 0;
			$portraits          = new PageArray();
			foreach ($projectRolesPage->find('template.name=project_role') as $projectRole) {
				if (!empty($projectRole->amount)) {
					$numberOfParticipants += $projectRole->amount;
					continue;
				}

				foreach ($projectRole->participants as $participant) {
					$portraits->add($participant->portraits);
				}
			}

			$numberOfParticipants += wireCount($portraits);

			if ($numberOfParticipants > 0) {
				$output[] = [
					'id' => 'participants_total',
					'type' => 'participants',
					'value' => $numberOfParticipants
				];
			}

			$projectRoles = $projectRolesPage->children('template.name=project_role');
			foreach ($projectRolesPage->children('template.name=project_role') as $projectRole) {
				$output[] = [
					'id' => $projectRole->id,
					'type' => 'roles',
					'name' => $projectRole->title,
					'link' => $projectRole->url,
					'linktitle' => $projectRole->title,
					'depth' => 1
				];
			}
		}


		return $output;
	}

	private static function getImagesData($projectPage) {
		if (!($projectPage instanceof Page) || !$projectPage->id) {
			return null;
		}

		$galleriesContainer = $projectPage->findOne('template.name=galleries_container');
		if (!($galleriesContainer instanceof Page) || !$galleriesContainer->id) {
			return null;
		}

		$output = [];


		return $output;
	}
}
