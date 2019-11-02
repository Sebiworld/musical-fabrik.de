<?php
namespace ProcessWire;

class HeaderComponent extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$headerPage = wire('pages')->get('template=header');

		// main image:
		$this->logo = $headerPage->main_image;

		// headline:
		$this->headline = $headerPage->headline;

		// build navigation:
		$this->navigation = new PageArray();
		if ($headerPage->template->hasField('main_navigation') && count($headerPage->main_navigation) > 0) {
			foreach ($headerPage->main_navigation as $navItem) {
				if ($navItem->type === 'page_reference') {
					$navItem->link = '';

					if ($navItem->template->hasField('page_reference') && $navItem->page_reference->id) {
						if (!$navItem->page_reference->viewable()) {
							continue;
						}
						$navItem->link .= $navItem->page_reference->url;

						if ($navItem->page_reference->id == $this->page->id) {
							$navItem->active = true;
						}
					}

					if ($navItem->template->hasField('section_name') && $navItem->section_name) {
						$navItem->link .= '#' . $navItem->section_name;

						// With Hashvalues the activation of menu items is regulated by Javascript:
						$navItem->active = false;
					}

					$this->navigation->add($navItem);
				} elseif ($navItem->type === 'link') {
					$this->navigation->add($navItem);
				}
			}
		}

		$this->idService = $this->getService('IdService');
		$this->mainMenuId = $this->idService->getID('main_menu');
		$this->dropdownId = $this->idService->getID('header_dropdown');
		$this->dropdownLabelId = $this->idService->getID('header_dropdown_label');

		// Build secondary navigation:
		$this->secondaryNavigation = new PageArray();
		if ($headerPage->template->hasField('secondary_navigation') && count($headerPage->secondary_navigation) > 0) {
			foreach ($headerPage->secondary_navigation as $navItem) {
				if ($navItem->type === 'page_reference') {
					$navItem->link = '';
					if ($navItem->template->hasField('page_reference') && $navItem->page_reference->id) {
						if (!$navItem->page_reference->viewable()) {
							continue;
						}
						$navItem->link .= $navItem->page_reference->url;
					}
					if ($navItem->template->hasField('section_name') && $navItem->section_name) {
						$navItem->link .= '#' . $navItem->section_name;
					}
					$this->secondaryNavigation->add($navItem);
				} elseif ($navItem->type === 'link') {
					$this->secondaryNavigation->add($navItem);
				}
			}
		}
	}

	public function getLogo() {
		return $this->logo;
	}
}
