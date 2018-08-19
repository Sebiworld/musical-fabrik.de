<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$general->addStyle(wire('config')->urls->templates . 'assets/css/beitrag.min.css', true, true);
echo $general->render();
