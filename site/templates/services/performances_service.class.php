<?php
namespace ProcessWire;

/**
 * Provides a single performance (a time_period page) for the public API.
 */
class PerformancesService extends TwackComponent {
	/** Assumed duration in seconds of a performance without end time. */
	const DEFAULT_DURATION = 10800;

	/** Number of candidates loaded per query of the performance search. */
	const SEARCH_CHUNK_SIZE = 20;

	/** Minutes before the beginning when the foyer opens. */
	const FOYER_ADMISSION_FIELD = 'admission_minutes';

	/** Minutes before the beginning when the hall opens. */
	const HALL_ADMISSION_FIELD = 'hall_admission_minutes';

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

		return $this->isPublicPerformance($period) ? $period : null;
	}

	/**
	 * Checks the visibility rules of getPublicPerformancePage() on a loaded page.
	 * @param Page $period
	 * @return bool
	 */
	public function isPublicPerformance($period) {
		if (!($period instanceof Page) || !$period->id || $period->template->name !== 'time_period') {
			return false;
		}

		if (!$this->isPublishedPage($period) || !$period->getUnformatted('accessable_for_guests')) {
			return false;
		}

		// time_period and event have no template file, so viewable() is checked
		// without it. This still applies page access locks and release times.
		if (!$period->viewable(false)) {
			return false;
		}

		$event = $period->parent;
		if (!($event instanceof Page) || !$event->id || $event->template->name !== 'event' || !$this->isPublishedPage($event) || !$event->viewable(false)) {
			return false;
		}

		$projectPage = $this->getService('ProjectService')->getProjectPage($period);
		if (!($projectPage instanceof Page) || !$projectPage->id) {
			return false;
		}

		return $projectPage->viewable();
	}

	/**
	 * Returns the project page for a `project` request parameter, or null if
	 * it is no project page or not viewable for the current user.
	 * @param int $id
	 * @return Page|null
	 */
	public function getViewableProjectPage($id) {
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}

		$page = wire('pages')->get('id=' . $id . ', include=all');
		if (!($page instanceof Page) || !$page->id || $page->template->name !== 'project' || !$page->viewable()) {
			return null;
		}

		return $page;
	}

	/**
	 * Builds the API output of `performances/next`.
	 * @param Page|null $projectPage restricts the search to this project, null searches all projects
	 * @param int $now unix timestamp of "now"
	 * @return array
	 */
	public function getNextPerformancesAjax($projectPage, $now) {
		$result = ['current' => null, 'next' => null];
		foreach ($this->findCurrentAndNextPerformances($projectPage, $now) as $key => $period) {
			$result[$key] = $period instanceof Page ? $this->getPerformanceSummaryAjax($period) : null;
		}

		$result['hash'] = md5(json_encode($result));

		return $result;
	}

	/**
	 * Finds the current and the next visible performance (time_period of the
	 * category "auffuehrung").
	 * - current: admission has begun (beginning minus the longer of the foyer
	 *   and the hall admission minutes) and the performance has not ended
	 *   (end, or beginning + 3 h without end). With several, the one with the
	 *   earliest beginning.
	 * - next: the earliest performance beginning after $now that is not current.
	 * @param Page|null $projectPage restricts the search to this project, null searches all projects
	 * @param int $now unix timestamp of "now"
	 * @return array{current: Page|null, next: Page|null}
	 */
	public function findCurrentAndNextPerformances($projectPage, $now) {
		$now = (int) $now;
		$result = ['current' => null, 'next' => null];

		$category = wire('pages')->get('template.name=event_category, name=auffuehrung, include=all');
		if (!($category instanceof Page) || !$category->id) {
			return $result;
		}

		// Visibility is checked per candidate afterwards, the selector only
		// narrows the candidates. Sorting by id as well keeps the result stable
		// for performances with the same beginning.
		$baseSelector = 'template.name=time_period, event_categories=' . $category->id . ', accessable_for_guests=1, include=all, status<' . Page::statusUnpublished;
		if ($projectPage instanceof Page && $projectPage->id) {
			$baseSelector .= ', has_parent=' . $projectPage->id;
		}

		// Performances that have begun and not ended yet.
		$running = $baseSelector . ', datetime_from<=' . $now
			. ', or1=(datetime_until>' . $now . '), or1=(datetime_until=\'\', datetime_from>' . ($now - self::DEFAULT_DURATION) . ')'
			. ', sort=datetime_from, sort=id';
		foreach ($this->iteratePerformances($running) as $period) {
			if ($now < $this->getEnd($period)) {
				$result['current'] = $period;
				break;
			}
		}

		// Performances that begin later. One of them is current if its
		// admission has begun, so the search goes on until no admission can
		// reach back to $now any more.
		$admissionLimit = $now + $this->getMaxAdmissionMinutes() * 60;
		foreach ($this->iteratePerformances($baseSelector . ', datetime_from>' . $now . ', sort=datetime_from, sort=id') as $period) {
			$begin = (int) $period->getUnformatted('datetime_from');
			if ($result['current'] === null && $begin - $this->getLongestAdmissionMinutes($period) * 60 <= $now) {
				$result['current'] = $period;
			} elseif ($result['next'] === null) {
				$result['next'] = $period;
			}

			if ($result['next'] !== null && ($result['current'] !== null || $begin > $admissionLimit)) {
				break;
			}
		}

		return $result;
	}

	/**
	 * Yields the visible performances of a selector, loaded in chunks.
	 * @param string $selector
	 * @return \Generator<Page>
	 */
	protected function iteratePerformances($selector) {
		$start = 0;
		do {
			$chunk = wire('pages')->find($selector . ', start=' . $start . ', limit=' . self::SEARCH_CHUNK_SIZE);
			foreach ($chunk as $period) {
				if ($this->isPublicPerformance($period)) {
					yield $period;
				}
			}
			$start += self::SEARCH_CHUNK_SIZE;
		} while ($chunk->count() === self::SEARCH_CHUNK_SIZE);
	}

	/**
	 * Returns the end of a performance: its end time, or its beginning plus
	 * the default duration without end.
	 * @param Page $period
	 * @return int
	 */
	protected function getEnd(Page $period) {
		$until = $this->getTimestamp($period, 'datetime_until');
		if ($until !== null) {
			return $until;
		}

		return (int) $period->getUnformatted('datetime_from') + self::DEFAULT_DURATION;
	}

	/**
	 * Returns the highest foyer or hall admission time of all performances,
	 * events and projects, which limits how far the search for a current
	 * performance has to look ahead.
	 * @return int
	 */
	protected function getMaxAdmissionMinutes() {
		$max = 0;
		foreach ([self::FOYER_ADMISSION_FIELD, self::HALL_ADMISSION_FIELD] as $fieldName) {
			if (!(wire('fields')->get($fieldName) instanceof Field)) {
				continue;
			}

			$page = wire('pages')->findOne('template.name=time_period|event|project, ' . $fieldName . '>0, include=all, sort=-' . $fieldName);
			if ($page instanceof Page && $page->id) {
				$max = max($max, (int) $page->getUnformatted($fieldName));
			}
		}

		return $max;
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
		$summary = $this->getPerformanceSummaryAjax($period);

		$output = [
			'id' => $summary['id'],
			'title' => $summary['title'],
			'timestamp' => $summary['timestamp'],
			'timestamp_until' => $summary['timestamp_until'],
			'admission_minutes' => $summary['admission_minutes'],
			'hall_admission_minutes' => $summary['hall_admission_minutes'],
			'ticket_url' => $summary['ticket_url'],
			'description' => $this->getHtml($period, 'description_text'),
			'visitor_info' => $this->getHtml($period, 'visitor_info') ?? $this->getHtml($event, 'visitor_info'),
			'event' => $summary['event'],
			'project' => $summary['project'],
			'seasons' => $eventsService->getPageLinksAjax($seasonPages),
			'casts' => $summary['casts'],
			'categories' => $eventsService->getPageLinksAjax($period->template->hasField('event_categories') ? $period->event_categories : []),
			'location' => $this->getLocationAjax($period),
			'roles' => $this->getRolesAjax($projectPage, $castPages, $seasonPages)
		];

		$output['hash'] = md5(json_encode($output));

		return $output;
	}

	/**
	 * Builds the short API output of a performance (as used by
	 * `performances/next`). Visibility has to be checked before.
	 * @param Page $period time_period page
	 * @return array
	 */
	public function getPerformanceSummaryAjax(Page $period) {
		$eventsService = $this->getService('EventsService');
		$event = $period->parent;
		$projectPage = $this->getService('ProjectService')->getProjectPage($period);
		$admission = $this->getAdmissionMinutes($period, $projectPage);

		return [
			'id' => $period->id,
			'title' => $period->title,
			'timestamp' => $this->getTimestamp($period, 'datetime_from'),
			'timestamp_until' => $this->getTimestamp($period, 'datetime_until'),
			'admission_minutes' => $admission[self::FOYER_ADMISSION_FIELD],
			'hall_admission_minutes' => $admission[self::HALL_ADMISSION_FIELD],
			'ticket_url' => $this->getText($period, 'link'),
			'event' => [
				'id' => $event->id,
				'title' => $event->title
			],
			'project' => [
				'id' => $projectPage->id,
				'title' => $projectPage->title,
				'url' => AppApi::getUrlRelativeToRoot($projectPage->url)
			],
			'casts' => $eventsService->getPageLinksAjax($eventsService->getPerformanceCasts($period))
		];
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
			'address' => $this->getHtml($location, 'address'),
			'lat' => $lat,
			'lng' => $lng,
			'directions' => $this->getHtml($location, 'directions'),
			'accessibility_info' => $this->getHtml($location, 'accessibility_info')
		];
	}

	/**
	 * Returns the foyer and the hall admission minutes of a performance, by
	 * field name. Each field is taken from the performance, else from its
	 * event, else from its project. An empty field falls back, 0 is a value
	 * (no admission before the beginning). A field missing on a template
	 * counts as empty. Null if the field is empty everywhere.
	 * @param Page $period time_period page
	 * @param Page|null $projectPage project of the performance, looked up if null
	 * @return array<string, int|null>
	 */
	protected function getAdmissionMinutes(Page $period, $projectPage = null) {
		if (!($projectPage instanceof Page)) {
			$projectPage = $this->getService('ProjectService')->getProjectPage($period);
		}

		$output = [];
		foreach ([self::FOYER_ADMISSION_FIELD, self::HALL_ADMISSION_FIELD] as $fieldName) {
			$output[$fieldName] = null;
			foreach ([$period, $period->parent, $projectPage] as $page) {
				if (!($page instanceof Page) || !$page->id || !$page->template->hasField($fieldName)) {
					continue;
				}

				$value = $page->getUnformatted($fieldName);
				if ($value !== '' && $value !== null) {
					$output[$fieldName] = (int) $value;
					break;
				}
			}
		}

		return $output;
	}

	/**
	 * Returns the longer of the foyer and the hall admission minutes of a
	 * performance, 0 without any admission.
	 * @param Page $period time_period page
	 * @return int
	 */
	protected function getLongestAdmissionMinutes(Page $period) {
		return (int) max($this->getAdmissionMinutes($period));
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

	/**
	 * Returns a text field as HTML with its paragraphs, or null if the field
	 * is missing on the template or has no content.
	 *
	 * The rich text fields strip their <p> tags when formatted
	 * (TextformatterPstripper), which merges paragraphs in the API output. So
	 * HTML fields are formatted with all their text formatters except that
	 * one; their markup comes from the rich text editor and is trusted. Plain
	 * text is escaped and gets paragraphs and line breaks.
	 */
	protected function getHtml(Page $page, $fieldName) {
		if (!$page->template->hasField($fieldName)) {
			return null;
		}

		$field = $page->template->fieldgroup->getField($fieldName, true);
		$value = $page->getUnformatted($fieldName);
		if (!is_string($value) || trim($value) === '') {
			return null;
		}

		if ((int) $field->get('contentType') < FieldtypeTextarea::contentTypeHTML) {
			$paragraphs = preg_split('/\R{2,}/', trim($value));

			return implode("\n", array_map(function ($paragraph) {
				return '<p>' . nl2br(wire('sanitizer')->entities(trim($paragraph)), false) . '</p>';
			}, $paragraphs));
		}

		foreach ((array) $field->get('textformatters') as $formatterName) {
			if (wire('modules')->getModuleClass($formatterName) === 'TextformatterPstripper') {
				continue;
			}
			$formatter = wire('modules')->get($formatterName);
			if (!($formatter instanceof Textformatter)) {
				continue;
			}
			$formatter->formatValue($page, $field, $value);
		}

		return trim($value) === '' ? null : $value;
	}

	protected function isPublishedPage(Page $page) {
		return !$page->isUnpublished() && !$page->isTrash();
	}
}
