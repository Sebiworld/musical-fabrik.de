<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

$inhaltsKomponente = $twack->getComponent('inhalt');
if ($inhaltsKomponente) {
	$inhaltsKomponente->addComponent('ProjektRolle', ['directory' => '']);
} else {
	$general->addComponent('ProjektRolle', ['directory' => '']);
}

echo $general->render();
