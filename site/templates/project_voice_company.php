<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');

$content = wire('twack')->getComponent('mainContent');
$content->mainImage = false;
$content->addComponent('ContentArticles', ['directory' => 'contents_component', 'title' => __('News & Events')]);

echo $general->render();
