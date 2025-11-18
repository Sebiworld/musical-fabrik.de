<?php
namespace ProcessWire;

class SectionPartnersAndSponsors extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

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

		if ($this->page->template->hasField('contents')) {
			$this->contents = $this->addComponent('ContentsComponent', ['directory' => '', 'page' => $this->page]);
		}

		$this->partners = new WireArray();
		if ($this->page->template->hasField('partners')) {
			$this->partners = $this->page->partners->sort('random');
		}

		$this->sponsors = new WireArray();
		if ($this->page->template->hasField('sponsors')) {
			$this->sponsors = $this->page->sponsors->sort('random');
		}

		$this->addComponent('Alerts', ['directory' => 'partials', 'name' => 'alerts', 'useField' => 'alerts']);
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'type' => 'partners-and-sponsors',
			'id' => $this->page->id,
			'section_name' => $this->page->section_name,
			'title' => $this->title,
			'hide_title' => !!$this->page->hide_title || empty($this->page->title),
			'classes' => $this->page->classes,
			'partners' => [],
			'sponsors' => [],
		];

		if ($this->contents) {
			$ajax = $this->contents->getAjax($ajaxArgs);
			if (!empty($ajax)) {
				$output = array_merge($output, $ajax);
			}
		}

		if ($this->page->template->hasField('partners') && $this->page->partners) {
			foreach ($this->page->partners->sort('name') as $listIndex => $page) {
				$itemOutput = [
					'id' => $page->id,
					'title' => $page->title,
					'image' => wire('twack')->getAjaxOf($page->main_image)
				];
				$output['partners'][] = $itemOutput;
			}
		}

		if ($this->page->template->hasField('sponsors') && $this->page->sponsors) {
			foreach ($this->page->sponsors->sort('name') as $listIndex => $page) {
				$itemOutput = [
					'id' => $page->id,
					'title' => $page->title,
					'image' => wire('twack')->getAjaxOf($page->main_image)
				];
				$output['sponsors'][] = $itemOutput;
			}
		}

		$alertsComponent = $this->getComponent('alerts');
		if ($alertsComponent) {
				$output['alerts'] = $alertsComponent->getAjax();
		}

		return $output;
	}
}
