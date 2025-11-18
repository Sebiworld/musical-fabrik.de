<?php
namespace ProcessWire;

/*
 * Display of a standard page as a one-page section
 */
class SectionPage extends TwackComponent {
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
			$this->contents = $this->addComponent('ContentsComponent', [
				'directory' => '',
				'page' => $this->page,
				'parameters' => ['section' => true]
			]);
		}

		$this->addComponent('Alerts', ['directory' => 'partials', 'name' => 'alerts', 'useField' => 'alerts']);
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'type' => 'page',
			'id' => $this->page->id,
			'section_name' => $this->page->section_name,
			'title' => $this->title,
			'hide_title' => !!$this->page->hide_title || empty($this->page->title),
			'classes' => $this->page->classes,
		];

		if ($this->contents) {
			$ajax = $this->contents->getAjax($ajaxArgs);
			if (!empty($ajax)) {
				$output = array_merge($output, $ajax);
			}
		}

		$alertsComponent = $this->getComponent('alerts');
		if ($alertsComponent) {
				$output['alerts'] = $alertsComponent->getAjax();
		}

		return $output;
	}
}
