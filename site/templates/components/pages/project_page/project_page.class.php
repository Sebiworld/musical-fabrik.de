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
			if ($this->main_image->ext == 'svg') {
				$this->main_image_html = $this->getService('ImageService')->getImgHtml(array(
				'image' => $this->main_image,
				'classes' => 'ar-content bg-image',
				'outputType' => 'bg-image',
				'loadAsync' => false,
				'normal' => 'original'
				));
			} else {
				$this->main_image_html =  $this->getService('ImageService')->getImgHtml([
				'image' => $this->main_image,
				'classes' => 'ar-content bg-image',
				'outputType' => 'bg-image',
				'normal' => array(
					'width' => 1800
					),
				'sm' => array(
					'width' => 700
					)
				]);
			}
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
