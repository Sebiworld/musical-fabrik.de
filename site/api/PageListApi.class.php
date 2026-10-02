<?php
namespace ProcessWire;

class PageListApi {
	public static function getPageListItems($data) {
		$twack = wire('modules')->get('Twack');
		if (!$twack) {
			throw new InternalServererrorException('Twack module not found');
		}

		if (empty($data->id)) {
			return $twack->getService('PagesService')->getAjax([]);
		}

		$data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);
		$page = wire('pages')->get('id=' . $data->id);

		// A page the user may not view gets the same answer as a missing one,
		// so the response does not reveal that it exists.
		if (!($page instanceof Page) || !$page->id || !$page->viewable()) {
			throw new NotFoundException();
		}

		return $twack->getService('PagesService')->getAjax([], $page);
	}
}
