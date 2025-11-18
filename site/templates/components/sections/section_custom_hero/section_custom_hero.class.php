<?php
namespace ProcessWire;

class SectionCustomHero extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->imageService = $this->getService('ImageService');

		// Determine the ID of the one-page section:
		$this->sectionId = '';
		if ((string) $this->page->section_name) {
			$this->sectionId = (string) $this->page->section_name;
		}

		// The title can be set by $args or by field "title":
		if (isset($args['title'])) {
			$this->title = $args['title'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->title = $this->page->title;
		}

		$this->addScript('custom-hero.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		]);
		$this->addScript('legacy/custom-hero.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		]);

		$this->addComponent('Alerts', ['directory' => 'partials', 'name' => 'alerts', 'useField' => 'alerts']);
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'id' => $this->page->id,
			'type' => 'hero',
			'classes' => $this->page->classes,
			// 'section_name' => $this->page->section_name,
			// 'hide_title' => !!$this->page->hide_title || empty($this->page->title),
			// 'background_image' => wire('twack')->getAjaxOf($this->page->background_image),
			// 'background_image_dark' => wire('twack')->getAjaxOf($this->page->background_image_dark),
			// 'contents' => $this->contents->getAjax($ajaxArgs),
			// 'domain_alternatives' => wire('twack')->getAjaxOf($this->page->domain_alternatives),
		];

		$alertsComponent = $this->getComponent('alerts');
		if ($alertsComponent) {
			$output['alerts'] = $alertsComponent->getAjax();
		}

		return $output;
	}
}
