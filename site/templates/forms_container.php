<?php

namespace ProcessWire;

$twack   = wire('modules')->get('Twack');

if (!$twack->isTwackAjaxCall()) {
    throw new WireException('This page can only be called via api', 400);
}

$general = $twack->getNewComponent('General');
$general->resetComponents();

$placeholders        = array();
$forms              = $twack->getComponent('forms');
$form               = $forms->addComponent('FormTemplate', [
    'containerPage'    => wire('page'),
    'placeholders'     => $placeholders,
    'page'             => wire('page')
]);

$general->addComponent($form);

echo $general->render();
