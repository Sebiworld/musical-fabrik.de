<?php

namespace ProcessWire;

class Filtereinstellungen extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $this->schlagwoerter = '';
        $this->freitextsuche = '';

        if (isset($args['filtereinstellungen'])) {
            // Ist ein Schlagwort-Filter gesetzt?
            if (isset($args['filtereinstellungen']['schlagwoerter'])) {
                $this->schlagwoerter = $args['filtereinstellungen']['schlagwoerter'];
            }

            // Ist etwas bei der Freitextsuche eingetragen?
            if (isset($args['filtereinstellungen']['freitextsuche'])) {
                $this->freitextsuche = $args['filtereinstellungen']['freitextsuche'];
            }
        }

        // Schlagwort-Auswahl hinzufügen:
        $this->addComponent('SchlagwoerterBox', ['directory' => 'bauteile', 'name' => 'schlagwoerter', 'aktiv' => $this->schlagwoerter, 'selektierbar' => true]);
    }
}
