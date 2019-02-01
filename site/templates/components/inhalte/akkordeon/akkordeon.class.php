<?php
namespace ProcessWire;

class Akkordeon extends TwackComponent {

	protected $idService;

	public function __construct($args) {
		parent::__construct($args);
		$this->tabs = new WireArray();

		$this->idService = $this->getService('IdService');
		$this->id = $this->idService->getID('akkordeon');

		if ($this->page->template->hasField('akkordeon') && count($this->page->akkordeon) > 0) {
			$this->generiereTabs($this->page->akkordeon);
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}
	}

	public function addTab($titel, $inhalt = '') {
		if (!is_string($titel)) {
			return null;
		}
		if (!is_string($inhalt)) {
			$inhalt = '';
		}

		$tab = new WireData();
		$tab->id = $this->idService->getID($this->id . '-' . $this->tabs->count);
		$tab->titel = $titel;
		$tab->inhalt = $inhalt;

		$this->tabs->add($tab);
		return $tab;
	}

	protected function generiereTabs(PageArray $tabs) {
		foreach ($tabs as $tab) {
			if ($tab->type == 'freitext') {
				$this->addTab($tab->title_html, $tab->freitext);
			}
		}
	}
}
