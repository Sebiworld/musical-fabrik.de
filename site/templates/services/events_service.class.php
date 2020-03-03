<?php
namespace ProcessWire;

/**
 * Provides methods for reading events.
 */
class EventsService extends TwackComponent {

	protected $projectPage = false;

	public function __construct($args) {
		parent::__construct($args);

		if ($this->startsWith($this->page->template->name, 'project')) {
			$this->projectPage = $this->page;
		} else {
			$this->projectPage = $this->page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
		}

		$this->overviewPagesService = $this->getService('OverviewPagesService');
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
	 * @return PageArray
	 */
	public function getEvents($args = array()) {
		$output = new \StdClass();
		$events = new PageArray();

		if(!is_array($args)){
			$args = [];
		}

		$eventsSelectorParts = array(
			'template.name=event'
		);
		if ($this->projectPage instanceof Page && $this->projectPage->id) {
			// If project page: Include all sub-dates of the project
			// Search all global dates:
			$projectsContainer = wire('pages')->get('template.name=projects_container');
			$eventsSelectorParts[] = "(has_parent!={$projectsContainer->id}), (has_parent={$this->projectPage->id})";
		}

		// Filtering by tags:
		if (isset($args['tags'])) {
			if (is_string($args['tags'])) {
				$args['tags'] = explode(',', $args['tags']);
			}

			if (is_array($args['tags'])) {
				$eventsSelectorParts[] = 'tags=' . implode('|', $args['tags']);
			}
		}

		// Filtering by free text:
		if (isset($args['query'])) {
			if (is_string($args['query'])) {
				$query = wire('sanitizer')->text($args['query']);
				$eventsSelectorParts[] = "title|name|intro|contents.text%={$query}";
			}
		}

		if(!empty($args['start_date'])){
			if(strtolower((string)$args['start_date']) === 'TODAY'){
				$startDatetime = time();
			}else{
				$startDatetime = wire('sanitizer')->date($args['start_date']);
			}

			if($startDatetime > 0){
				$eventsSelectorParts[] = "datetime_from>={$startDatetime}";
			}
		}

		// Criteria for subperiods:
		$timeperiodSelectorParts = array('template.name=time_period');
		if(!empty($args['guestuser']) && $args['guestuser'] || wire('user')->isGuest()){
			$timeperiodSelectorParts[] = 'accessable_for_guests=1';
		}

		if(!empty($args['categories']) && is_array($args['categories'])){
			// Categories have an AND connection:
			foreach($args['categories'] as $category){
				$timeperiodSelectorParts[] = 'event_categories=' . $category;
			}
		}

		// Only allow appointments to which the period selector applies:
		$timeperiodSelector = false;
		if (count($timeperiodSelectorParts) > 1) {
			$timeperiodSelector = implode(', ', $timeperiodSelectorParts);
			$eventsSelectorParts[] = "time_periods=[{$timeperiodSelector}]";
		}

		// Should the dates be sorted specially?
		if (isset($args['sort'])) {
			$eventsSelectorParts[] = 'sort=' . $args['sort'];
		}else if (isset($args['sort_by'])) {
			$eventsSelectorParts[] = 'sort=' . $args['sort_by'];
		} else {
			$eventsSelectorParts[] = 'sort=-datetime_from';
		}
		$eventsSelector = implode(', ', $eventsSelectorParts);

		// Store original number of articles without limit:
		$output->totalNumber = wire('pages')->count($eventsSelector);

		// The index of the last element:
		$output->lastElementIndex = 0;

		// Include Limit and Offset:
		if (isset($args['start'])) {
			$eventsSelectorParts[] = 'start=' . $args['start'];
			$output->lastElementIndex = intval($args['start']);
		} elseif (isset($args['offset'])) {
			$eventsSelectorParts[] = 'start=' . $args['offset'];
			$output->lastElementIndex = intval($args['offset']);
		} else {
			$eventsSelectorParts[] = 'start=0';
		}

		if (isset($args['limit']) && $args['limit'] >= 0) {
			$eventsSelectorParts[] = 'limit=' . $args['limit'];
			$output->lastElementIndex = $output->lastElementIndex + intval($args['limit']);
		} elseif (!isset($args['limit'])) {
			$eventsSelectorParts[] = 'limit=12';
			$output->lastElementIndex = $output->lastElementIndex + 12;
		}

		// Are there any more posts that can be downloaded?
		$output->moreAvailable = $output->lastElementIndex + 1 < $output->totalNumber;

		$eventsSelector = implode(', ', $eventsSelectorParts);
		foreach(wire('pages')->find($eventsSelector) as $event){

			// Only play the periods that match the period selector (if specified):
			if ($timeperiodSelector) {
				$event->time_periods = $event->time_periods->filter($timeperiodSelector);
			}

			$options = array();
			if(!empty($args['characterLimit'])){
				$options['limit'] = $args['characterLimit'];
			}

			$event = $this->overviewPagesService->formatPage($event, $options);

			$events->add($event);
		}

		$output->events = $events;

		return $output;
	}

	public function getAjax(){
		$ajaxOutput = array();

		$args = wire('input')->post('args');
		if (!is_array($args)) {
			$args = array();
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
		$ajaxOutput['events'] = array();
		foreach ($result->events as $event) {
			if(!$event->viewable()){
				continue;
			}

			$component = $this->addComponent('PageCard', ['directory' => '', 'page' => $event]);
			if ($component instanceof TwackNullComponent) {
				continue;
			}

			$ajaxOutput['events'][] = $component->getAjax();

			$locations->add(wire('pages')->find("template.name=location, location.owner.has_parent={$event->id}, check_access=0"));

			$categorySelector = "template.name=event_category, event_categories.owner.has_parent={$event->id}, check_access=0";
			if(wire('user')->isGuest()){
				$categorySelector .= ', fuer_gaeste_freigegeben=1';
			}
			$categories->add(wire('pages')->find($categorySelector));
		}

		$ajaxOutput['categories'] = $categories->explode(['id', 'name', 'title']);
		$ajaxOutput['locations'] = $locations->explode(['id', 'name', 'title', 'adresse', 'map.lat', 'map.lng', 'map.zoom']);

		return $ajaxOutput;
	}

	protected function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}
