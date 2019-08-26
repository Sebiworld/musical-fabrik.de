<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$general->addStyle('beitraege-uebersicht.css', array(
    'path'     => wire('config')->urls->templates . 'assets/css/',
    'absolute' => true,
    'inline' => true
));

$inhalt = $twack->getComponent('inhalt');
$inhalt->addComponent('AktuellesInhalte', ['directory' => 'inhalte', 'titel' => '', 'typ' => 'kacheln']);

echo $general->render();
