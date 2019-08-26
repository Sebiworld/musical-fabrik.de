<?php

namespace ProcessWire;

$twack   = wire('modules')->get('Twack');

if(!$twack->isTwackAjaxCall()){
    throw new WireException("This page can only be called via api", 400);
}

$general = $twack->getNewComponent('General');
$general->resetComponents();

$platzhaltertexte       = array();
$formulare              = $twack->getComponent('formulare');
$formular               = $formulare->addComponent('TemplateFormular', [
    'containerSeite'   => wire('page'),
    'platzhaltertexte' => $platzhaltertexte,
    'page'             => wire('page')
]);

$general->addComponent($formular);

echo $general->render();
