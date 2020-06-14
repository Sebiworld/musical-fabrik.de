<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');
$general->resetComponents();
$general->addComponent('HomePage', ['directory' => 'pages']);
echo $general->render();
