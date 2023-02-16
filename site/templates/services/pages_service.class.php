<?php
namespace ProcessWire;

class PagesService extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);
		$this->projectPage = $this->getService('ProjectService')->getProjectPageWithFallback();
	}

	/**
	 * Returns all pages that can be output on this page.
	 * @return PageArray
	 */
	public function getResults($args = [], $selector = []) {
		$output   = new \StdClass();
		$results  = new PageArray();

		if ($this->projectPage instanceof Page && $this->projectPage->id && $this->getService('ProjectService')->isProjectPage($this->projectPage)) {
			// If project page: Include only sub-pages of the project
			$selector[] = ['has_parent', $this->projectPage->id];
		}

		if (isset($args['sort'])) {
			$selector[] = ['sort', $args['sort']];
		} else {
			$selector[] = ['sort', '-datetime_from'];
		}

		// Filtering by keywords:
		if (isset($args['tags'])) {
			if (is_string($args['tags'])) {
				$args['tags'] = explode(',', $args['tags']);
			}

			if (is_array($args['tags'])) {
				$selector[] = ['tags', $args['tags']];
			}
		}

		// Filtering by free text:
		$results = new PageArray();
		if (isset($args['query']) && is_string($args['query'])) {
			// Sort query-matches in title and name higher than matches in other fields:
			$titleSelector = $selector;
			$titleSelector[] = ['title|name', '%=', $args['query'], 'text'];
			$results = $this->wire('pages')->find($titleSelector);

			$selector[] = ['title|name|intro|contents.text', '%=', $args['query'], 'text'];
			$secondaryResults = $this->wire('pages')->find($selector);
			$secondaryResults->removeItems($results);
			$results->add($secondaryResults);
		} else {
			$results = $this->wire('pages')->find($selector);
		}

		$pwProtectionModule = $this->wire('modules')->get('PageAccessPassword');

		// Filter pages that are not viewable by the current user:
		foreach ($results as $resultPage) {
			if (!$resultPage->viewable()) {
				$results->remove($resultPage);
				continue;
			}

			if ($pwProtectionModule && !$pwProtectionModule->isUnlocked($resultPage)) {
				$results->remove($resultPage);
				continue;
			}
		}

		// Store original number of articles without limit:
		$output->totalNumber = $results->count;

		// The index of the last element:
		$output->lastElementIndex = 0;

		$sortSelector = [];
		if (isset($args['start'])) {
			$sortSelector[]                  = ['start', '=', $args['start'], 'int'];
			$output->lastElementIndex    = intval($args['start']);
		} elseif (isset($args['offset'])) {
			$sortSelector[]                  = ['start', '=', $args['offset'], 'int'];
			$output->lastElementIndex    = intval($args['offset']);
		} else {
			$sortSelector[] = ['start', 0];
		}

		if (isset($args['limit']) && $args['limit'] >= 0) {
			$sortSelector[]                  = ['limit', '=', $args['limit'], 'int'];
			$output->lastElementIndex    = $output->lastElementIndex + intval($args['limit']);
		} elseif (!isset($args['limit'])) {
			$sortSelector[]                  = ['limit', 12];
			$output->lastElementIndex    = $output->lastElementIndex + 12;
		}

		$results = $results->find($sortSelector);

		// Are there any more posts that can be downloaded?
		$output->moreAvailable = $output->lastElementIndex + 1 < $output->totalNumber;

		// Prepare args for the overview pages service:
		if (isset($args['charLimit'])) {
			$args['limit'] = $args['charLimit'];
		} else {
			unset($args['limit']);
		}
		$results = $this->format($results, $args);

		$output->items = $results;

		return $output;
	}

	public function getAjax($ajaxArgs = []) {
		$ajaxOutput = [];

		$args = wire('input')->post('args');
		if (!is_array($args)) {
			$args = [];
		}
		if (!empty($ajaxArgs['args']) && is_array($ajaxArgs['args'])) {
			$args = array_merge($args, $ajaxArgs['args']);
		}

		// Is a tag filter set?
		if (wire('input')->get('tags')) {
			$args['tags'] = wire('input')->get('tags');
		}

		// Is something entered in the free text search?
		if (wire('input')->get('q')) {
			$args['query'] = wire('input')->get('q');
		}

		if (wire('input')->get('limit')) {
			$args['limit'] = wire('input')->get('limit');
		}

		if (wire('input')->get('start')) {
			$args['start'] = wire('input')->get('start');
		} elseif (wire('input')->get('offset')) {
			$args['start'] = wire('input')->get('offset');
		}

		$selector = [];
		if (isset($ajaxArgs['selector']) && is_array($ajaxArgs['selector'])) {
			$selector = $ajaxArgs['selector'];
		}

		if (wire('input')->get('charLimit') && is_int(wire('input')->get('charLimit'))) {
			$args['charLimit'] = wire('input')->get('charLimit');
		} elseif (wire('input')->get('charLimit') !== 'none') {
			$args['charLimit']                       = 150;
		}

		$result                                  = $this->getResults($args, $selector);

		$ajaxOutput['totalNumber']               = $result->totalNumber;
		$ajaxOutput['moreAvailable']             = $result->moreAvailable;
		$ajaxOutput['lastElementIndex']          = $result->lastElementIndex;

		// Deliver HTML card for each post:
		$ajaxOutput['items'] = [];
		foreach ($result->items as $item) {
			$component = $this->addComponent('PageCard', ['directory' => '', 'page' => $item]);
			if ($component instanceof TwackNullComponent) {
				continue;
			}

			$ajaxOutput['items'][] = $component->getAjax($ajaxArgs);
		}

		return $ajaxOutput;
	}

	public function format(PageArray $pages, $args = []) {
		foreach ($pages as &$page) {
			// Check whether the post is visible to the user:
			if (!$page->viewable()) {
				$pages->remove($page);
			}

			$projectPage = $this->getService('ProjectService')->getProjectPage($page);
			if ($projectPage instanceof Page && $projectPage->id) {
				$page->projectPage = $projectPage;

				if ($page->projectPage->color) {
					$page->color = $page->projectPage->color;
				}
			}

			if (isset($args['limit']) && is_int($args['limit']) && $page->template->hasField('intro')) {
				$limit  = $args['limit'];
				$endstr = '&nbsp;…';
				if (isset($args['endstr'])) {
					$endstr = $args['endstr'];
				}
				$page->intro = Twack::wordLimiter($page->intro, $limit, $endstr);
			}

			if ($page->template->hasField('authors') && $page->authors instanceof PageArray) {
				$authors = [];
				foreach ($page->authors as $author) {
					$authors[] = $author->first_name . ' ' . $author->last_name;
				}
				$page->authors_readable = implode(' & ', $authors);
			}
		}
		return $pages;
	}

	public function formatPage(Page $page, $args = []) {
		// Check whether the post is visible to the user:
		if (!$page->viewable()) {
			return false;
		}

		$projectPage = $this->getService('ProjectService')->getProjectPage($page);
		if ($projectPage instanceof Page && $projectPage->id) {
			$page->projectPage = $projectPage;

			if ($projectPage->color) {
				$page->color = $projectPage->color;
			}
		}

		if (isset($args['limit']) && $page->template->hasField('intro')) {
			$limit  = $args['limit'];
			$endstr = '&nbsp;…';
			if (isset($args['endstr'])) {
				$endstr = $args['endstr'];
			}
			$page->intro = Twack::wordLimiter($page->intro, $limit, $endstr);
		}

		if ($page->template->hasField('authors') && $page->authors instanceof PageArray) {
			$authors = [];
			foreach ($page->authors as $author) {
				$authors[] = $author->first_name . ' ' . $author->last_name;
			}
			$page->authors_readable = implode(' & ', $authors);
		}

		return $page;
	}
}
