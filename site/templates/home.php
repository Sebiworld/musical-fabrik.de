<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$general->resetComponents();
$general->addComponent('Startseite', ['directory' => 'seiten']);
echo $general->render();
