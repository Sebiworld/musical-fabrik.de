<?php
namespace ProcessWire;

class MfComments extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->idService = $this->getService('IdService');

		$useField = 'comments';
		if (isset($args['useField'])) {
			$useField = $args['useField'];
		}

		if ($this->page->template->hasField($useField) && $this->page->get($useField)) {
			$this->comments = $this->page->get($useField);
		}
	}

	protected function getComments(CommentArray $comments) {
		return $comments->getCommentList();
	}

	protected function getForm(CommentArray $comments, $options = array()) {
		return $this->wire(new MfCommentForm($this->page, $comments, $options));
	}

	protected function getCount(CommentArray $comments) {
		// https://github.com/LunarLogic/starability
		$stars = $comments->stars();
		return "
		<div class='sterne'>
			<div class='starability-result' data-rating='".str_replace(',', '.', $comments->stars(false))."' aria-describedby='rated-element'>"
				.$stars." Sterne
			</div>
			Insgesamt ".$stars." Sterne
		</div>";
	}

	protected function getStars(CommentArray $comments) {
		// https://github.com/LunarLogic/starability
		$stars = $comments->stars();
		return "
		<div class='sterne'>
			<div class='starability-result' data-rating='".str_replace(',', '.', $comments->stars(false))."' aria-describedby='rated-element'>"
				.$stars." Sterne
			</div>
			Insgesamt ".$stars." Sterne
		</div>";
	}
}
