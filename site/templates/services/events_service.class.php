<?php
namespace ProcessWire;

/**
 * Provides methods for reading events.
 */
class EventsService extends TwackComponent {
	/** Sort values of the event list. Other values fall back to DEFAULT_SORT. */
	const SORT_VALUES = ['-datetime_from', 'datetime_from'];

	const DEFAULT_SORT = '-datetime_from';

	/** Number of events per request without a limit. */
	const DEFAULT_LIMIT = 12;

	/** Upper bound of the limit, so that a request cannot load every event at once. */
	const MAX_LIMIT = 100;

	protected $projectPage = false;

	/**
	 * Results of visibility checks within this request, by check, user and
	 * page. Cleared whenever a page is saved or deleted.
	 * @var array<string, bool>
	 */
	protected $visibilityCache = [];

	public function __construct($args) {
		parent::__construct($args);

		if ($this->startsWith($this->page->template->name, 'project')) {
			$this->projectPage = $this->page;
		} else {
			$this->projectPage = $this->page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
		}

		$this->overviewPagesService = $this->getService('OverviewPagesService');

		$this->wire('pages')->addHookAfter('savedPageOrField', $this, 'clearVisibilityCache');
		$this->wire('pages')->addHookAfter('deleted', $this, 'clearVisibilityCache');
	}

	public function getEventsContainer() {
		$eventsPage = wire('pages')->get('/')->children('template.name=events_container')->first();
		if ($this->projectPage instanceof Page && $this->projectPage->id) {
			$results = $this->projectPage->find('template.name=events_container');
			if ($results->count > 0) {
				$eventsPage = $results->first();
			}
		}
		return $eventsPage;
	}

	/**
	 * Returns all appointments whose periods match a selector.
	 * The selector is built as an array, so values from the request cannot
	 * add selector parts of their own.
	 * @return \StdClass totalNumber, lastElementIndex, moreAvailable, events (PageArray)
	 */
	public function getEvents($args = [], $basePage = false) {
		$output = new \StdClass();
		$events = new PageArray();

		if (!is_array($args)) {
			$args = [];
		}

		$selector = [
			['template.name', 'event']
		];

		$projectPage = $this->projectPage;
		if ($basePage instanceof Page && $basePage->id) {
			$projectPage = $this->getService('ProjectService')->getProjectPage($basePage);
		}

		if ($projectPage instanceof Page && $projectPage->id) {
			// If project page: Include all sub-dates of the project

			if ($args['includeGlobalDates'] ?? false) {
				// Also include global dates (outside of all projects), as an OR-group:
				$orGroup = ['has_parent' => $projectPage->id];
				$projectContainerIds = wire('pages')->find('template.name=projects_container')->explode('id');
				if (!empty($projectContainerIds)) {
					$orGroup['has_parent!='] = $projectContainerIds;
				}
				$selector[] = $orGroup;
			} else {
				$selector[] = ['has_parent', $projectPage->id];
			}
		}

		// Filtering by tags:
		if (isset($args['tags'])) {
			$tags = is_string($args['tags']) ? explode(',', $args['tags']) : $args['tags'];
			if (is_array($tags)) {
				$tags = array_values(array_filter(array_map(function ($tag) {
					return is_scalar($tag) ? trim((string) $tag) : '';
				}, $tags), 'strlen'));
				if (!empty($tags)) {
					$selector[] = ['tags', $tags];
				}
			}
		}

		// Filtering by free text:
		if (isset($args['query']) && is_string($args['query']) && $args['query'] !== '') {
			$selector[] = ['title|name|intro|contents.text', '%=', $args['query'], 'text'];
		}

		if (!empty($args['start_date']) && is_scalar($args['start_date'])) {
			if (strtolower((string) $args['start_date']) === 'today') {
				$startDatetime = time();
			} else {
				$startDatetime = (int) wire('sanitizer')->date($args['start_date']);
			}

			if ($startDatetime > 0) {
				$selector[] = ['datetime_from', '>=', $startDatetime];
			}
		}

		// Criteria for subperiods. "guestuser" asks for the periods a guest
		// may see, also when a logged-in user makes the request:
		$asGuest = !empty($args['guestuser']) || wire('user')->isGuest();
		$periodSelector = [
			['template.name', 'time_period']
		];
		if ($asGuest) {
			$periodSelector[] = ['accessable_for_guests', 1];
		}

		$categoryIds = [];
		if (!empty($args['categories']) && is_array($args['categories'])) {
			// Categories have an AND connection:
			foreach ($args['categories'] as $category) {
				$categoryId = is_scalar($category) ? (int) $category : 0;
				if ($categoryId > 0) {
					$categoryIds[] = $categoryId;
					$periodSelector[] = ['event_categories', $categoryId];
				}
			}
		}

		// Only allow appointments to which the period selector applies:
		$filterByPeriods = count($periodSelector) > 1;
		if ($filterByPeriods) {
			$selector[] = ['field' => 'time_periods', 'find' => $periodSelector];
		}

		// Should the dates be sorted specially? Only known sort values are used.
		$sort = $args['sort'] ?? ($args['sort_by'] ?? self::DEFAULT_SORT);
		if (!is_string($sort) || !in_array($sort, self::SORT_VALUES, true)) {
			$sort = self::DEFAULT_SORT;
		}
		$selector[] = ['sort', $sort];

		// The events the current user may see. The number is taken from these,
		// so that it does not reveal events the user may not see:
		$visibleEvents = new PageArray();
		$candidates = wire('pages')->find($selector);
		$this->preloadPeriods($candidates);
		foreach ($candidates as $event) {
			if (!$this->isVisibleEvent($event)) {
				continue;
			}

			// With a period filter, an event is listed for its periods only. For
			// the count one listable period is enough; all of them are read below
			// for the events that are delivered:
			if ($filterByPeriods && !$this->hasListablePeriod($event, $asGuest, $categoryIds)) {
				continue;
			}

			$visibleEvents->add($event);
		}
		$output->totalNumber = $visibleEvents->count();

		// Include Limit and Offset:
		$start = 0;
		if (isset($args['start']) && is_scalar($args['start'])) {
			$start = max(0, (int) $args['start']);
		} elseif (isset($args['offset']) && is_scalar($args['offset'])) {
			$start = max(0, (int) $args['offset']);
		}

		$limit = self::DEFAULT_LIMIT;
		if (isset($args['limit']) && is_scalar($args['limit']) && (int) $args['limit'] > 0) {
			$limit = min((int) $args['limit'], self::MAX_LIMIT);
		}

		// The index of the last element:
		$output->lastElementIndex = $start + $limit;

		// Are there any more posts that can be downloaded?
		$output->moreAvailable = $output->lastElementIndex + 1 < $output->totalNumber;

		foreach ($visibleEvents->slice($start, $limit) as $event) {
			// Only play the periods that may be listed and match the categories:
			$event->time_periods = $this->getListablePeriods($event, $asGuest, $categoryIds);

			$options = [];
			if (!empty($args['characterLimit'])) {
				$options['limit'] = (int) $args['characterLimit'];
			}

			$event = $this->overviewPagesService->formatPage($event, $options);

			$events->add($event);
		}

		$output->events = $events;

		return $output;
	}

	/**
	 * May the current user see the event in the event list? Events locked by
	 * a password are left out for everybody, also for users with access.
	 * @param Page $event
	 * @return bool
	 */
	public function isVisibleEvent(Page $event) {
		if (!$event->id || $event->template->name !== 'event' || $event->isTrash()) {
			return false;
		}

		return $this->cachedVisibility('event', $event, function () use ($event) {
			if (!$event->viewable()) {
				return false;
			}

			$passwordModule = wire('modules')->get('PageAccessPassword');

			return !($passwordModule && $passwordModule->isLocked($event));
		});
	}

	/**
	 * The periods in the field time_periods of an event that may be listed
	 * (see isListablePeriod()) and have all of the given categories.
	 * @param Page $event
	 * @param bool $asGuest
	 * @param int[] $categoryIds
	 * @return PageArray
	 */
	public function getListablePeriods(Page $event, $asGuest, $categoryIds = []) {
		$periods = new PageArray();
		foreach ($event->time_periods as $period) {
			if ($this->isListablePeriodWithCategories($period, $asGuest, $categoryIds)) {
				$periods->add($period);
			}
		}

		return $periods;
	}

	/**
	 * Loads the periods below the given events with one query, so that
	 * reading the field time_periods of each event takes them from the page
	 * cache instead of one query per event. Only loads, decides nothing.
	 * @param PageArray $events
	 */
	protected function preloadPeriods(PageArray $events) {
		if (!$events->count()) {
			return;
		}

		wire('pages')->find([
			['template.name', 'time_period'],
			['parent_id', $events->explode('id')],
			['include', 'hidden']
		]);
	}

	/**
	 * Has the event at least one period of getListablePeriods()? Stops at the
	 * first one.
	 * @param Page $event
	 * @param bool $asGuest
	 * @param int[] $categoryIds
	 * @return bool
	 */
	public function hasListablePeriod(Page $event, $asGuest, $categoryIds = []) {
		foreach ($event->time_periods as $period) {
			if ($this->isListablePeriodWithCategories($period, $asGuest, $categoryIds)) {
				return true;
			}
		}

		return false;
	}

	protected function isListablePeriodWithCategories($period, $asGuest, $categoryIds) {
		if (!$this->isListablePeriod($period, $asGuest)) {
			return false;
		}

		$periodCategoryIds = $period->getUnformatted('event_categories')->explode('id');

		return empty(array_diff($categoryIds, $periodCategoryIds));
	}

	/**
	 * May the time period be listed (event list, event cards, performances
	 * of a project)? Hidden periods are never listed; they stay reachable by
	 * their own route. In the guest view the rule of isGuestVisiblePeriod()
	 * applies, otherwise the current user has to be allowed to view the
	 * period.
	 * @param Page $period
	 * @param bool $asGuest true for the guest view, also for a logged-in user
	 * @return bool
	 */
	public function isListablePeriod($period, $asGuest) {
		if (!($period instanceof Page) || !$period->id || $period->template->name !== 'time_period') {
			return false;
		}

		if ($period->isHidden() || $period->isTrash()) {
			return false;
		}

		if ($asGuest) {
			return $this->isGuestVisiblePeriod($period);
		}

		// time_period has no template file, so viewable() is checked without it.
		return $this->cachedVisibility('period', $period, function () use ($period) {
			return $period->viewable(false);
		});
	}

	/**
	 * May a guest see the time period? It has to be released for guests and
	 * viewable as guest (release times and permission locks of the period
	 * and its parents), and neither the period nor one of its parents may be
	 * unpublished or locked by a password. Checked as guest, also when a
	 * logged-in user makes the request.
	 * @param Page $period
	 * @return bool
	 */
	public function isGuestVisiblePeriod(Page $period) {
		if (!$period->id || $period->template->name !== 'time_period' || $period->isTrash() || !$period->getUnformatted('accessable_for_guests')) {
			return false;
		}

		$passwordModule = wire('modules')->get('PageAccessPassword');

		return $this->cachedVisibility('guest-period', $period, fn () => $this->getService('PublicVisibilityService')->asGuest(function () use ($period, $passwordModule) {
			if (!$period->viewable(false)) {
				return false;
			}

			foreach ($period->parents()->add($period) as $item) {
				if ($item->isUnpublished() || ($passwordModule && $passwordModule->isLocked($item))) {
					return false;
				}
			}

			return true;
		}));
	}

	/**
	 * Empties the cache of visibility checks (hooked to page saves and deletes).
	 */
	public function clearVisibilityCache($event = null) {
		$this->visibilityCache = [];
	}

	/**
	 * Runs a visibility check once per request for the same check, user and page.
	 * @param string $check name of the check
	 * @param Page $page
	 * @param callable $callback returns the result
	 * @return bool
	 */
	protected function cachedVisibility($check, Page $page, callable $callback) {
		$key = $check . ':' . wire('user')->id . ':' . $page->id;
		if (!array_key_exists($key, $this->visibilityCache)) {
			$this->visibilityCache[$key] = (bool) $callback();
		}

		return $this->visibilityCache[$key];
	}

	public function getAjax($ajaxArgs = []) {
		$ajaxOutput = [];

		$args = wire('input')->post('args');
		if (!is_array($args)) {
			$args = [];
		}

		// Is a tag filter set?
		if (wire('input')->get('tags')) {
			$args['tags'] = wire('input')->get('tags');
		}

		// Is something entered in the free text search?
		if (wire('input')->get->text('q')) {
			$args['query'] = wire('input')->get->text('q');
		}

		if (wire('input')->get->int('limit')) {
			$args['limit'] = wire('input')->get->int('limit');
		}

		if (wire('input')->get->int('start')) {
			$args['start'] = wire('input')->get->int('start');
		} elseif (wire('input')->get->int('offset')) {
			$args['start'] = wire('input')->get->int('offset');
		}

		if (wire('input')->get->text('start_date')) {
			$args['start_date'] = wire('input')->get->text('start_date');
		}

		if (wire('input')->get->text('sort')) {
			$args['sort'] = wire('input')->get->text('sort');
		}

		$args['characterLimit'] = 150;
		$result = $this->getEvents($args);

		$ajaxOutput['totalNumber'] = $result->totalNumber;
		$ajaxOutput['moreAvailable'] = $result->moreAvailable;
		$ajaxOutput['lastElementIndex'] = $result->lastElementIndex;

		// Collects all categories used so that they do not have to be specified each time with all information:
		$categories = new PageArray();

		// Collects all venues used so that they do not have to be specified each time with all information:
		$locations = new PageArray();

		// Deliver HTML card for each post:
		$ajaxOutput['events'] = [];
		foreach ($result->events as $event) {
			if (!$this->isVisibleEvent($event)) {
				continue;
			}

			$component = $this->addComponent('PageCard', ['directory' => '', 'page' => $event]);
			if ($component instanceof TwackNullComponent) {
				continue;
			}

			$ajaxOutput['events'][] = $component->getAjax($ajaxArgs);

			$locations->add(wire('pages')->find("template.name=location, location.owner.has_parent={$event->id}, check_access=0"));

			if (wire('user')->isGuest()) {
				$categories->add($this->getGuestCategoriesOfEvent($event));
			} else {
				$categories->add(wire('pages')->find("template.name=event_category, event_categories.owner.has_parent={$event->id}, check_access=0"));
			}
		}

		$ajaxOutput['categories'] = $categories->explode(['id', 'name', 'title']);
		$ajaxOutput['locations'] = $locations->explode(['id', 'name', 'title', 'adresse', 'map.lat', 'map.lng', 'map.zoom']);

		return $ajaxOutput;
	}

	/**
	 * Categories of an event that a guest may see: only those of the time
	 * periods in its field time_periods that may be listed for guests
	 * (isListablePeriod()).
	 * @return PageArray
	 */
	protected function getGuestCategoriesOfEvent(Page $event) {
		$categoryIds = [];
		foreach ($event->time_periods as $period) {
			if (!$this->isListablePeriod($period, true)) {
				continue;
			}

			foreach ($period->getUnformatted('event_categories') as $category) {
				$categoryIds[] = (int) $category->id;
			}
		}

		if (empty($categoryIds)) {
			return new PageArray();
		}

		return wire('pages')->find('template.name=event_category, id=' . implode('|', array_unique($categoryIds)) . ', check_access=0');
	}

	/**
	 * Returns the casts playing a time period: the multi-page field "casts",
	 * or the legacy single-page field "cast" if "casts" is empty.
	 * @return Page[]
	 */
	public function getPerformanceCasts($period) {
		$casts = [];
		if (!($period instanceof Page) || !$period->id) {
			return $casts;
		}

		if ($period->template->hasField('casts') && $period->casts->count) {
			foreach ($period->casts as $castPage) {
				if (!($castPage instanceof Page) || !$castPage->id) {
					continue;
				}

				$casts[] = $castPage;
			}
		} elseif ($period->template->hasField('cast') && $period->cast instanceof Page && $period->cast->id) {
			$casts[] = $period->cast;
		}

		return $casts;
	}

	/**
	 * Converts pages to the short link form used for performances.
	 * @return array list of ['id', 'title', 'url']
	 */
	public function getPageLinksAjax($pages) {
		if ($pages instanceof PageArray) {
			$pages = $pages->getArray();
		}

		if (!is_array($pages)) {
			return [];
		}

		return array_map(function ($item) {
			return [
				'id' => $item->id,
				'title' => $item->title,
				'url' => AppApi::getUrlRelativeToRoot($item->url)
			];
		}, array_values($pages));
	}

	protected function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}
