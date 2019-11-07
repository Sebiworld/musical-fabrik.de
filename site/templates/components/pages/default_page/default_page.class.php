<?php
namespace ProcessWire;

class DefaultPage extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->configPage = $this->getService('ConfigurationService')->getConfigurationPage();
		$this->imageService = $this->getService('ImageService');

		// Content can be added to the global component 'mainContent':
		$this->twack->makeComponentGlobal($this, 'mainContent');

		$this->singleimageModalId = $this->getGlobalParameter('singleimageModalId');

		$this->title = $this->page->title;
		if ($this->page->template->hasField('headline') && !empty((string) $this->page->headline)) {
			$this->title = $this->page->headline;
		}

		if ($this->page->template->hasField('intro') && !empty((string) $this->page->intro)) {
			$this->intro = $this->page->intro;
		}

		if ($this->page->template->hasField('comments') && $this->page->comments) {
			$this->comments = $this->addComponent('MfComments', ['name' => 'comments', 'directory' => 'partials']);
		}

		if ($this->page->template->hasField('main_image') && $this->page->main_image) {
			$this->mainImage = $this->page->main_image;
		}

		if ($this->page->template->hasField('authors') && $this->page->authors instanceof PageArray) {
			$authors = array();
			foreach ($this->page->authors as $autor) {
				$authors[] = $autor->first_name . ' ' . $autor->surname;
			}
			if (count($authors) > 0) {
				$this->authors = $authors;
			}
		}

		if ($this->page->template->hasField('datetime_from') && !empty($this->page->datetime_from)) {
			$this->datetime_unformatted = $this->page->getUnformatted('datetime_from');
			$this->publishTimeString = date('d.m.Y', $this->datetime_unformatted);


			if ($this->page->template->hasField('datetime_until')) {
				// Time-until field exists: Probably event, i.e. output time as well
				$this->publishTimeString .= sprintf($this->_('On %1$s'), date('d.m.Y, H:m', $this->datetime_unformatted));
				if (!empty($this->page->datetime_until)) {
					$this->datetime_bis_unformatted = $this->page->getUnformatted('datetime_until');
					if (date('d.m.Y', $this->datetime_unformatted) == date('d.m.Y', $this->page->getUnformatted('datetime_until'))) {
						// Same day, add time only
						$this->publishTimeString = sprintf('On %1$s - %2$s', date('d.m.Y, H:m', $this->datetime_unformatted), date('H:m', $this->datetime_until_unformatted));
					} else {
						$this->publishTimeString = sprintf('On %1$s until %2$s', date('d.m.Y, H:m', $this->datetime_unformatted), date('d.m.Y, H:m', $this->datetime_until_unformatted));
					}
				}
			}
		}

		$this->tags = $this->addComponent('tagsField', ['directory' => 'partials', 'name' => 'tags']);

		if ($this->page->template->hasField('contents')) {
			$this->contents = $this->addComponent('ContentsComponent', ['directory' => '']);
		}

		$this->addStyle('default_page.css', array(
            'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true,
			'inline' => true
        ));
	}

	public function getAjax() {
		$output = array(
			'title' => $this->title
		);

		if (!empty($this->datetime_unformatted)) {
			$output['datetime_from'] = $this->datetime_unformatted;
		}

		if (!empty($this->datetime_until_unformatted)) {
			$output['datetime_until'] = $this->datetime_until_unformatted;
		}

		if (!empty($this->intro)) {
			$output['intro'] = $this->intro;
		}

		if (!empty($this->page->main_image)) {
			$output['main_image'] = $this->getAjaxOf($this->page->main_image);
		}

		if (!empty($this->authors)) {
			$output['authors'] = $this->authors;
		}

		if ($this->tags && $this->tags instanceof TwackComponent) {
			$tagAjax = $this->tags->getAjax();
			if(!empty($tagAjax)){
				$output['tags'] = $tagAjax;
			}
		}

		if ($this->contents && $this->contents instanceof TwackComponent) {
			$output['contents'] = $this->contents->getAjax();
		}

		// The component is registered under the global name "mainContent". From the template files some components are added manually.
		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax();
				if(empty($ajax)) continue;
				$output = array_merge($output, $ajax);
			}
		}

		return $output;
	}
}
