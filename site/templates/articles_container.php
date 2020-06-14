<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');
$content = wire('twack')->getComponent('mainContent');
$content->addComponent('ContentArticles', ['directory' => 'contents_component', 'title' => '', 'typ' => 'tiles']);

echo $general->render();
