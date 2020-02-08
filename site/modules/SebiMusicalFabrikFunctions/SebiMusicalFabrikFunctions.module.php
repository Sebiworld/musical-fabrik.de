<?php
namespace ProcessWire;

class SebiMusicalFabrikFunctions extends WireData implements Module {

	public static function getModuleInfo() {
		return array(
			'title' => 'MusicalFabrik-Functions',
			'version' => '0.4.2',
			'summary' => 'Hooks and basic functions for the MusicalFabrik page',
			'singular' => true,
			'autoload' => true,
			'icon' => 'anchor',
			'requires' => array('PHP>=5.5.0', 'ProcessWire>=3.0.0'),
		);
	}

	public function init() {
		// Automatically adds subpages:
		$this->pages->addHookAfter('added', $this, 'hookPagesAfterAdded');

		// Add fields before saving:
		$this->pages->addHookAfter('saveReady', $this, 'hookPageSaveReady');

		// Synchronize keyword with project:
		$this->pages->addHookAfter('save', $this, 'hookPagesAfterSave');

		// Reload Backend JS:
		$this->addHookBefore('Page::render', $this, 'hookPageRender');

		$this->addHook('LazyCron::every30Seconds', $this, 'cleanup');
	}

	public function cleanup(HookEvent $event){
		$containerpages = wire('pages')->find('template.name=forms_container, cleanup=1');
		foreach($containerpages as $containerpage){
			$pagesToDelete = $containerpage->find('created<='.strtotime('-2 weeks'));
			foreach($pagesToDelete as $deletablePage){
				$deletablePage->delete();
			}
		}
	}

	public function hookPagesAfterAdded(HookEvent $event) {
		$page = $event->arguments[0];
		if (!$page instanceof Page || !$page->id) {
			return;
		}

		if ($this->startsWith($page->template->name, 'project') && $page->template->name !== 'project_role' && $page->template->name !== 'project_roles_container' && $page->template->name !== 'projects_container') {
			if ($page->numChildren > 0) {
				// The site has already duplicated child sites (maybe other sites?)
				return;
			}

			// Create tickets & info page:
			$page = $this->wire(new Page());
			$page->template = 'default_page';
			$page->parent = $page;
			$page->name = 'tickets-und-infos';
			$page->title = 'Tickets & Infos';
			$page->published = true;
			$page->save();

			// Create current overview:
			$page = $this->wire(new Page());
			$page->template = 'articles_container';
			$page->parent = $page;
			$page->name = 'aktuelles';
			$page->title = 'Aktuelles';
			$page->published = true;
			$page->save();

			// Create Portraits Container:
			$page = $this->wire(new Page());
			$page->template = 'portraits_container';
			$page->parent = $page;
			$page->name = 'mitwirkenden_portraits';
			$page->title = 'Mitwirkenden-Portraits';
			$page->published = true;
			$page->save();

			// Create Role Container:
			$page = $this->wire(new Page());
			$page->template = 'project_roles_container';
			$page->parent = $page;
			$page->name = 'rollen';
			$page->title = 'Rollen';
			$page->published = true;
			$page->save();

			// Create Staff Assignments Container:
			$page = $this->wire(new Page());
			$page->template = 'casts_container';
			$page->parent = $page;
			$page->name = 'besetzungen';
			$page->title = 'Besetzungen';
			$page->published = true;
			$page->save();

			// Create Scale Container:
			$page = $this->wire(new Page());
			$page->template = 'seasons_container';
			$page->parent = $page;
			$page->name = 'staffeln';
			$page->title = 'Staffeln';
			$page->published = true;
			$page->save();

			// Create first scale automatically:
			$seasonsContainer = $page->children('template.name=seasons_container')->first;

			if($seasonsContainer instanceof Page && $seasonsContainer->id){
				$page = $this->wire(new Page());
				$page->template = 'season';
				$page->parent = $seasonsContainer;
				$page->name = 'spielzeit-1';
				$page->title = 'Spielzeit 1';
				$page->published = true;
				$page->save();
			}

		} elseif ($page->template->name == 'time_periods') {
			$parentPages = wire('pages')->find('time_periods='.$page->id);
			foreach ($parentPages as $parentPage) {
				$datetimeFrom = false;
				if (!$parentPage->template->hasField('time_periods') || $page->time_period->count <= 0) {
					$datetimeFrom = '';
				} else {
					$start = $page->time_periods->filter('sort=datetime_from')->first();
					$datetimeFrom = $start->getUnformatted('datetime_from');
				}

				if ($parentPage->getUnformatted('datetime_from') != $datetimeFrom) {
					$parentPage->of(false);
					$parentPage->save();
				}
			}
		}
	}

	public function hookPageSaveReady(HookEvent $event) {
		$page = $event->arguments[0];

		// Fill in values before saving the page:
		if ($page->template->name == 'event' && $page->template->hasField('time_periods')) {
			if ($page->time_periods->count > 0) {
				$start = $page->time_periods->filter('sort=datetime_from')->first();
				$page->datetime_from = $start->getUnformatted('datetime_from');

				$end = $page->time_periods->filter('sort=-datetime_until')->first();
				$page->datetime_until = $end->getUnformatted('datetime_until');
			} else {
				$page->datetime_from = '';
				$page->datetime_until = '';
			}
		}

		// If project page: Set project name as keyword. Otherwise "Verein"
		if ($page->template->hasField('tags')) {
			$projectPage = false;
			if ($this->startsWith($page->template->name, 'project')) {
				$projectPage = $page;
			} else {
				$projectPage = $page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
			}

			$tagTitle = 'Verein';
			if ($projectPage instanceof Page && $projectPage->id) {
				$tagTitle = $projectPage->title;
			}

			$tagContainer = wire('pages')->get('template.name=tags_container');
			if ($tagContainer instanceof Page && $tagContainer->id) {
				$tagPage = $tagContainer->get('title='.$tagTitle);

				if (!($tagPage instanceof Page) || !$tagPage->id) {
					// Create new keyword:
					$tagPage = new Page(wire('templates')->get('name=tag'));
					$tagPage->title = $tagTitle;
				}
				$tagPage->of(false);

				if ($projectPage instanceof Page && $projectPage->id) {
					// Add color to keyword if set:
					if ($projectPage->color) {
						$tagPage->color = $projectPage->color;
					}

					// Accept release dates from the project page to the keyword, if set:
					$tagPage->releasetime_start_activate = $projectPage->releasetime_start_activate;
					$tagPage->releasetime_start = $projectPage->getUnformatted('releasetime_start');

					$tagPage->releasetime_end_activate = $projectPage->releasetime_end_activate;
					$tagPage->releasetime_end = $projectPage->getUnformatted('releasetime_end');
				}

				$tagPage->save(null, ['adjustName' => true]);

				if (!$page->tags->has('id='.$tagPage)) {
					$page->tags->add($tagPage);
				}
			}
		}
	}

	public function hookPagesAfterSave(HookEvent $event) {
		$page = $event->arguments[0];
		if (!$page instanceof Page || !$page->id) {
			return;
		}

		if ($this->startsWith($page->template->name, 'project') && $page->template->name !== 'project_role' && $page->template->name !== 'project_roles_container' && $page->template->name !== 'projects_container') {
			$tagContainer = wire('pages')->get('template.name=tags_container');
			if ($tagContainer instanceof Page && $tagContainer->id) {
				$tagPage = $tagContainer->get('title='.$page->title);
				if($tagPage instanceof Page && $tagPage->id){
					// There is a keyword matching the project

					$of = $tagPage->of();
					$tagPage->of(false);

					// Add color to keyword if set:
					if ($page->color) {
						$tagPage->color = $page->color;
					}

					// Accept release dates from the project page to the keyword, if set:
					$tagPage->releasetime_start_activate = $page->releasetime_start_activate;
					$tagPage->releasetime_start = $page->getUnformatted('releasetime_start');

					$tagPage->releasetime_end_activate = $page->releasetime_end_activate;
					$tagPage->releasetime_end = $page->getUnformatted('releasetime_end');

					$tagPage->save();
					$tagPage->of($of);
				}
			}
		}
	}

	public function hookPageRender(HookEvent $event) {
		$page = $event->object;
		if ($page->process === 'ProcessPageEdit') {
			$page = wire('pages')->get(wire('input')->get->id);
			if (!$page->id) {
				return;
			}
			if ($page->template->name === 'portrait') {
				wire('config')->scripts->add(wire('config')->urls->siteModules . 'SebiMusicalFabrikFunctions/fill-portrait-title.js');
			}
		}
		return;
	}

	protected function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}
