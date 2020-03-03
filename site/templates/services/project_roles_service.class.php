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
        if ($this->portraitsContainer->count < 1) {
            throw new ComponentNotInitializedException('ProjectRolesService', 'No matching Portraits-Container were found.');
        }
    }

    /**
     * Returns the roll and bottom rolls with portraits
     * @return StdClass
     */
    public function getProjectRole($projectRolePage = false, $viewType = false, $depth = 2) {
        $firstCall = false;
        if ($projectRolePage === false && $viewType === false) {
            // Oberste Rolle. Wenn kein Anzeigemodus gesetzt ist, soll der des Elternelements genommen werden.
            $elternElement = $this->page->closest('template.name=rollen_container|rolle, project_role_view_options!=""');
            $firstCall     = true;
            if ($elternElement instanceof Page && $elternElement->id && $elternElement->template->hasField('project_role_view_options') && $elternElement->project_role_view_options->name) {
                $viewType = $elternElement->project_role_view_options->name;
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

        $rolesOutput             = array();
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
        $projectRoles = array();
        foreach ($projectRolePage->children('template.name=project_role') as $childRole) {
            $projectRoles[] = $this->getProjectRole($childRole, $viewType, $depth);
        }
        $rolesOutput['projectRoles'] = $projectRoles;

        if ($viewType == 'as_block' || $viewType == 'as_block_with_roles') {
            // All portraits of the role are required for the block display.

            $rolesOutput['portraits'] = new PageArray();
            $rolesOutput['seasons']   = array();
            foreach ($projectRolePage->participants as $participant) {
                if ($participant->type !== 'season' && $participant->type !== 'cast_season') {
                    $rolesOutput['portraits']->add($participant->portraits);
                    continue;
                }

                // This entry applies to a specific scale
                foreach ($participant->seasons as $season) {
                    if (!isset($rolesOutput['seasons'][$season->id]) || !is_array($rolesOutput['seasons'][$season->id])) {
                        $rolesOutput['seasons'][$season->id] = array(
                            'id'        => $season->id,
                            'name'      => $season->name,
                            'title'     => $season->title,
                            'portraits' => new PageArray()
                        );
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
                        $rolesOutput['seasons'][$season->id] = array(
                            'id'        => $season->id,
                            'name'      => $season->name,
                            'title'     => $season->title,
                            'portraits' => new PageArray()
                        );
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

            $rolesOutput['casts'] = array();

            // Collection of all seasons found in the cast:
            $rolesOutput['seasons'] = new PageArray();

            $arePortraitsAvailable = false;
            foreach ($this->getCasts() as $cast) {
                $castArray              = array();
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
     * Returns the available staffing levels
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
