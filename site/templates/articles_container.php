<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$content = $twack->getComponent('mainContent');
$content->addComponent('ContentArticles', ['directory' => 'contents_component', 'title' => '', 'typ' => 'tiles']);

echo $general->render();
