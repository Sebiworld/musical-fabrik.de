<?php
namespace ProcessWire;

class BreadcrumbKomponente extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->breadcrumbs = $this->page->parents;
		$this->page->active = true;
		if (count($this->breadcrumbs) > 0) {
			$this->breadcrumbs->add($this->page);

			$limit = 50;
			$endstr = '&nbsp;…';

			foreach ($this->breadcrumbs as &$b) {
				$b->title_kurz = Twack::wordLimiter($b->title, $limit, $endstr);
			}
		}

		$this->addStyle(wire('config')->urls->templates . 'assets/css/breadcrumb.min.css', true, true);
	}
}
