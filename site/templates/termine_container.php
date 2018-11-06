<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

$inhalt = $twack->getComponent('inhalt');
$inhalt->addComponent('TerminListe', ['directory' => 'bauteile']);

echo $general->render();
