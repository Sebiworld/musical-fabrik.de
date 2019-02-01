<?php
namespace ProcessWire;

/*
* Vorgabe, wie ein FormularAusgabeTyp aussehen muss:
* (So können statt Bootstrap auch noch andere Ausgabe-Varianten definiert werden)
*/
if (!class_exists('Processwire\FormularAusgabeTyp')) {
	abstract class FormularAusgabeTyp extends TwackComponent {
		protected $idService;
		protected $platzhaltertexte;

		public function __construct($args) {
			parent::__construct($args);
			$this->idService = $this->getService('IdService');

			$this->platzhaltertexte = array();
			if (isset($args['platzhaltertexte']) && is_array($args['platzhaltertexte'])) {
				$this->platzhaltertexte = $args['platzhaltertexte'];
			}
		}

		/**
		 * Liefert den HTML-String für die Ausgabe eines einzelnen Feldes
		 * @param  Field  $feld
		 * @param  Page   $seite
		 * @return string
		 */
		abstract public function getFeldHTML(Field $feld, Page $seite);

		/**
		 * Durchsucht einen String nach {{Platzhaltern}}, und ersetzt diese, sofern Entsprechungen definiert wurden in $args["platzhaltertexte"].
		 * @param  string $eingabe
		 * @return string
		 */
		protected function ersetzePlatzhalter($eingabe) {
			if (!is_string($eingabe)) {
				$eingabe .= '';
			}
			foreach ($this->platzhaltertexte as $key => $value) {
				$eingabe = str_replace("{{ ".$key." }}", $value, $eingabe);
				$eingabe = str_replace("{{".$key."}}", $value, $eingabe);
			}
			$eingabe = preg_replace("/\{\{([^}]+)\}\}/", "", $eingabe);
			return $eingabe;
		}

		/**
		 * Prüft, ob ein String Platzhalter besitzt.
		 * @param  string $eingabe
		 * @return boolean
		 */
		protected function hasPlatzhalter($eingabe) {
			return !!strstr($eingabe, "{{") && !!strstr($eingabe, "}}");
		}

		/**
		 * Formt ein Attribut-Array zu einem String um, der als HTML-Attribut verwendet werden kann.
		 * @param  array $attribute
		 * @return string
		 */
		public function getAttributeString($attribute) {
			if (!is_array($attribute)) {
				return '';
			}

			$output = ' ';
			foreach ($attribute as $key => $value) {
				$output .= $key . '="' . $value . '" ';
			}
			return $output;
		}
	}
}
