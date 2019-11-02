<?php
namespace ProcessWire;

class MfCommentForm extends CommentForm {
	public function __construct(Page $page, CommentArray $comments, $options = array()) {
		parent::__construct($page, $comments, $options);

		// default messages
		$h3 = $this->_('h3'); // Headline tag
		$this->options['headline'] = "<$h3>" . $this->_('Post Comment') . "</$h3>"; // Form headline
		$this->options['successMessage'] = "<div class='alert alert-success'><strong>" . $this->_('Thank you, your submission has been saved.') . "</strong></div>";
		$this->options['pendingMessage'] = "<div class='alert alert-success pending'><strong>" . $this->_('Your comment has been submitted and will appear once approved by the moderator.') . "</strong></div>";
		$this->options['errorMessage'] = "<div class='alert alert-danger'><strong>" . $this->_('Your submission was not saved due to one or more errors. Please check that you have completed all fields before submitting again.') . "</strong></div>";
	}

	protected function renderFormNormal($id, $class, $attrs, $labels, $inputValues) {
		$form =
		"\n<form id='{$id}_form' class='$class CommentFormNormal' action='$attrs[action]#$id' method='$attrs[method]'>" .
		"\n\t<div class='CommentFormCite {$id}_cite form-group'>" .
		"\n\t\t<label for='{$id}_cite'>$labels[cite]</label>" .
		"\n\t\t<input type='text' name='cite' class='required form-control' required='required' id='{$id}_cite' value='$inputValues[cite]' maxlength='128' />" .
		"\n\t</div>" .
		"\n\t<div class='CommentFormEmail {$id}_email form-group'>" .
		"\n\t\t<label for='{$id}_email'>$labels[email]</label>" .
		"\n\t\t<input type='text' name='email' class='required email form-control' required='required' id='{$id}_email' value='$inputValues[email]' maxlength='255' />" .
		"\n\t</div>";

		if ($this->commentsField && $this->commentsField->useWebsite && $this->commentsField->schemaVersion > 0) {
			$form .=
			"\n\t<div class='CommentFormWebsite {$id}_website form-group'>" .
			"\n\t\t<label for='{$id}_website'>$labels[website]</label>" .
			"\n\t\t<input type='text' name='website' class='website form-control' id='{$id}_website' value='$inputValues[website]' maxlength='255' />" .
			"\n\t</div>";
		}

		if ($this->commentsField->useStars && $this->commentsField->schemaVersion > 5) {
			$commentStars = new MfCommentStars();
			$starsClass = 'CommentFormStars';
			if ($this->commentsField->useStars > 1) {
				$starsNote = $labels['starsRequired'];
				$starsClass .= ' CommentFormStarsRequired';
			} else {
				$starsNote = '';
			}

			$form .=
			"<div class='$starsClass {$id}_stars form-group' data-note='$starsNote'>
				<fieldset class='starability-growRotate'>"
					.($labels['stars'] ? "\n\t\t<legend for='{$id}_stars'>$labels[stars]</legend>" : "").
					"<input type='radio' id='first-rate1' name='stars' value='1' />
				  	<label for='first-rate1' title='Terrible'>1 star</label>
				  	<input type='radio' id='first-rate2' name='stars' value='2' />
				  	<label for='first-rate2' title='Not good'>2 stars</label>
				  	<input type='radio' id='first-rate3' name='stars' value='3' />
				  	<label for='first-rate3' title='Average'>3 stars</label>
				  	<input type='radio' id='first-rate4' name='stars' value='4' />
				  	<label for='first-rate4' title='Very good'>4 stars</label>
				  	<input type='radio' id='first-rate5' name='stars' value='5' />
				  	<label for='first-rate5' title='Amazing'>5 stars</label>
				</fieldset>
			</div>";

			// $form .=
			// "\n\t<div class='$starsClass {$id}_stars form-group' data-note='$starsNote'>" .
			// ($labels['stars'] ? "\n\t\t<label for='{$id}_stars'>$labels[stars]</label>" : "") .
			// "\n\t\t<input type='number' class='form-control' name='stars' id='{$id}_stars' value='$inputValues[stars]' min='0' max='5' />" .
			// "\n\t\t" . $commentStars->render(0, true) .
			// "\n\t</div>";
		}

		// do we need to show the honeypot field?
		$honeypot = $this->options['requireHoneypotField'];
		if ($honeypot) {
			$honeypotLabel = isset($labels[$honeypot]) ? $labels[$honeypot] : '';
			$honeypotValue = isset($inputValues[$honeypot]) ? $inputValues[$honeypot] : '';
			$form .=
			"\n\t<div class='CommentFormHP {$id}_hp form-group'>" .
			"\n\t\t<label for='{$id}_$honeypot'>$honeypotLabel</label>" .
			"\n\t\t<input type='text' class='form-control' id='{$id}_$honeypot' name='$honeypot' value='$honeypotValue' size='3' />" .
			"\n\t</div>";
		}

		$form .=
		"\n\t<div class='CommentFormText {$id}_text form-group'>" .
		"\n\t\t<label for='{$id}_text'>$labels[text]</label>" .
		"\n\t\t<textarea name='text' class='required form-control' required='required' id='{$id}_text' rows='$attrs[rows]' cols='$attrs[cols]'>$inputValues[text]</textarea>" .
		"\n\t</div>" .
		$this->renderNotifyOptions() .
		"\n\t<div class='CommentFormSubmit {$id}_submit form-group'>" .
		"\n\t\t<button type='submit' name='{$id}_submit' id='{$id}_submit' value='1'>$labels[submit]</button>" .
		"\n\t\t<input type='hidden' name='page_id' value='{$this->page->id}' />" .
		"\n\t</div>" .
		"\n</form>";

		return $form;
	}

	protected function renderFormThread($id, $class, $attrs, $labels, $inputValues) {

		$form =
		"\n<form class='$class CommentFormThread' action='$attrs[action]#$id' method='$attrs[method]'>" .
		"\n\t<p class='CommentFormCite {$id}_cite'>" .
		"\n\t\t<label>" .
		"\n\t\t\t<span>$labels[cite]</span> " .
		"\n\t\t\t<input type='text' name='cite' class='required' required='required' value='$inputValues[cite]' maxlength='128' />" .
		"\n\t\t</label> " .
		"\n\t</p>" .
		"\n\t<p class='CommentFormEmail {$id}_email'>" .
		"\n\t\t<label>" .
		"\n\t\t\t<span>$labels[email]</span> " .
		"\n\t\t\t<input type='email' name='email' class='required email' required='required' value='$inputValues[email]' maxlength='255' />" .
		"\n\t\t</label>" .
		"\n\t</p>";

		if ($this->commentsField && $this->commentsField->useWebsite && $this->commentsField->schemaVersion > 0) {
			$form .=
			"\n\t<p class='CommentFormWebsite {$id}_website'>" .
			"\n\t\t<label>" .
			"\n\t\t\t<span>$labels[website]</span> " .
			"\n\t\t\t<input type='text' name='website' class='website' value='$inputValues[website]' maxlength='255' />" .
			"\n\t\t</label>" .
			"\n\t</p>";
		}

		if ($this->commentsField->useStars && $this->commentsField->schemaVersion > 5) {
			$commentStars = new MfCommentStars();
			$starsClass = 'CommentFormStars';
			if ($this->commentsField->useStars > 1) {
				$starsNote = $labels['starsRequired'];
				$starsClass .= ' CommentFormStarsRequired';
			} else {
				$starsNote = '';
			}
			$form .=
			"\n\t<p class='$starsClass {$id}_stars' data-note='$starsNote'>" .
			"\n\t\t<label>" .
			"\n\t\t\t<span>$labels[stars]</span>" .
			"\n\t\t\t<input type='number' name='stars' value='$inputValues[stars]' min='0' max='5' />" .
			"\n\t\t\t" . $commentStars->render(0, true) .
			"\n\t\t</label>" .
			"\n\t</p>";
		}

		// do we need to show the honeypot field?
		$honeypot = $this->options['requireHoneypotField'];
		if ($honeypot) {
			$honeypotLabel = isset($labels[$honeypot]) ? $labels[$honeypot] : '';
			$honeypotValue = isset($inputValues[$honeypot]) ? $inputValues[$honeypot] : '';
			$form .=
			"\n\t<p class='CommentFormHP {$id}_hp'>" .
			"\n\t\t<label><span>$honeypotLabel</span>" .
			"\n\t\t<input type='text' name='$honeypot' value='$honeypotValue' size='3' />" .
			"\n\t\t</label>" .
			"\n\t</p>";
		}

		$form .=
		"\n\t<p class='CommentFormText {$id}_text'>" .
		"\n\t\t<label>" .
		"\n\t\t\t<span>$labels[text]</span>" .
		"\n\t\t\t<textarea name='text' class='required' required='required' rows='$attrs[rows]' cols='$attrs[cols]'>$inputValues[text]</textarea>" .
		"\n\t\t</label>" .
		"\n\t</p>" .
		$this->renderNotifyOptions() .
		"\n\t<p class='CommentFormSubmit {$id}_submit'>" .
		"\n\t\t<button type='submit' name='{$id}_submit' value='1'>$labels[submit]</button>" .
		"\n\t\t<input type='hidden' name='page_id' value='{$this->page->id}' />" .
		"\n\t\t<input type='hidden' class='CommentFormParent' name='parent_id' value='0' />" .
		"\n\t</p>" .
		"\n</form>";

		return $form;
	}
}
