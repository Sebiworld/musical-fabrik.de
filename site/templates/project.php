<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');

$content = wire('twack')->getComponent('mainContent');
$content->mainImage = false;
$content->hideTitle = true;
$content->addComponent('ContentArticles', ['directory' => 'contents_component', 'title' => __('News & Events')]);

echo $general->render();
