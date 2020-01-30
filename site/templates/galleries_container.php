<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$general->addStyle('galleries-container.css', array(
    'path'     => wire('config')->urls->templates . 'assets/css/',
    'absolute' => true
));

$content = $twack->getComponent('mainContent');
$content->addComponent('ContentGalleries', ['directory' => 'contents_component', 'title' => '']);

echo $general->render();
