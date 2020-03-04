<?php
namespace ProcessWire;

class ContentChart extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->addScript('content_chart.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/content_chart.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));

		// The title can be set by $args or by field "title":
		if (isset($args['title'])) {
			$this->title = $args['title'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->title = $this->page->title;
		}

		if (!$this->page->template->hasField('chart') || $this->page->chart->count < 1) {
			throw new ComponentNotInitializedException("ContentChart", "No chart was found.");
		}

		$this->chart = $this->page->chart->first();
	}

	/**
	 * Returns information about a certain diagram via Ajax
	 * @return array
	 */
	public function getAjax($ajaxArgs = []) {
		$ausgabe = array(
			'type' => $this->chart->type
		);

		if (isset($this->title) && !empty($this->title) && !$this->page->hide_title) {
			$ausgabe['title'] = $this->title;
		}

		if ($this->chart->type === 'bar') {
			$ausgabe['values'] = $this->chart->chart_values->explode(['value', 'label', 'color']);
			$ausgabe['labels'] = $this->chart->chart_labels->explode(['value_minimum', 'value_maximum', 'label']);
		} elseif ($this->chart->type === 'doughnut') {
			$ausgabe['values'] = $this->chart->chart_values->explode(['value', 'label', 'color']);
		}

		return $ausgabe;
	}
}
