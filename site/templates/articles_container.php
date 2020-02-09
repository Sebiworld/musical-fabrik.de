<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
// $general->addStyle('articles-container.css', array(
//     'path'     => wire('config')->urls->templates . 'assets/css/',
//     'absolute' => true
// ));

$content = $twack->getComponent('mainContent');
$content->addComponent('ContentArticles', ['directory' => 'contents_component', 'title' => '', 'typ' => 'tiles']);

echo $general->render();
