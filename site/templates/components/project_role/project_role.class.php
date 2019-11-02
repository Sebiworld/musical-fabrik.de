<?php
namespace ProcessWire;

require_once __DIR__ . '/project_role_base.class.php';

/**
 * Represents a role (template role_container or role).
 * Picks out the portraits for this role and the subroles. Generates a relay container if necessary.
 */
class ProjectRole extends ProjectRoleBase {

	const DEFAULT_VIEWMODE = 'as_block';

	public function __construct($args) {
		parent::__construct($args);

		$this->viewType = false;
		if(!empty($args['viewType'])){
			$this->viewType = $args['viewType'];
		}else if(!empty($this->page->project_role_view_options)){
			$this->viewType = $this->page->project_role_view_options->name;
		}else{
			//  If no viewtype is set, the one of the parent element should be used.
			$parentElement = $this->page->closest('template.name=project_roles_container|project_role, project_role_view_options!=""');
			if ($parentElement instanceof Page && $parentElement->id && $parentElement->template->hasField('project_role_view_options') && $parentElement->project_role_view_options->name) {
				$this->viewType = $parentElement->project_role_view_options->name;
			}
		}

		if(!$this->viewType){
			$this->viewType = SELF::DEFAULT_VIEWMODE;
		}

		$rolesComponent = $this->getNewComponent(
			'project_role_'.$this->viewType,
			array(
				'parameters' => $this->getArray(),
				'season' => $this->season
			)
		);
		$this->output = $rolesComponent;

		if($this->portraits->has('seasons.count<' . $this->seasons->count())){
			// There are portraits that are not assigned to all seasons. Activate season output:
			$this->output = $this->getNewComponent(
				'ProjectRoleSeasons',
				array(
					'projectRole' => $rolesComponent,
					'parameters' => $this->getArray(),
					'viewType' => $this->viewType,
					'season' => $this->season,
					'portraits' => $this->portraits
				)
			);
		}

		$this->addStyle('project_role.css', array(
            'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true,
			'inline' => true
        ));
	}

	/**
	 * Returns all portraits of the subroles
	 * @return PageArray
	 */
	public function getPortraits(){
		$portraits = new WireArray();

		foreach ($this->page->participants as $participant) {
			foreach($participant->portraits as $portrait){
				if(!$portraits->has('id='.$portrait->id)){
					$portraits->add($this->getNewComponent('Portrait', array(
						'directory' => 'project_role',
						'page' => $portrait,
						'root' => true
					)));
				}

				$portraits->get('id='.$portrait->id)->addSeasonCastCombination($participant->seasons, $participant->casts, $this->page);
			}
		}

		foreach($this->subroles as $projectRole){
			foreach ($projectRole->participants as $participant) {
				foreach($participant->portraits as $portrait){
					if(!$portraits->has('id='.$portrait->id)){
						$portraits->add($this->getNewComponent('Portrait', array(
							'directory' => 'project_role',
							'page' => $portrait
						)));
					}

					$portraits->get('id='.$portrait->id)->addSeasonCastCombination($participant->seasons, $participant->casts, $projectRole);
				}
			}
		}

		return $portraits;
	}
}
