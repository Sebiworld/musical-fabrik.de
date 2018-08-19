<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$general->addStyle(wire('config')->urls->templates . 'assets/css/beitraege-uebersicht.min.css', true, true);

$inhalt = $twack->getComponent('inhalt');
$inhalt->addComponent('AktuellesInhalte', ['directory' => 'inhalte', 'titel' => '', 'typ' => 'kacheln']);

echo $general->render();
