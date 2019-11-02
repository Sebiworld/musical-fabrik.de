<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

$content = $twack->getComponent('mainContent');
$content->addComponent('EventList', ['directory' => 'partials']);

echo $general->render();
