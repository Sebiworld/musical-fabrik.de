<?php

require_once(dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php');

// Install SeoMaestroEnhanced, FieldtypeSeoMaestroEnhanced and InputfieldSeoMaestroEnhanced modules.
$wire->wire('modules')->get('SeoMaestroEnhanced');
