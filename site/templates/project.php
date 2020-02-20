<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');

$content = $twack->getComponent('mainContent');
$content->mainImage = false;
$content->hideTitle = true;
$content->addComponent('ContentArticles', ['directory' => 'contents_component', 'title' => __('News & Events')]);

echo $general->render();
