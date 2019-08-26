<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$general->addStyle('beitrag.css', array(
    'path'     => wire('config')->urls->templates . 'assets/css/',
    'absolute' => true,
    'inline' => true
));
echo $general->render();
