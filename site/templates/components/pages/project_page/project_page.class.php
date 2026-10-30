<?php

namespace ProcessWire;

/**
 * Applies globally to all project pages (including subpages).
 */
class ProjectPage extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $content            = $this->getComponent('mainContent');

        $this->isProjectPage = false;
        $projectService      = $this->getService('ProjectService');
        $this->projectPage   = $projectService->getProjectPage();

        if (!$projectService->isProjectPage($this->projectPage)) {
            return;
        }

        $this->addGlobalParameters(['projectPage' => $this->projectPage]);
        $this->isProjectPage = true;

        if ($this->projectPage->template->hasField('info_overlay') && !empty($this->projectPage->info_overlay)) {
            $this->infoOverlay = $this->projectPage->info_overlay;
        }
    }

    public function getAjax($ajaxArgs = []) {
        $output = array();

        if (empty($ajaxArgs['showOnly']) || $ajaxArgs['showOnly'] === 'projectinfo') {
            $output['isProjectPage'] = $this->isProjectPage;

            if ($this->isProjectPage) {
                $output['project'] = $this->getAjaxOf($this->projectPage);

                if ($this->projectPage->main_image) {
                    $output['project']['main_image'] = $this->getAjaxOf($this->projectPage->main_image);
                }

                if ($this->projectPage->color) {
                    $output['color'] = $this->projectPage->color;
                }
            }
        }

        if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax($ajaxArgs);
				if(empty($ajax) || !is_array($ajax)) continue;
				$output = array_merge($output, $ajax);
			}
		}

        return $output;
    }
}
