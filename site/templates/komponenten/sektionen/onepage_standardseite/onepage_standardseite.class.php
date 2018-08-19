<?php
namespace ProcessWire;

/*
 * Anzeige einer Standardseite als Onepage-Sektion
 */
class OnepageStandardseite extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		// Die ID der Onepage-Sektion ermitteln:
		$this->sektionID = '';
		if ((string) $this->page->sektion_name) {
			$this->sektionID = (string) $this->page->sektion_name;
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}

		if ($this->page->template->hasField('inhalte')) {
			$this->inhalte = $this->addComponent('Inhalte', [
				'ordner' => '',
				'page' => $this->page,
				'parameter' => ['sektion' => true]
				]);
		}

		$this->addStyle(wire('config')->urls->templates . 'assets/css/sektion-standardseite.min.css', true, true);
	}
}
