<?php
namespace ProcessWire;

require 'kommentar_formular.class.php';
require 'kommentar_liste.class.php';
require 'kommentar_sterne.class.php';

/**
 * ProcessWire FieldtypeComments > CommentArray
 *
 * Maintains an array of multiple Comment instances.
 * Serves as the value referenced when a FieldtypeComment field is reference from a Page.
 *
 * ProcessWire 3.x, Copyright 2016 by Ryan Cramer
 * https://processwire.com
 *
 *
 */

class KommentarArray extends CommentArray {
	/**
	 * Return instance of CommentList object
	 *
	 * @param array $options See CommentList::$options for details
	 * @return CommentList
	 *
	 */
	public function getCommentList(array $options = array()) {
		return $this->wire(new KommentarListe($this, $options));
	}

	/**
	 * Provides the default rendering of a comment form, which may or may not be what you want
	 *
	 * @param array $options
	 * @return string
	 * @see CommentForm class and override it to serve your needs
	 *
	 */
	public function renderForm(array $options = array()) {
		$defaultOptions = array(
			'depth' => ($this->field ? (int) $this->field->get('depth') : 0)
			);
		$options = array_merge($defaultOptions, $options);
		$form = $this->getCommentForm($options);
		return $form->render();
	}

	/**
	 * Return instance of CommentForm object
	 *
	 * @param array $options
	 * @return CommentForm
	 * @throws WireException
	 *
	 */
	public function getCommentForm(array $options = array()) {
		// parent::getCommentForm($options);
		return $this->wire(new KommentarFormular($this->page, $this, $options));
	}

	/**
	 * Render combined star rating for all comments in this CommentsArray
	 *
	 * @param bool $showCount Specify true to include how many ratings the average is based on
	 * @param array $options Overrides of stars and/or count, see $defaults in method
	 * @return string
	 *
	 */
	public function renderStars($showCount = false, $options = array()) {
		$defaults = array(
			'stars' => null, // optionally override the combined stars value (stars and count must both be specified)
			'count' => null, // optionally override the combined count value (stars and count must both be specified)
			'blank' => true, // return blank string if no ratings yet?
			'partials' => true, // allow partial stars?
			'schema' => '', // may be 'rdfa', 'microdata' or blank. Used only if showCount=true.
			'input' => false, // allow input? (may not be combined with 'partials' option)
		);
		$options = array_merge($defaults, $options);
		if (!is_null($options['stars'])) {
			$stars = $options['stars'];
			$count = (int) $options['count'];
		} else {
			list($stars, $count) = $this->stars($options['partials'], true);
		}
		if (!$count && $options['blank']) {
			return '';
		}
		$commentStars = new CommentStars();
		$out = $commentStars->render($stars, $options['input']);
		if ($showCount) {
			$out .= $commentStars->renderCount((int) $count, $stars, $options['schema']);
		}
		return $out;
	}
}
