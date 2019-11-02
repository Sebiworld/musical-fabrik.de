<?php
namespace ProcessWire;


class Portrait extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if($this->page->template->name !== 'portrait'){
			throw new ComponentNotInitializedException("No valid portrait page was transferred.");
		}

		$this->root = false;
		if(isset($args['root']) && $args['root']){
			$this->root = true;
		}

		$this->imageService = $this->getService('ImageService');
		$this->projectService = $this->getService('ProjectService');

		// Read attributes from the page that can be used to sort the WireArray:
		$this->setArray($this->page->getArray());
		$this->id = $this->page->id;
		$this->name = $this->page->name;
		$this->title = $this->page->title;

		$this->seasons = new WireArray();
		$this->projectRoles = new WireArray();

		// All the seasons in the project:
		$seasonsContainer = $this->projectService->getSeasonsContainer();
		$this->allSeasons = $seasonsContainer->children();

		// All castings that exist in the project:
		$castsContainer = $this->projectService->getCastsContainer();
		$this->allCasts = $castsContainer->children();

		// TODO: Popover at click with detailed information about the portrait
	}

	public function addSeasonCastCombination($seasons, $casts, $projectRoles = false){
		// If no season page array is passed, the role applies to all seasons:
		if(!($seasons instanceof PageArray)){
			$seasons = $this->allSeasons;
		}

		// If no cast page array is passed, the role applies to all casts:
		if(!($casts instanceof PageArray)){
			$casts = $this->allCasts;
		}

		if($projectRoles instanceof Page && $projectRoles->id){
			$tmpRole = $projectRoles;
			$projectRoles = new PageArray();
			$projectRoles->add($tmpRole);
		}elseif(!($projectRoles instanceof PageArray)){
			$projectRoles = new PageArray();
		}

		foreach($seasons as $seasonPage){
			// Sub-selectors don't work on elements,
			// which are only present in the memory. In order to use sub-selectors
			//, they should be stored in the DB. Therefore
			// The casts and seasons are stored at the first level.
			$this->{'season_'.$seasonPage->id} = 1;

			if(!$this->seasons->has('id=' . $seasonPage->id)){
				$season = new WireData();
				$season->setArray($seasonPage->getArray());
				$season->id = $seasonPage->id;
				$season->name = $seasonPage->name;
				$season->title = $seasonPage->title;
				$season->casts = new WireArray();
				$this->seasons->add($season);
			}

			foreach($casts as $castPage){
				$this->{'cast_'.$castPage->id} = 1;
				$this->{'season_'.$seasonPage->id.'_'.$castPage->id} = 1;

				if(!$this->seasons->get('id=' . $seasonPage->id)->casts->has('id='.$castPage->id)){
					$cast = new WireData();
					$cast->setArray($castPage->getArray());
					$cast->id = $castPage->id;
					$cast->name = $castPage->name;
					$cast->title = $castPage->title;
					$cast->projectRoles = new WireArray();
					$this->seasons->get('id=' . $seasonPage->id)->casts->add($cast);

					if(!($this->{'season_'.$seasonPage->id.'_casts'} instanceof WireArray)){
						$this->{'season_'.$seasonPage->id.'_casts'} = new WireArray();
					}
					$this->{'season_'.$seasonPage->id.'_casts'}->add($cast);
				}

				// Add roles to the cast:
				foreach($projectRoles as $projectRolePage){
					$this->{'project_role_'.$projectRolePage->id} = 1;
					$this->{'season_'.$seasonPage->id.'_'.$castPage->id.'_'.$projectRolePage->id} = 1;
					$this->{'season_'.$seasonPage->id.'_project_role_'.$projectRolePage->id} = 1;
					$this->{'cast_'.$castPage->id.'_project_role_'.$projectRolePage->id} = 1;

					if($this->seasons->get('id=' . $seasonPage->id)->casts->get('id='.$castPage->id)->projectRoles->has('id='.$projectRolePage->id)){
						continue;
					}
					$projectRole = new WireData();
					$projectRole->setArray($projectRolePage->getArray());
					$projectRole->id = $projectRolePage->id;
					$projectRole->name = $projectRolePage->name;
					$projectRole->title = $projectRolePage->title;
					$this->seasons->get('id=' . $seasonPage->id)->casts->get('id='.$castPage->id)->projectRoles->add($projectRole);

					if(!($this->{'season_'.$seasonPage->id.'_'.$castPage->id.'_project_roles'} instanceof WireArray)){
						$this->{'season_'.$seasonPage->id.'_'.$castPage->id.'_project_roles'} = new WireArray();
					}
					$this->{'season_'.$seasonPage->id.'_'.$castPage->id.'_project_roles'}->add($projectRole);

					if(!($this->{'season_'.$seasonPage->id.'_project_roles'} instanceof WireArray)){
						$this->{'season_'.$seasonPage->id.'_project_roles'} = new WireArray();
					}
					$this->{'season_'.$seasonPage->id.'_project_roles'}->add($projectRole);

					if(!($this->{'cast_'.$castPage->id.'_project_roles'} instanceof WireArray)){
						$this->{'cast_'.$castPage->id.'_project_roles'} = new WireArray();
					}
					$this->{'cast_'.$castPage->id.'_project_roles'}->add($projectRole);
				}
			}
		}
	}

	public function renderWithSubtitle($subtitle){
		$this->subtitle = $subtitle;
		return parent::render();
	}

	public function render(){
		$this->subtitle = false;
		return parent::render();
	}
}