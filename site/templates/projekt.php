<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

// Das Titelbild wird schon an anderer Stelle ausgespielt, und darf deshalb auf der Projektseite nicht mehr ausgegeben werden:
$inhalt = $twack->getComponent('inhalt');
$inhalt->titelbild = false;
$inhalt->addComponent('AktuellesInhalte', ['directory' => 'inhalte', 'titel' => 'Neuigkeiten']);

echo $general->render();
