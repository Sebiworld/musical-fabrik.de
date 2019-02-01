<?php
namespace ProcessWire;

// require 'kommentar_array.class.php';

class Kommentare extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->idService = $this->getService('IdService');

		$nutzeFeld = 'kommentare';
		if (isset($args['nutzeFeld'])) {
			$nutzeFeld = $args['nutzeFeld'];
		}

		if ($this->page->template->hasField($nutzeFeld) && $this->page->get($nutzeFeld)) {
			$this->kommentare = $this->page->get($nutzeFeld);
		}
	}

	protected function getKommentare(CommentArray $kommentare) {
		return $kommentare->getCommentList();
	}

	protected function getFormular(CommentArray $kommentare, $options = array()) {
		return $this->wire(new KommentarFormular($this->page, $kommentare, $options));
	}

	protected function getAnzahl(CommentArray $kommentare) {
		// https://github.com/LunarLogic/starability
		$sterne = $kommentare->stars();
		return "
		<div class='sterne'>
			<div class='starability-result' data-rating='".str_replace(',', '.', $kommentare->stars(false))."' aria-describedby='rated-element'>"
				.$sterne." Sterne
			</div>
			Insgesamt ".$sterne." Sterne
		</div>";
	}

	protected function getSterne(CommentArray $kommentare) {
		// https://github.com/LunarLogic/starability
		$sterne = $kommentare->stars();
		return "
		<div class='sterne'>
			<div class='starability-result' data-rating='".str_replace(',', '.', $kommentare->stars(false))."' aria-describedby='rated-element'>"
				.$sterne." Sterne
			</div>
			Insgesamt ".$sterne." Sterne
		</div>";
	}
}
