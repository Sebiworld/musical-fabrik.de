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
			$projectPage = $this->getService('ProjectService')->getProjectPage($this->page);
		}

		if (!($projectPage instanceof Page) || !$projectPage->id) {
			throw new ComponentNotInitializedException('GeneralDataBox', $this->_('No project page was found.'));
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

		$eventsService       = $this->getService('EventsService');
		$eventsResult        = $eventsService->getEvents([
			'guestuser'  => true,
			'categories' => [$performanceCategory->id]
		], $projectPage);

		$this->title = $this->_('Performances');
		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(["\n", "\r"], '', $args['title']);
		}

		$allPerformances    = [];
		$performances    = [];
		$performancesOld = [];
		foreach ($eventsResult->events as $event) {
			foreach ($event->time_periods->sort('-datetime_from') as $period) {
				if (!$period->template->hasField($args['useField']) || $period->getUnformatted($args['useField']) == 0) {
					continue;
				}

				$tmp             = new \StdClass();
				$tmp->id = $period->id;
				$tmp->title = $period->title;
				$tmp->location_id = !empty($period->location->id) ? $period->location->id : null;
				$tmp->timestamp  = $period->getUnformatted($args['useField']);
				$tmp->timestamp_until  = $period->template->hasField('datetime_until') ? $period->getUnformatted('datetime_until') : '';
				$tmp->date       = date('d.m.Y', $period->getUnformatted($args['useField']));
				$tmp->time       = date('H:i', $period->getUnformatted($args['useField']));
				$tmp->weekday    = $days[date('w', $period->getUnformatted($args['useField']))];
				$tmp->seasons    = $event->seasons;
				$tmp->cast       = '';
				$tmp->casts_obj = [];
				$tmp->categories = $period->event_categories;
				$tmp->ticket_url = $period->template->hasField('link') ? $period->link : '';

				if($period->template->hasField('casts') && $period->casts->count) {
					foreach ($period->casts as $castPage) {
						if(!($castPage instanceof Page) || !$castPage->id) {
							continue;
						}

						if(!empty($tmp->cast)) {
							$tmp->cast .= ', ';
						}

						$tmp->cast .= $castPage->title;
						$tmp->casts_obj[] = $castPage;
					}
				} else if ($period->template->hasField('cast') && $period->cast instanceof Page && $period->cast->id) {
					$tmp->cast .= $period->cast->title;
					$tmp->casts_obj[] = $period->cast;
				}

				$allPerformances[] = $tmp;
				if ($period->getUnformatted($args['useField']) < time()) {
					// The event is over
					array_unshift($performancesOld, $tmp);
				} else {
					// The event is in the future
					$performances[] = $tmp;
				}
			}
		}

		if (count($allPerformances) > 1) {
			usort($allPerformances, function ($a, $b) {
				if ($a->timestamp === $b->timestamp) {
					return 0;
				}
				if ($a->timestamp > $b->timestamp) {
					return 1;
				}

				return -1;
			});
		}

		if (count($performances) > 1) {
			usort($performances, function ($a, $b) {
				if ($a->timestamp === $b->timestamp) {
					return 0;
				}
				if ($a->timestamp > $b->timestamp) {
					return 1;
				}

				return -1;
			});
		}

		if (count($performancesOld) > 1) {
			usort($performancesOld, function ($a, $b) {
				if ($a->timestamp === $b->timestamp) {
					return 0;
				}
				if ($a->timestamp > $b->timestamp) {
					return -1;
				}

				return 1;
			});
		}

		$this->allPerformances = $allPerformances;
		$this->performances    = $performances;
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

	public function getAjax($ajaxArgs = []) {
		$output = [
			'performances' => [],
			'performances_count' => count($this->allPerformances),
			'ticket_page' => AppApi::getAjaxOf($this->ticketPage)
		];

		foreach ($this->allPerformances as $performance) {
			if (!$performance->id) {
				continue;
			}

			$output['performances'][]=[
				'id' => $performance->id,
				'title' => $performance->title,
				'location_id' => $performance->location_id,
				'timestamp'  => $performance->timestamp,
				'timestamp_until'  => $performance->timestamp_until,
				'seasons'    => array_map(function ($item) {
					return [
						'id' => $item->id,
						'title' => $item->title,
						'url' => AppApi::getUrlRelativeToRoot($item->url)
					];
				}, $performance->seasons->getArray()),
				'casts'       => array_map(function ($item) {
					return [
						'id' => $item->id,
						'title' => $item->title,
						'url' => AppApi::getUrlRelativeToRoot($item->url)
					];
				}, $performance->casts_obj),
				'categories' => array_map(function ($item) {
					return [
						'id' => $item->id,
						'title' => $item->title,
						'url' => AppApi::getUrlRelativeToRoot($item->url)
					];
				}, $performance->categories->getArray()),
				'ticket_url' => $performance->ticket_url,
			];
		}

		return $output;
	}
}
