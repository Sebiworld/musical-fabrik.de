<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');

$content = wire('twack')->getComponent('mainContent');
if ($content) {
	$content->addComponent('ProjectRole', ['directory' => '']);
} else {
	$general->addComponent('ProjectRole', ['directory' => '']);
}

echo $general->render();
