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

		$this->addComponent('Alerts', ['directory' => 'partials', 'name' => 'alerts', 'useField' => 'alerts']);
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'type' => 'form',
			'id' => $this->page->id,
			'section_name' => $this->page->section_name,
			'title' => $this->title,
			'hide_title' => !!$this->page->hide_title || empty($this->page->title),
			'classes' => $this->page->classes,
			'intro' => $this->page->intro,
			'form' => $this->form->getAjax($ajaxArgs),
			'outro' => $this->page->freetext,
		];

		if ($this->contents) {
			$ajax = $this->contents->getAjax($ajaxArgs);
			if (!empty($ajax)) {
				$output = array_merge($output, $ajax);
			}
		}

		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax($ajaxArgs);
				if (empty($ajax)) {
					continue;
				}
				$output = array_merge($output, $ajax);
			}
		}

		$alertsComponent = $this->getComponent('alerts');
		if ($alertsComponent) {
				$output['alerts'] = $alertsComponent->getAjax();
		}

		return $output;
	}
}
