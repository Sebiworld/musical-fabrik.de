<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

$content = $twack->getComponent('mainContent');
if ($content) {
	$content->addComponent('SearchPage', ['directory' => 'pages']);
} else {
	$general->addComponent('SearchPage', ['directory' => 'pages']);
}

echo $general->render();