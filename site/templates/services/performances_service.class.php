<?php
namespace ProcessWire;

/**
 * Provides a single performance (a time_period page) for the public API.
 */
class PerformancesService extends TwackComponent {
	/**
	 * Returns the performance page if it may be shown to everyone, otherwise null.
	 * Visible means: a published time_period below a published event, released
	 * for guests, not locked by page access rules, in a project that the
	 * current user may view.
	 * @param int $id
	 * @return Page|null
	 */
	public function getPublicPerformancePage($id) {
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}

		$period = wire('pages')->get('id=' . $id . ', include=all');
		if (!($period instanceof Page) || !$period->id || $period->template->name !== 'time_period') {
			return null;
		}

		if (!$this->isPublishedPage($period) || !$period->getUnformatted('accessable_for_guests')) {
			return null;
		}

		// time_period and event have no template file, so viewable() is checked
		// without it. This still applies page access locks and release times.
		if (!$period->viewable(false)) {
			return null;
		}

		$event = $period->parent;
		if (!($event instanceof Page) || !$event->id || $event->template->name !== 'event' || !$this->isPublishedPage($event) || !$event->viewable(false)) {
			return null;
		}

		$projectPage = $this->getService('ProjectService')->getProjectPage($period);
		if (!($projectPage instanceof Page) || !$projectPage->id) {
			return null;
		}

		if (!$projectPage->viewable()) {
			return null;
		}

		return $period;
	}

	/**
	 * Builds the API output of a performance. Visibility has to be checked
	 * before (see getPublicPerformancePage()).
	 * @param Page $period time_period page
	 * @return array
	 */
	public function getPerformanceAjax(Page $period) {
		$eventsService = $this->getService('EventsService');
		$event = $period->parent;
		$projectPage = $this->getService('ProjectService')->getProjectPage($period);

		$castPages = $eventsService->getPerformanceCasts($period);
		$seasonPages = $event->template->hasField('seasons') ? $event->seasons : new PageArray();

		$output = [
			'id' => $period->id,
			'title' => $period->title,
			'timestamp' => $this->getTimestamp($period, 'datetime_from'),
			'timestamp_until' => $this->getTimestamp($period, 'datetime_until'),
			'admission_minutes' => $this->getAdmissionMinutes($period, $event),
			'ticket_url' => $this->getText($period, 'link'),
			'description' => $this->getText($period, 'description_text'),
			'visitor_info' => $this->getText($period, 'visitor_info') ?? $this->getText($event, 'visitor_info'),
			'event' => [
				'id' => $event->id,
				'title' => $event->title
			],
			'project' => [
				'id' => $projectPage->id,
				'title' => $projectPage->title,
				'url' => AppApi::getUrlRelativeToRoot($projectPage->url)
			],
			'seasons' => $eventsService->getPageLinksAjax($seasonPages),
			'casts' => $eventsService->getPageLinksAjax($castPages),
			'categories' => $eventsService->getPageLinksAjax($period->template->hasField('event_categories') ? $period->event_categories : []),
			'location' => $this->getLocationAjax($period),
			'roles' => $this->getRolesAjax($projectPage, $castPages, $seasonPages)
		];

		$output['hash'] = md5(json_encode($output));

		return $output;
	}

	protected function getRolesAjax(Page $projectPage, array $castPages, $seasonPages) {
		$output = [
			'roles' => [],
			'seasons' => [],
			'casts' => [],
			'portraits' => [],
			'child_ids' => []
		];

		$rolesService = $this->getService('ProjectRolesService');

		$castIds = array_map(function ($castPage) {
			return $castPage->id;
		}, $castPages);
		$seasonIds = $seasonPages instanceof PageArray ? $seasonPages->explode('id') : [];

		$rolesContainer = wire('pages')->get('template.name=project_roles_container, has_parent=' . $projectPage->id);
		if ($rolesContainer instanceof Page && $rolesContainer->id && $rolesContainer->viewable()) {
			$output = $rolesService->filterProjectRoles($rolesService->getProjectRoles($rolesContainer), $castIds, $seasonIds);
		}

		// The casts of the roles output are the casts playing this performance.
		// Without playing casts, the casts referenced by the roles stay.
		if (empty($castPages)) {
			return $output;
		}

		$output['casts'] = [];
		foreach ($castPages as $castPage) {
			$castOutput = $rolesService->getProjectCastAjax($castPage);
			if (!empty($castOutput)) {
				$output['casts'][$castPage->id] = $castOutput;
			}
		}

		return $output;
	}

	protected function getLocationAjax(Page $period) {
		if (!$period->template->hasField('location')) {
			return null;
		}

		$location = $period->location;
		if ($location instanceof PageArray) {
			$location = $location->first();
		}

		if (!($location instanceof Page) || !$location->id) {
			return null;
		}

		$lat = null;
		$lng = null;
		if ($location->template->hasField('karte') && is_object($location->karte)) {
			$lat = empty($location->karte->lat) ? null : (float) $location->karte->lat;
			$lng = empty($location->karte->lng) ? null : (float) $location->karte->lng;
		}

		return [
			'id' => $location->id,
			'title' => $location->title,
			'address' => $this->getText($location, 'address'),
			'lat' => $lat,
			'lng' => $lng,
			'directions' => $this->getText($location, 'directions'),
			'accessibility_info' => $this->getText($location, 'accessibility_info')
		];
	}

	protected function getAdmissionMinutes(Page $period, Page $event) {
		foreach ([$period, $event] as $page) {
			if (!$page->template->hasField('admission_minutes')) {
				continue;
			}

			$value = $page->getUnformatted('admission_minutes');
			if ($value !== '' && $value !== null) {
				return (int) $value;
			}
		}

		return null;
	}

	protected function getTimestamp(Page $page, $fieldName) {
		if (!$page->template->hasField($fieldName)) {
			return null;
		}

		$value = (int) $page->getUnformatted($fieldName);

		return $value > 0 ? $value : null;
	}

	/**
	 * Returns the formatted value of a text field, or null if the field is
	 * missing on the template or has no content.
	 */
	protected function getText(Page $page, $fieldName) {
		if (!$page->template->hasField($fieldName)) {
			return null;
		}

		$value = $page->get($fieldName);
		if (!is_string($value) || trim($value) === '') {
			return null;
		}

		return $value;
	}

	protected function isPublishedPage(Page $page) {
		return !$page->isUnpublished() && !$page->isTrash();
	}
}
