<?php
namespace ProcessWire;

class ContentCollapsible extends TwackComponent {

	protected $idService;

	public function __construct($args) {
		parent::__construct($args);
		$this->tabs = new WireArray();

		$this->idService = $this->getService('IdService');
		$this->id = $this->idService->getID('collapsible');

		if ($this->page->template->hasField('collapsible') && count($this->page->collapsible) > 0) {
			$this->generateTabs($this->page->collapsible);
		}

		// The title can be set by $args or by field "title":
		if (isset($args['title'])) {
			$this->title = $args['title'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->title = $this->page->title;
		}
	}

	public function addTab($title, $content = '') {
		if (!is_string($title)) {
			return null;
		}
		if (!is_string($content)) {
			$content = '';
		}

		$tab = new WireData();
		$tab->id = $this->idService->getID($this->id . '-' . $this->tabs->count);
		$tab->title = $title;
		$tab->content = $content;

		$this->tabs->add($tab);
		return $tab;
	}

	protected function generateTabs(PageArray $tabs) {
		foreach ($tabs as $tab) {
			if ($tab->type == 'freetext') {
				$this->addTab($tab->title_html, $tab->freetext);
			}
		}
	}
}
