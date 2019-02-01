<?php
namespace ProcessWire;

class OnepageFormular extends TwackComponent {
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

		if (!$this->page->template->hasField('formular')) {
			throw new ComponentParameterException('OnepageFormular', 'Am Onepage-Formular wurde kein Feld für die Containerseite hinterlegt.');
		}
		
		$containerSeite = $this->page->get('formular');
		if (!($containerSeite instanceof Page) || !$containerSeite->id) {
			throw new ComponentParameterException('OnepageFormular', 'Im Onepage-Formular wurde keine valide Containerseite angegeben.');
		}

		$formulare = $this->getGlobalComponent('formulare');
		$this->formular = $formulare->addComponent('TemplateFormular', ['containerSeite' => $containerSeite, 'page' => $this->page]);

		if ($this->page->template->hasField('inhalte')) {
			$this->inhalte = $this->addComponent('Inhalte', [
				'directory' => '',
				'page' => $this->page,
				'parameters' => ['sektion' => true]
				]);
		}

		$this->addStyle(wire('config')->urls->templates . 'assets/css/sektion-formular.min.css', true, true);
	}
}
