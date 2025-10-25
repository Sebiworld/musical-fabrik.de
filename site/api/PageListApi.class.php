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

		if (!($page instanceof Page) || !$page->id) {
			throw new NotFoundException();
		} elseif (!$page->viewable()) {
			throw new ForbiddenException();
		}

		return $twack->getService('PagesService')->getAjax([], $page);
	}
}
