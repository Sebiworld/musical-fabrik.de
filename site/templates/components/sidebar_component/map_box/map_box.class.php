<?php

namespace ProcessWire;

class MapBox extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        if (!isset($args['useField']) || empty($args['useField'])) {
            throw new ComponentNotInitializedException('MapBox', $this->_('Please enter the name of the map field that is to be used.'));
        }

        $this->map = $this->page->get($args['useField']);

        $mapModule = wire('modules')->get('FieldtypeMapMarker');
        if (!empty($this->map->address)) {
            $this->addScript('https://maps.googleapis.com/maps/api/js?key=' . $mapModule->get('googleApiKey') . '&callback=initMap', array('absolute' => true));
        }

        $this->address = '';
        if (isset($args['address']) && !empty($args['address'])) {
            $this->address = str_replace(array("\n", "\r"), '', $args['address']);
        }
    }
}
