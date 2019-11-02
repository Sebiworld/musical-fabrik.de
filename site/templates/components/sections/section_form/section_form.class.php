<?php
namespace ProcessWire;

class SectionForm extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		// Determine the ID of the one-page section:
		$this->sectionId = '';
		if ((string) $this->page->section_name) {
			$this->sectionId = (string) $this->page->section_name;
		}

		// The title can be set by $args or by field "title":
		if (isset($args['title'])) {
			$this->title = $args['title'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->title = $this->page->title;
		}

		if (!$this->page->template->hasField('form')) {
			throw new ComponentParameterException('SectionForm', $this->_('No field for the container page has been defined on the one-page form.'));
		}
		
		$containerPage = $this->page->get('form');
		if (!($containerPage instanceof Page) || !$containerPage->id) {
			throw new ComponentParameterException('SectionForm', $this->_('No valid container page was specified in the one-page form.'));
		}

		$forms = $this->getGlobalComponent('forms');
		$this->form = $forms->addComponent('FormTemplate', ['containerPage' => $containerPage, 'page' => $this->page]);

		if ($this->page->template->hasField('contents')) {
			$this->contents = $this->addComponent('ContentsComponent', [
				'directory' => '',
				'page' => $this->page,
				'parameters' => ['section' => true]
				]);
		}
	}

	// public function getAjax() {
	// 	$output = array();
		
	// 	$output = $this->form->getAjax();

	// 	if ($this->childComponents) {
	// 		foreach ($this->childComponents as $component) {
	// 			$ajax = $component->getAjax();
	// 			if(empty($ajax)) continue;
	// 			$output = array_merge($output, $ajax);
	// 		}
	// 	}

	// 	return $output;
	// }
}
