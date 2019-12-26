<?php
namespace ProcessWire;

/**
 * Applies globally to all project pages (including subpages).
 */
class ProjectPage extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->isProjectPage = false;
		$this->projectPage = $this->page;
		if ($this->projectPage->template->name !== 'project') {
			$this->projectPage = $this->page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
		}
		if (!($this->projectPage instanceof Page) || !($this->projectPage->id.'')) {
			return;
		}

		$this->addGlobalParameters(['projectPage' => $this->projectPage]);
		$this->isProjectPage = true;

		if ($this->projectPage->template->hasField('info_overlay') && !empty($this->projectPage->info_overlay)) {
			$this->infoOverlay = $this->projectPage->info_overlay;
		}

		if ($this->projectPage->main_image) {
			$this->main_image = $this->projectPage->main_image;
			$this->main_image_html = $this->getService('ImageService')->getImgHtml(array(
				'image' => $this->main_image,
				'classes' => array('ar-content'),
				'outputType' => 'image',
				'loadAsync' => true,
				'default' => array(
					'width' => 800
				),
				'srcset' => array(
					'320w' => array(
						'width' => 320
					),
					'640w' => array(
						'width' => 640
					),
					'720w' => array(
						'width' => 720
					),
					'800w' => array(
						'width' => 800
					),
					'960w' => array(
						'width' => 960
					),
					'1600w' => array(
						'width' => 1600
					),
					'2000w' => array(
						'width' => 2000
					),
					'2400w' => array(
						'width' => 2400
					)
				)
			));
		}

		// Include CSS file for the project
		$this->loadProjectCss($this->projectPage->name);

		// Sidebar components:
		$sidebar = $this->getGlobalComponent('sidebar');

		// Images slider:
		$sidebar->addComponent('ImagesBox');

		// Information about the performances:
		$sidebar->addComponent('EventsBox');

		// Share Buttons:
		$sidebar->addComponent('SharingBox');

		// General data:
		$sidebar->addComponent('GeneralDataBox');

		// Partners:
		$sidebar->addComponent('SponsorsBox', ['title' => $this->_('Our Partners'), 'useField' => 'partners']);

		//Sponsors:
		$sidebar->addComponent('SponsorsBox', ['title' => $this->_('Our Sponsors'), 'useField' => 'sponsors']);
	}

	/**
	 * Includes a CSS file (if available)
	 */
	public function loadProjectCss($cssName) {
		$cssPath = 'assets/css/'. Twack::getManifestFilename($cssName.'.css');
		
		if (file_exists(wire('config')->paths->templates . $cssPath)) {
			$this->addStyle($cssName.'.css', array(
				'path'     => wire('config')->urls->templates . 'assets/css/',
				'absolute' => true
			));
			return true;
		}
		return false;
	}

	public function getAjax() {
		$output = array(
			'isProjectPage' => $this->isProjectPage
		);

		if($this->isProjectPage){
			$output['project'] = $this->getAjaxOf($this->projectPage);

			if($this->projectPage->main_image){
				$output['project']['main_image'] = $this->getAjaxOf($this->projectPage->main_image);
			}

			if($this->projectPage->color){
				$output['color'] = $this->projectPage->color;
			}
		}

		return $output;
	}
}
