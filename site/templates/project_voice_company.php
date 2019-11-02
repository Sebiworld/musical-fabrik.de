<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

$content = $twack->getComponent('mainContent');
$content->mainImage = false;
$content->addComponent('ContentArticles', ['directory' => 'contents', 'title' => __('News & Events')]);

echo $general->render();
