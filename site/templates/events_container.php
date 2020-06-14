<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');

$content = wire('twack')->getComponent('mainContent');
$content->addComponent('EventList', ['directory' => 'partials']);

echo $general->render();
