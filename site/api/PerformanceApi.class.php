<?php
namespace ProcessWire;

class PerformanceApi {
	public static function getPerformance($data) {
		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);

		$twack = wire('modules')->get('Twack');
		if (!$twack) {
			throw new InternalServererrorException('Twack module not found');
		}

		$performancesService = $twack->getService('PerformancesService');

		// Hidden, unreleased and unknown performances all answer 404, so the
		// response does not reveal whether a page exists.
		$period = $performancesService->getPublicPerformancePage($data->id);
		if (!($period instanceof Page)) {
			throw new NotFoundException('Performance not found.', 404, ['errorcode' => 'performance_not_found']);
		}

		$output = $performancesService->getPerformanceAjax($period);

		if (!empty($data->hash) && $output['hash'] === $data->hash) {
			throw new AppApiException('No new contents', 204, ['errorcode' => 'no_new_contents']);
		}

		return $output;
	}

	/**
	 * The current and the next performance, over all projects or, with the
	 * parameter `project`, of one project. An unknown or hidden project
	 * answers like a project without performances.
	 */
	public static function getNextPerformances($data) {
		$twack = wire('modules')->get('Twack');
		if (!$twack) {
			throw new InternalServererrorException('Twack module not found');
		}

		$performancesService = $twack->getService('PerformancesService');

		$projectPage = null;
		$withProject = isset($data->project);
		if ($withProject) {
			$projectPage = $performancesService->getViewableProjectPage(wire('sanitizer')->int($data->project));
		}

		if ($withProject && !($projectPage instanceof Page)) {
			$output = ['current' => null, 'next' => null];
			$output['hash'] = md5(json_encode($output));
		} else {
			$output = $performancesService->getNextPerformancesAjax($projectPage, time());
		}

		if (!empty($data->hash) && $output['hash'] === $data->hash) {
			throw new AppApiException('No new contents', 204, ['errorcode' => 'no_new_contents']);
		}

		return $output;
	}
}
