<?php
namespace ProcessWire;

class FooterComponent extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$footerPage = wire('pages')->get('template.name=footer');

		// footer image
		// Become a member - Calltoaction
		if ($footerPage->template->hasField('contents')) {
			$this->contents = $this->addComponent('ContentsComponent', ['directory' => '', 'page' => $footerPage, 'useField' => 'contents']);
		}

		$this->breadcrumbs = $this->addComponent('BreadcrumbsComponent', ['name' => 'breadcrumbs', 'directory' => 'partials']);

		// Header Menu, Contact, Tag-Cloud
		$this->menue = new PageArray();
		$this->menue->add($this->getGlobalComponent('header')->main_navigation);
		$this->menue->add($this->getGlobalComponent('header')->secondary_navigation);

		$this->adresse = $footerPage->adresse;

		$this->tags = $this->addComponent('TagsBox', ['directory' => 'partials', 'limit' => 20]);

		// Socialmedia-Icons
		if ($footerPage->template->hasField('socialmedia_links') && count($footerPage->socialmedia_links) > 0) {
			$this->socialmedia_links = $footerPage->socialmedia_links;
		}

		//  Dashboard, Impressum, Datenschutz, Sitemap links / Copyright right side
		$this->tertiary_navigation = new PageArray();
		if ($footerPage->template->hasField('main_navigation') && count($footerPage->main_navigation) > 0) {
			foreach ($footerPage->main_navigation as $navItem) {
				if ($navItem->type === 'page_reference') {
					$navItem->link = '';
					if ($navItem->template->hasField('page_reference') && $navItem->page_reference->id) {
						$navItem->link .= $navItem->page_reference->url;
					}
					if ($navItem->template->hasField('section_name') && $navItem->section_name) {
						$navItem->link .= '#' . $navItem->section_name;
					}
					$this->tertiary_navigation->add($navItem);
				} elseif ($navItem->type === 'link') {
					$this->tertiary_navigation->add($navItem);
				}
			}
		}

		$searchPage = wire('pages')->get('template.name=search_page');
		if ($searchPage instanceof Page && $searchPage->id) {
			$this->searchPage = $searchPage;
		}

		$this->copyright = "&copy;&nbsp;Copyright 2012&nbsp;-&nbsp;" . date('Y') . ' &nbsp;' . $footerPage->short_text;
	}
}
