<?php
namespace ProcessWire;

/*
* Default how a ProjectRoleBase must look like:
* (This way other output variants can be defined instead of bootstrap)
*/
if (!class_exists('Processwire\ProjectRoleBase')) {
	abstract class ProjectRoleBase extends TwackComponent {

		public function __construct($args) {
			parent::__construct($args);

			// Get services that need all the roles:
			$this->imageService = $this->getService('ImageService');
			$this->projectService = $this->getService('ProjectService');
			$this->projectRolesService = $this->getService('ProjectRolesService');
			$this->idService = $this->getService('IdService');

			// Should the title of the role be displayed? The title of the parent role is already displayed with description on the standard page, so the title does not have to be displayed by default.
			$this->showTitle = false;
			if(isset($args['showTitle']) && $args['showTitle']){
				$this->showTitle = true;
			}

			// All lower rollers arranged under this roller:
			$this->subroles = $this->page->children('template.name=project_role');
			if(isset($args['subroles']) && !empty($args['subroles']->id)){
				$this->subroles = $args['subroles'];
			}

			// All available scales in the project:
			$seasonsContainer = $this->projectService->getSeasonsContainer();
			$this->seasons = $seasonsContainer->children();

			// Should a special scale be displayed?
			$this->season = $this->seasons->first();
			if(isset($args['season']) && !empty($args['season']->id)){
				$this->season = $args['season'];
			}

			// All castings that exist in the project:
			$castsContainer = $this->projectService->getCastsContainer();
			$this->allCasts = $castsContainer->children();

			$this->portraits = new WireArray();
			if(!empty($args['portraits']) && $args['portraits'] instanceof WireArray){
				$this->portraits = $args['portraits'];
			}else{
				$this->portraits = $this->getPortraits();
			}
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

		/**
		 * Generates a component for each subrole
		 * @return WireArray<TwackComponent>
		 */
		public function getSubroleComponents(){
			$components = new WireArray();
			foreach($this->unterrollen as $projectRole){
				$components->add($this->getNewComponent(
					'project_role_'.$this->viewType,
					array(
						'directory' => 'project_role',
						'parameters' => $this->getArray(),
						'season' => $this->season,
						'portraits' => $this->portraits->find('season_'.$this->staffel->id.'_project_role_'.$projectRole->id.'=1')
					)
				));
			}
			return $components;
		}

		/**
		 * Returns an HTML string with the output of all subrolls.
		 * @return string
		 */
		public function renderSubroles(){
			$output = '';

			foreach($this->getSubroleComponents() as $component){
				try{
					$output .= $component->render();
				}catch(\Exception $e){}
			}

			// echo "<pre>";
			// var_dump($output);
			// echo "</pre>";
			$output = '';

			return $output;
		}
	}
}
