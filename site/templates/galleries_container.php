<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');

$content = wire('twack')->getComponent('mainContent');
$content->addComponent('ContentGalleries', ['directory' => 'contents_component', 'title' => '']);

echo $general->render();
