<?php
namespace ProcessWire;

/**
 * Provides methods for reading contributions
 */
class ProjectRolesService extends TwackComponent {
	protected $projectPage;
	protected $portraitsContainer;

	public function __construct($args) {
		parent::__construct($args);

		$this->projectPage = $this->page;
		if ($this->projectPage->template->name != 'project') {
			$this->projectPage = $this->page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
		}

		if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
			$this->projectPage = $args['projectPage'];
		}

		if (!($this->projectPage instanceof Page) || !$this->projectPage->id) {
			$this->projectPage = wire('pages')->get('/');
		}

		$this->portraitsContainer = wire('pages')->find('template.name=portraits_container, include=hidden, has_parent=' . $this->projectPage->id);
	}

	public function getProjectCastAjax($page) {
		if (!($page instanceof Page) || !$page->id) {
			return null; // Skip if cast not found
		}

		if ($page->template->name != 'cast') {
			return null; // Skip if not a cast
		}

		$castOutput = AppApi::getAjaxOf($page);
		if (isset($castOutput['created'])) {
			unset($castOutput['created']);
		}
		if (isset($castOutput['modified'])) {
			unset($castOutput['modified']);
		}
		if (isset($castOutput['template'])) {
			unset($castOutput['template']);
		}

		if (!empty($page['text'])) {
			$castOutput['description'] = $page['text'];
		}

		$castOutput['hash'] = md5(json_encode($castOutput));

		return $castOutput;
	}

	public function getProjectSeasonAjax($page) {
		if (!($page instanceof Page) || !$page->id) {
			return null; // Skip if season not found
		}

		if ($page->template->name != 'season') {
			return null; // Skip if not a season
		}

		$seasonOutput = AppApi::getAjaxOf($page);
		if (isset($seasonOutput['created'])) {
			unset($seasonOutput['created']);
		}
		if (isset($seasonOutput['modified'])) {
			unset($seasonOutput['modified']);
		}
		if (isset($seasonOutput['template'])) {
			unset($seasonOutput['template']);
		}

		$seasonOutput['hash'] = md5(json_encode($seasonOutput));

		return $seasonOutput;
	}

	public function getProjectPortraitAjax($page) {
		if (!($page instanceof Page) || !$page->id) {
			return null; // Skip if portrait not found
		}

		if ($page->template->name != 'portrait') {
			return null; // Skip if not a portrait
		}

		$portraitOutput = AppApi::getAjaxOf($page);

		if (isset($portraitOutput['created'])) {
			unset($portraitOutput['created']);
		}
		if (isset($portraitOutput['modified'])) {
			unset($portraitOutput['modified']);
		}
		if (isset($portraitOutput['template'])) {
			unset($portraitOutput['template']);
		}

		if (!empty($page['main_image'])) {
			$portraitOutput['main_image'] = AppApi::getAjaxOf($page['main_image']);
		}

		if (!empty($page['first_name'])) {
			$portraitOutput['first_name'] = $page['first_name'];

			include __DIR__ . '/../utils/names_male.php';
			include __DIR__ . '/../utils/names_female.php';

			if (!empty($namesMale) || !empty($namesFemale)) {
				$firstNameParts = explode(' ', $page['first_name']);
				foreach ($firstNameParts as $fmPart) {
					if (empty($fmPart) || !is_string($fmPart)) {
						continue;
					}

					$firstNamePartsDash = explode('-', $fmPart);
					foreach ($firstNamePartsDash as $fmPartDash) {
						if (empty($fmPartDash) || !is_string($fmPartDash)) {
							continue;
						}

						if (is_array($namesMale) && in_array(strtolower($fmPartDash), $namesMale)) {
							$portraitOutput['portrait_mode'] = 'male';
							continue 2;
						} else if (is_array($namesFemale) && in_array(strtolower($fmPartDash), $namesFemale)) {
							$portraitOutput['portrait_mode'] = 'female';
							continue 2;
						}
					}
				}
			}
		}

		if (!empty($page['last_name'])) {
			$portraitOutput['last_name'] = $page['last_name'];
		}

		if (!empty($page['title_separable'])) {
			$portraitOutput['title_separable'] = $page['title_separable'];
		}

		if (!empty($page['intro'])) {
			$portraitOutput['intro'] = $page['intro'];
		}

		if (!empty($page['user_accounts'])) {
			$portraitOutput['user_ids'] = [];
			foreach ($page['user_accounts'] as $userAccount) {
				$portraitOutput['user_ids'][] = $userAccount->id;
			}
		}

		$portraitOutput['hash'] = md5(json_encode($portraitOutput));

		return $portraitOutput;
	}

	public function getProjectRoleAjax($projectRole) {
		if (!($projectRole instanceof Page) || !$projectRole->id) {
			return null; // Skip if projectRole not found
		}

		if ($projectRole->template->name != 'project_role' && $projectRole->template->name != 'project_roles_container') {
			return null; // Skip if not a projectRole
		}

		if (!$projectRole->viewable()) {
			return null; // Skip roles that are not viewable
		}

		$output = [
			'roles' => [],
			'seasons' => [],
			'casts' => [],
			'portraits' => [],
		];

		$roleOutput = AppApi::getAjaxOf($projectRole);
		if (isset($roleOutput['created'])) {
			unset($roleOutput['created']);
		}
		if (isset($roleOutput['modified'])) {
			unset($roleOutput['modified']);
		}

		if (!empty($projectRole['headline'])) {
			$roleOutput['headline'] = $projectRole['headline'];
		}

		if (!empty($projectRole['main_image'])) {
			$roleOutput['main_image'] = AppApi::getAjaxOf($projectRole['main_image']);
		}

		if (!empty($projectRole['dont_crop_main_image'])) {
			$roleOutput['dont_crop_main_image'] = $projectRole['dont_crop_main_image'];
		}

		if (!empty($projectRole['text'])) {
			$roleOutput['description'] = $projectRole['text'];
		}

		if (!empty($projectRole['amount'])) {
			$roleOutput['amount'] = $projectRole['amount'];
		}

		if (!empty($projectRole['project_role_view_options']->name)) {
			$roleOutput['view_type'] = $projectRole['project_role_view_options']->name;
		} else {
			$closestRoleWithViewType = $projectRole->closest('template.name=project_roles_container|project_role, project_role_view_options.name!=""');
			if ($closestRoleWithViewType instanceof Page && $closestRoleWithViewType->id && $closestRoleWithViewType->template->hasField('project_role_view_options') && $closestRoleWithViewType->project_role_view_options->name) {
				$roleOutput['view_type'] = $closestRoleWithViewType->project_role_view_options->name;
			}
		}

		if ($projectRole->template->hasField('participants')) {
			$roleOutput['participants'] = [];
			foreach ($projectRole->participants as $participant) {
				if (empty($projectRole->participants)) {
					continue;
				}

				$participantOutput = [
					'portrait_ids' => [],
				];

				foreach ($participant->portraits as $portrait) {
					if (!($portrait instanceof Page) || !$portrait->id) {
						continue; // Skip if portrait not found
					}

					$participantOutput['portrait_ids'][] = $portrait->id;

					if (!isset($output['portraits'][$portrait->id])) {
						$portraitOutput = $this->getProjectPortraitAjax($portrait);

						if (empty($portraitOutput)) {
							continue; // Skip if portrait not found
						}

						$output['portraits'][$portrait->id] = $portraitOutput;
					}
				}

				if (!empty($participant->seasons)) {
					$participantOutput['season_ids'] = [];

					foreach ($participant->seasons as $season) {
						if (!($season instanceof Page) || !$season->id) {
							continue; // Skip if season not found
						}

						$participantOutput['season_ids'][] = $season->id;

						if (!isset($output['seasons'][$season->id])) {
							$seasonOutput = $this->getProjectSeasonAjax($season);
							if (empty($seasonOutput)) {
								continue; // Skip if season not found
							}

							$output['seasons'][$season->id] = $seasonOutput;
						}
					}
				}

				if (!empty($participant->casts)) {
					$participantOutput['cast_ids'] = [];

					foreach ($participant->casts as $cast) {
						if (!($cast instanceof Page) || !$cast->id) {
							continue; // Skip if cast not found
						}

						$participantOutput['cast_ids'][] = $cast->id;

						if (!isset($output['casts'][$cast->id])) {
							$castOutput = $this->getProjectCastAjax($cast);

							if (empty($castOutput)) {
								continue; // Skip if cast not found
							}

							$output['casts'][$cast->id] = $castOutput;
						}
					}
				}

				if (!empty($participant['amount_positions_available'])) {
					$participantOutput['amount_positions_available'] = $participant['amount_positions_available'];
				}


				$roleOutput['participants'][] = $participantOutput;
			}
		}

		$childRoles = $projectRole->children('template.name=project_role');
		if (!empty($childRoles)) {
			$roleOutput['child_ids'] = [];
			foreach ($childRoles as $childRole) {
				if (!($childRole instanceof Page) || !$childRole->id) {
					continue; // Skip if cast not found
				}

				if (!$childRole->viewable()) {
					continue; // Skip roles that are not viewable
				}

				$roleOutput['child_ids'][] = $childRole->id;
			}
		}

		$roleOutput['hash'] = md5(json_encode($roleOutput));

		$output['roles'][$projectRole->id] = $roleOutput;

		return $output;
	}

	public function getProjectRoles($parentPage = false) {
		if (!($parentPage instanceof Page) || !$parentPage->id) {
			$parentPage = $this->projectPage;
		}

		if (!($parentPage instanceof Page) || !$parentPage->id) {
			throw new NotFoundException();
		}

		$output = [
			'roles' => [],
			'seasons' => [],
			'casts' => [],
			'portraits' => [],
			'child_ids' => []
		];

		$roleOutput = $this->getProjectRoleAjax($parentPage);
		if (!empty($roleOutput)) {
			if (is_array($roleOutput['roles'])) {
				foreach ($roleOutput['roles'] as $id => $role) {
					if (!empty($output['roles'][$id])) {
						continue;
					}

					$output['roles'][$id] = $role;
				}
			}

			if (is_array($roleOutput['casts'])) {
				foreach ($roleOutput['casts'] as $id => $cast) {
					if (!empty($output['casts'][$id])) {
						continue;
					}

					$output['casts'][$id] = $cast;
				}
			}

			if (is_array($roleOutput['seasons'])) {
				foreach ($roleOutput['seasons'] as $id => $season) {
					if (!empty($output['seasons'][$id])) {
						continue;
					}

					$output['seasons'][$id] = $season;
				}
			}

			if (is_array($roleOutput['portraits'])) {
				foreach ($roleOutput['portraits'] as $id => $portrait) {
					if (!empty($output['portraits'][$id])) {
						continue;
					}

					$output['portraits'][$id] = $portrait;
				}
			}
		} else {
			$childRoles = $parentPage->children('template.name=project_role');
			if (!empty($childRoles)) {
				foreach ($childRoles as $childRole) {
					if (!($childRole instanceof Page) || !$childRole->id) {
						continue; // Skip if cast not found
					}

					if (!$childRole->viewable()) {
						continue; // Skip roles that are not viewable
					}

					$output['child_ids'][] = $childRole->id;
				}
			}
		}

		foreach ($parentPage->find('template.name=project_role') as $projectRole) {
			if (!($projectRole instanceof Page) || !$projectRole->id) {
				return null; // Skip if projectRole not found
			}

			if (!$projectRole->viewable()) {
				continue; // Skip roles that are not viewable
			}

			$roleOutput = $this->getProjectRoleAjax($projectRole);

			if (empty($roleOutput)) {
				continue; // Skip if role not found
			}

			if (is_array($roleOutput['roles'])) {
				foreach ($roleOutput['roles'] as $id => $role) {
					if (!empty($output['roles'][$id])) {
						continue;
					}

					$output['roles'][$id] = $role;
				}
			}

			if (is_array($roleOutput['casts'])) {
				foreach ($roleOutput['casts'] as $id => $cast) {
					if (!empty($output['casts'][$id])) {
						continue;
					}

					$output['casts'][$id] = $cast;
				}
			}

			if (is_array($roleOutput['seasons'])) {
				foreach ($roleOutput['seasons'] as $id => $season) {
					if (!empty($output['seasons'][$id])) {
						continue;
					}

					$output['seasons'][$id] = $season;
				}
			}

			if (is_array($roleOutput['portraits'])) {
				foreach ($roleOutput['portraits'] as $id => $portrait) {
					if (!empty($output['portraits'][$id])) {
						continue;
					}

					$output['portraits'][$id] = $portrait;
				}
			}
		}

		return $output;
	}

	public function getProjectPortraits($ids = []) {
		$output = [
			'portraits' => [],
			'ids' => $ids
		];

		foreach ($ids as $id) {
			$portrait = wire('pages')->findOne('id=' . $id, ['template.name=portrait']);
			$portraitOutput = $this->getProjectPortraitAjax($portrait);

			if (empty($portraitOutput)) {
				continue; // Skip if portrait not found
			}

			$output['portraits'][$portrait->id] = $portraitOutput;
		}

		$output['hash'] = md5(json_encode($output));

		return $output;
	}

	/**
	 * Returns the role and child roles with portraits
	 * @return StdClass
	 */
	public function getProjectRole($projectRolePage = false, $viewType = false, $depth = 2) {
		$firstCall = false;
		if ($projectRolePage === false && $viewType === false) {
			// Oberste Rolle. Wenn kein Anzeigemodus gesetzt ist, soll der des Elternelements genommen werden.
			$parentPage = $this->page->closest('template.name=rollen_container|rolle, project_role_view_options!=""');
			$firstCall     = true;

			if ($parentPage instanceof Page && $parentPage->id && $parentPage->template->hasField('project_role_view_options') && $parentPage->project_role_view_options->name) {
				$viewType = $parentPage->project_role_view_options->name;
			}
		}

		if ($viewType === false) {
			$viewType = 'as_block';
		}

		// If no role is specified, the current page is used as the base role:
		if (!$projectRolePage || !($projectRolePage instanceof Page) || !$projectRolePage->id) {
			$projectRolePage = $this->page;
		}

		$seasonsContainer = $this->projectPage->get('template.name=seasons_container');

		// Determine display mode for this role. If not explicitly set, the value of the parent element is used.
		if ($projectRolePage->template->hasField('project_role_view_options') && $projectRolePage->project_role_view_options->name) {
			$viewType = $projectRolePage->project_role_view_options->name;
		}

		$rolesOutput             = [];
		$rolesOutput['page']     = $projectRolePage;
		$rolesOutput['viewType'] = $viewType;
		if ($firstCall) {
			$rolesOutput['rootDepth'] = true;
		}

		// Display group picture only: Only this role without portraits is required.
		if ($viewType == 'only_groupimage') {
			return $rolesOutput;
		}

		if ($depth < 1) {
			return $rolesOutput;
		}

		$depth = $depth - 1;
		// If display only underroll teaser: Only the direct child roles anteasern, without portraits:
		if ($viewType == 'subroles_teaser') {
			$depth = 0;
		}
		// else if($viewType == 'as-block-with-roles' && $depth > 1) $depth = 1;

		// Determine child roles and get the settings and portraits for them:
		$projectRoles = [];
		foreach ($projectRolePage->children('template.name=project_role') as $childRole) {
			$projectRoles[] = $this->getProjectRole($childRole, $viewType, $depth);
		}
		$rolesOutput['projectRoles'] = $projectRoles;

		if ($viewType == 'as_block' || $viewType == 'as_block_with_roles') {
			// All portraits of the role are required for the block display.

			$rolesOutput['portraits'] = new PageArray();
			$rolesOutput['seasons']   = [];
			foreach ($projectRolePage->participants as $participant) {
				if ($participant->type !== 'season' && $participant->type !== 'cast_season') {
					$rolesOutput['portraits']->add($participant->portraits);
					continue;
				}

				// This entry applies to a specific scale
				foreach ($participant->seasons as $season) {
					if (!isset($rolesOutput['seasons'][$season->id]) || !is_array($rolesOutput['seasons'][$season->id])) {
						$rolesOutput['seasons'][$season->id] = [
							'id'        => $season->id,
							'name'      => $season->name,
							'title'     => $season->title,
							'portraits' => new PageArray()
						];
					}

					$rolesOutput['seasons'][$season->id]['portraits']->add($participant->portraits);
				}
			}

			// All portraits without belonging to a squadron must be sorted into the squadrons:
			if (!empty($rolesOutput['seasons']) && !empty($rolesOutput['portraits'])) {
				foreach ($rolesOutput['seasons'] as $season) {
					$season['portraits']->add($rolesOutput['portraits']);
				}

				// Add the squadrons that may not yet exist:
				foreach ($seasonsContainer->children('id!=' . implode('|', array_keys($rolesOutput['seasons']))) as $season) {
					if (!isset($rolesOutput['seasons'][$season->id]) || !is_array($rolesOutput['seasons'][$season->id])) {
						$rolesOutput['seasons'][$season->id] = [
							'id'        => $season->id,
							'name'      => $season->name,
							'title'     => $season->title,
							'portraits' => new PageArray()
						];
					}

					$rolesOutput['seasons'][$season->id]['portraits'] = $rolesOutput['portraits'];
				}
			}
		} elseif ($viewType == 'by_cast' || $viewType == 'as_cast_block') {
			// For the display according to staffing, the portraits and the respective staffing page are required for each staffing:
			$portraits = new PageArray();
			foreach ($this->portraitsContainer as $container) {
				$portraits->add(wire('pages')->find('template.name=portrait, sort=nachname, project_roles.project_role.id=' . $projectRolePage->id));
			}

			$rolesOutput['casts'] = [];

			// Collection of all seasons found in the cast:
			$rolesOutput['seasons'] = new PageArray();

			$arePortraitsAvailable = false;
			foreach ($this->getCasts() as $cast) {
				$castArray              = [];
				$castArray['page']      = $cast;
				$castArray['portraits'] = new PageArray();

				if ($cast->seasons instanceof PageArray && $cast->seasons->count > 0) {
					$rolesOutput['seasons']->add($cast->seasons);
				}

				foreach ($portraits as $portrait) {
					$projectRoleInput = $portrait->project_roles->get('project_role=' . $projectRolePage->id);
					if (!($projectRoleInput instanceof Rolle)) {
						continue;
					}
					if (!$projectRoleInput->casts->get('id=' . $cast->id)) {
						continue;
					}
					$castArray['portraits']->add($portrait);
					$arePortraitsAvailable = true;
				}

				$rolesOutput['casts'][] = $castArray;
			}

			if ($rolesOutput['seasons']->count > 0) {
				// Apply sorting as in the page tree:
				$seasonsTmp             = $rolesOutput['seasons'];
				$rolesOutput['seasons'] = $seasonsContainer->children('id=' . implode('|', array_keys($rolesOutput['seasons'])));
			}

			if (!$arePortraitsAvailable && !$firstCall) {
				unset($rolesOutput['casts']);
			}
		}

		return $rolesOutput;
	}

	/**
	 * Returns the available casts
	 * @return PageArray
	 */
	public function getCasts() {
		$container = $this->projectPage->find('template.name=casts_container, include=hidden');
		$pages     = new PageArray();
		if ($container instanceof PageArray && count($container) > 0) {
			foreach ($container as $page) {
				$pages->add($page->children('template.name=cast'));
			}
		}
		return $pages;
	}

	/**
	 * Returns the number of contributors for a role side
	 * @param  Page $projectRolePage
	 * @return
	 */
	public function getParticipantsNumber($projectRolePage = false) {
		if (!($projectRolePage instanceof Page) || !$projectRolePage->id) {
			$projectRolePage = wire('pages')->get('/');
		}

		// Of all roles for which no fixed number has been specified, the corresponding portraits are counted:
		$subrolesWithoutCount = $projectRolePage->find('template.name=project_role, amount=""');
		$amount               = wire('pages')->find('template.name=portrait, project_roles.project_role.parent=' . $subrolesWithoutCount->implode('|', 'id'))->count;

		foreach ($projectRolePage->find('template.name=project_role, amount>0') as $subrolesWithCount) {
			$amount += $subrolesWithCount->amount;
		}

		return $amount;
	}
}
