<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

$content = $twack->getComponent('mainContent');
if ($content) {
	$content->addComponent('ProjectRole', ['directory' => '']);
} else {
	$general->addComponent('ProjectRole', ['directory' => '']);
}

echo $general->render();
