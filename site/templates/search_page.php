<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');

$content = wire('twack')->getComponent('mainContent');
if ($content) {
	$content->addComponent('SearchPage', ['directory' => 'pages']);
} else {
	$general->addComponent('SearchPage', ['directory' => 'pages']);
}

echo $general->render();