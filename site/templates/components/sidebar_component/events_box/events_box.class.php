<?php

namespace ProcessWire;

class EventsBox extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);
		$days = [
			$this->_('Sunday'),
			$this->_('Monday'),
			$this->_('Tuesday'),
			$this->_('Wednesday'),
			$this->_('Thursday'),
			$this->_('Friday'),
			$this->_('Saturday')
		];

		$projectPage = $this->getGlobalParameter('projectPage');
		if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
			$projectPage = $args['projectPage'];
		}
		if (!($projectPage instanceof Page) || !$projectPage->id) {
			throw new ComponentNotInitializedException('EventsBox', 'No project page found.');
		}

		if (!isset($args['useField']) || !is_string($args['useField']) || empty($args['useField'])) {
			$args['useField'] = 'datetime_from';
		}
		if (!wire('fields')->get($args['useField'])) {
			throw new ComponentNotInitializedException('EventsBox', 'There is no field with the name "%1$s"!', $args['useField']);
		}

		$performanceCategory = wire('pages')->get('template.name=event_category, name=auffuehrung, include=all');
		if (!($performanceCategory->id . '')) {
			throw new ComponentNotInitializedException('EventsBox', 'No performance category page found.');
		}

		$eventsService = $this->getService('EventsService');
		$eventsResult = $eventsService->getEvents([
			'guestuser' => true,
			'categories' => [$performanceCategory->id]
		]);

		$this->title = $this->_('Performances');
		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(["\n", "\r"], '', $args['title']);
		}

		$performances = [];
		$performancesOld = [];
		foreach ($eventsResult->events as $event) {
			foreach ($event->time_periods->sort('-datetime_from') as $period) {
				if (!$period->template->hasField($args['useField']) || $period->getUnformatted($args['useField']) == 0) {
					continue;
				}

				$tmp = new \StdClass();
				$tmp->timestamp = $period->getUnformatted($args['useField']);
				$tmp->date = date('d.m.Y', $period->getUnformatted($args['useField']));
				$tmp->time = date('H:i', $period->getUnformatted($args['useField']));
				$tmp->weekday = $days[date('w', $period->getUnformatted($args['useField']))];
				$tmp->seasons = $event->seasons;
				$tmp->cast = '';
				$tmp->categories = $period->event_categories;

				if ($period->template->hasField('cast') && $period->cast instanceof Page && $period->cast->id) {
					$tmp->cast .= $period->cast->title;
				}

				if ($period->getUnformatted($args['useField']) < time()) {
					// The event is over
					array_unshift($performancesOld, $tmp);
				} else {
					// The event is in the future
					$performances[] = $tmp;
				}
			}
		}

		if (count($performances) > 1) {
			usort($performances, function ($a, $b) {
				if ($a->timestamp === $b->timestamp) {
					return 0;
				}
				return $a->timestamp > $b->timestamp ? -1 : 1;
			});
		}

		if (count($performancesOld) > 1) {
			usort($performancesOld, function ($a, $b) {
				if ($a->timestamp === $b->timestamp) {
					return 0;
				}
				return $a->timestamp < $b->timestamp ? -1 : 1;
			});
		}

		$this->performances = $performances;
		$this->performancesOld = $performancesOld;

		if ($projectPage->template->hasField('page_reference') && $projectPage->page_reference->id) {
			$this->ticketPage = $projectPage->page_reference;
		}

		// if (($this->performances && count($this->performances) > 0) || ($this->performancesOld && count($this->performancesOld) > 0)) {
			// $this->addScript('performances-box.js', array(
			// 	'path'     => wire('config')->urls->templates . 'assets/js/',
			// 	'absolute' => true
			// ));
			// $this->addScript('legacy/performances-box.js', array(
			// 	'path'     => wire('config')->urls->templates . 'assets/js/',
			// 	'absolute' => true
			// ));
		// }
	}
}
