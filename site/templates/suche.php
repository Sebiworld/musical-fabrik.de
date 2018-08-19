<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

$inhaltsKomponente = $twack->getComponent('inhalt');
if ($inhaltsKomponente) {
	$inhaltsKomponente->addComponent('Suche', ['directory' => 'seiten']);
} else {
	$general->addComponent('Suche', ['directory' => 'seiten']);
}

echo $general->render();
