<?php
namespace ProcessWire;

class PageCard extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->viewType = $this->page->template->name;
		if (!empty($args['viewType'])) {
			$this->viewType = $args['viewType'];
		}

		if (isset($args['directory'])) {
			unset($args['directory']);
		}
		$args['logging'] = false;
		$args['throwErrors'] = false;

		$this->cardComponent = false;
		$this->cardComponent = $this->addComponent(
			$this->viewType . '_card',
			$args
		);

		if ($this->cardComponent instanceof TwackNullComponent) {
			$this->viewType = 'default';

			$this->cardComponent = $this->addComponent(
				$this->viewType . '_card',
				$args
			);
		}

		if (empty($this->classes)) {
			$this->classes = '';
		}
		$this->classes .= ' ' . $this->viewType . '_card';

		$this->attributeString = '';
		if (!empty($args['attributes']) && is_array($args['attributes'])) {
			$attrParts = [];
			foreach ($args['attributes'] as $key => $value) {
				$attrParts[] = $key . '="' . $value . '"';
			}

			$this->attributeString = implode(' ', $attrParts);
		}

		$this->date = $this->page->created;
		if ($this->page->template->hasField('datetime_from')) {
			$this->date = $this->page->getUnformatted('datetime_from');
		}
	}

	// 	main_image ?: ImageDto;
	// intro ?: string;
	// datetime_from ?: number;
	// project ?: ProjectPageDto;
	// details_deactivated ?: boolean;
	// external_type ?: string;
	// external_link ?: string;

	public function getAjax($ajaxArgs = []) {
		$output = $this->getAjaxOf($this->page);
		$output['viewtype'] = $this->viewType;

		if ($this->page->template->hasField('external_type') && $this->page->external_type) {
			$output['external_type'] = $this->page->external_type;
		}

		if ($this->page->template->hasField('external_link') && $this->page->external_link) {
			$output['external_link'] = $this->page->external_link;
		}

		if ($this->cardComponent) {
			$ajax = $this->cardComponent->getAjax($ajaxArgs);

			if (!empty($ajax) && is_array($ajax)) {
				$output = array_merge($output, $ajax);
			}
		}

		// Project infos
		$projectPage = $this->getService('ProjectService')->getProjectPage($this->page);
		if ($projectPage instanceof Page && !!$projectPage->id) {
			$output['project_id'] = $projectPage->id;
		}

		if ($this->wire('input')->get('htmlOutput')) {
			$output['html'] = $this->renderView();
		}

		return $output;
	}

	public function getAjaxMore($ajaxArgs = []) {
		$output = $this->getAjaxOf($this->page);
		$output['datetime_from'] = $this->date;
		$output['intro'] = $this->page->intro;

		$output['details_deactivated'] = !$this->page->template->hasField('no_details_view') || !$this->page->no_details_view;

		if ($output['details_deactivated']) {
			if ($this->page->template->hasField('btn_text')) {
				$output['btn_text'] = $this->page->btn_text;
			}
		} else {
			unset($output['url'], $output['httpUrl']);
		}

		if ($this->page->template->hasField('gridImage') && $this->page->gridImage) {
			$output['main_image'] = $this->getAjaxOf($this->page->gridImage);
		} else if ($this->page->template->hasField('main_image') && $this->page->main_image) {
			$output['main_image'] = $this->getAjaxOf($this->page->main_image->height(300));
		}

		if ($this->page->template->hasField('card_image') && $this->page->card_image) {
			$output['card_image'] = $this->getAjaxOf($this->page->card_image);
		}


		if ($this->page->template->hasField('color') && $this->page->color) {
			$output['color'] = $this->page->color;
		}

		if ($this->page->template->hasField('info_overlay') && $this->page->info_overlay) {
			$output['description'] = $this->page->info_overlay;
		}

		// Project infos
		$projectPage = $this->getService('ProjectService')->getProjectPage($this->page);
		if ($projectPage instanceof Page && !!$projectPage->id) {
			$output['project_id'] = $projectPage->id;
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

		if ($this->wire('input')->get('htmlOutput')) {
			$output['html'] = $this->renderView();
		}

		return $output;
	}
}
