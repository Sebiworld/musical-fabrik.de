<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$general->resetComponents();
$general->addComponent('HomePage', ['directory' => 'pages']);
echo $general->render();
