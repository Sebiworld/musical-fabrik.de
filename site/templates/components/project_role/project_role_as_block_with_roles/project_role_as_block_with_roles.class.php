<?php
namespace ProcessWire;

require_once __DIR__ . '/../project_role_base.class.php';

/**
 * Represents the portraits of the role as a block.
 */
class ProjectRoleAsBlockWithRoles extends ProjectRoleBase {

	public function __construct($args) {
		parent::__construct($args);
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
							'page' => $portrait,
						)));
					}

					$portraits->get('id='.$portrait->id)->addSeasonCastCombination($participant->seasons, $participant->casts, $projectRole);
				}
			}
		}

		return $portraits;
	}

	/**
	 * Returns an HTML string with the output of all subroles.
	 * @return string
	 */
	public function renderSubroles(){
		// No output of the subroles, since these are already included in the view.
		return '';
	}
}
