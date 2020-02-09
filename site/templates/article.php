<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
// $general->addStyle('article.css', array(
//     'path'     => wire('config')->urls->templates . 'assets/css/',
//     'absolute' => true
// ));
echo $general->render();
