<?php
namespace ProcessWire;

class PageCard extends TwackComponent {

	public function __construct($args) {
        parent::__construct($args);

		$this->viewType = $this->page->template->name;
		if(!empty($args['viewType'])){
			$this->viewType = $args['viewType'];
        }

        if (isset($args['directory'])) {
            unset($args['directory']);
        }
        $args['logging'] = false;
        $args['throwErrors'] = false;

        $cardComponent = false;
            $cardComponent = $this->addComponent(
            $this->viewType . '_card',
            $args
        );

        if($cardComponent instanceof TwackNullComponent){
            $this->viewType = 'default';
            $this->addComponent(
                $this->viewType . '_card',
                $args
            );
        }

        if(empty($this->classes)){
            $this->classes = '';
        }
        $this->classes .= ' ' . $this->viewType . '_card';

        $this->attributeString = '';
        if(!empty($args['attributes']) && is_array($args['attributes'])){
            $attrParts = [];
            foreach($args['attributes'] as $key => $value){
                $attrParts[] = $key . '="' . $value . '"';
            }

            $this->attributeString = implode(' ', $attrParts);
        }
    }
    
    public function getAjax($ajaxArgs = []) {
        $output = array();

        if ($this->childComponents) {
            foreach ($this->childComponents as $component) {
                $ajax = $component->getAjax($ajaxArgs);
                if (empty($ajax)) {
                    continue;
                }
                $output = array_merge($output, $ajax);
            }
        }

        if($this->wire('input')->get('htmlOutput')){
			$output['html'] = $this->renderView();
		}

        return $output;
    }
}
