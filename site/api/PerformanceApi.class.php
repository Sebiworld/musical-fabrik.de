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
}
