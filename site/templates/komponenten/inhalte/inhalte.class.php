<?php
namespace ProcessWire;

class Inhalte extends TwackComponent {

	public function __construct($args = array(), $seite = null, $pfade = null) {
		parent::__construct($args, $seite, $pfade);

		if (isset($args['nutzefeld']) && !empty($args['nutzefeld'])) {
			$this->nutzefeld = (string) $args['nutzefeld'];
		} elseif (!isset($this->nutzefeld) || empty($this->nutzefeld)) {
			$this->nutzefeld = 'inhalte';
		}

		$this->nutzeFeld($this->nutzefeld);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/inhalt.min.css', true, true);
	}

	/**
	 * Nimmt ein Feld entgegen (RepeaterMatrix), und baut aus den gesetzten Inhalten die Slider-Seiten zusammen
	 * @param Field $feld
	 */
	protected function nutzeFeld($feldname) {
		if ($this->page->template->hasField($feldname)) {
			foreach ($this->page->get($feldname) as $inhaltselement) {
				// Namen aus der Repeatermatrix Komponentennamen zuordnen:
				try {
					// Nach der Komponente im Ordner der Inhaltskomponente suchen:
					$this->addComponent($inhaltselement->type, [
					'page' => $inhaltselement,
					'parameters' => $this->getArray()
					]);
				} catch (\Exception $e) {
					try {
						$this->addComponent($inhaltselement->type, [
						'page' => $inhaltselement,
						'location' => array(),
						'parameters' => $this->getArray()
						]);
					} catch (\Exception $e) {
					}
				}
			}
		}
	}

	public function getAjax() {
		$output = array(
			'inhalte' => []
		);

		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax();
				if(empty($ajax)) continue;

				$seite = $component->getPage();
				$ajax['depth'] = $seite->depth;
				$output['inhalte'][] = $ajax;
			}
		}

		return $output;
	}
}
