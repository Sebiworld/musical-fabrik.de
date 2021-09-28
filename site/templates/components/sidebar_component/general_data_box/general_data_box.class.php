<?php

namespace ProcessWire;

class GeneralDataBox extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $this->dataCollection = new WireArray();

        $projectPage = $this->getGlobalParameter('projectPage');
        if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
            $projectPage = $args['projectPage'];
        }
        if (!($projectPage instanceof Page) || !$projectPage->id) {
            throw new ComponentNotInitializedException('GeneralDataBox', $this->_('No project page was found.'));
        }

        $this->title = $this->_('Project facts');
        if (isset($args['title']) && !empty($args['title'])) {
            $this->title = str_replace(array("\n", "\r"), '', $args['title']);
        }

        if($projectPage->template->hasField('infos') && $projectPage->infos->count > 0){
            foreach($projectPage->infos as $info){
                $this->addData($info->short_html, $info->short_html2, $info->link, $info->title, $info->depth);
            }
        }

        // Number of participants:
        $projectRolesPage = $projectPage->get('template.name=project_roles_container');

        if ($projectRolesPage->id && $projectRolesPage->viewable()) {
            // Of all roles for which no fixed number has been specified, the corresponding portraits are counted:
            $this->numberOfParticipants = 0;
            $portraits          = new PageArray();
            foreach ($projectRolesPage->find('template.name=project_role') as $projectRole) {
                if (!empty($projectRole->amount)) {
                    $this->numberOfParticipants += $projectRole->amount;
                    continue;
                }

                foreach ($projectRole->participants as $participant) {
                    $portraits->add($participant->portraits);
                }
            }

            $this->numberOfParticipants += wireCount($portraits);

            if ($this->numberOfParticipants > 0) {
                $roleText = sprintf($this->_('%1$s Participants!'), $this->numberOfParticipants);
                if (10 * floor($this->numberOfParticipants / 10) > 0) {
                    // Total number, rounded down to 10:
                    $this->numberOfParticipants = 10 * floor($this->numberOfParticipants / 10);
                    $roleText         = sprintf($this->_('More than %1$s Participants!'), $this->numberOfParticipants);
                }
                $this->addData('', $roleText);

                foreach ($projectRolesPage->children('template.name=project_role') as $projectRole) {
                    if($projectRole->viewable()){
                        $this->addData($projectRole->title, '', $projectRole->url, $projectRole->title, 1);
                    }else{
                        $this->addData($projectRole->title, '', '', $projectRole->title, 1);
                    }
                }
            }
        }
    }

    public function addData($name, $value = '', $link = '', $linktitle = '', $depth = 0) {
        $datensatz        = new WireData();
        $datensatz->label = '';

        if (!empty($name)) {
            $datensatz->name  = $name;
            $datensatz->label = $name;
        }
        if (!empty($value)) {
            if (!empty($datensatz->label)) {
                $datensatz->label .= ': ';
            }
            $datensatz->value = $value;
            $datensatz->label .= $value;
        }
        if (!empty($link)) {
            $datensatz->link = $link;
        }
        if (!empty($linktitle)) {
            $datensatz->linktitle = $linktitle;
        }
        $datensatz->depth = $depth;

        $this->dataCollection->add($datensatz);
    }
}
