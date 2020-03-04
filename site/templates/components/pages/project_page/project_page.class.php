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

        if ($this->projectPage->main_image) {
            $this->main_image      = $this->projectPage->main_image;
            $this->main_image_html = $this->getService('ImageService')->getPictureHtml(array(
                'image'          => $this->main_image,
                'alt'            => sprintf($this->_('Logo of %1$s'), $this->projectPage->title),
                'pictureclasses' => array('ar-content'),
                'loadAsync'      => true,
                'default'        => array(
                    'width' => 800
                ),
                'media' => array(
                    '(max-width: 500px)' => array(
                        'width' => 500
                    ),
                    '(min-width: 1200px)' => array(
                        'width' => 1200
                    )
                )
            ));
        }

        // Sidebar components:
        $sidebar = $this->getGlobalComponent('sidebar');

        // General data:
        $sidebar->addComponent('GeneralDataBox');

        // Images slider:
        $sidebar->addComponent('ImagesBox');

        // Information about the performances:
        $sidebar->addComponent('EventsBox');

        // Share Buttons:
        $sidebar->addComponent('SharingBox');

        // Partners:
        $sidebar->addComponent('SponsorsBox', ['title' => $this->_('Our Partners'), 'useField' => 'partners']);

        //Sponsors:
        $sidebar->addComponent('SponsorsBox', ['title' => $this->_('Our Sponsors'), 'useField' => 'sponsors']);

        // Include CSS file for the project
        $this->loadProjectCss($this->projectPage->name);
    }

    /**
     * Includes a CSS file (if available)
     */
    public function loadProjectCss($cssName) {
        $cssPath = 'assets/css/' . Twack::getManifestFilename($cssName . '.css');

        if (file_exists(wire('config')->paths->templates . $cssPath)) {
            $this->addStyle($cssName . '.css', array(
                'path'     => wire('config')->urls->templates . 'assets/css/',
                'absolute' => true
            ));
            return true;
        }
        return false;
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
