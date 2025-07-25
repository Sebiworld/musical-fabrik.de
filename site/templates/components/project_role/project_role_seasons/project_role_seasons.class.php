<?php
namespace ProcessWire;

require_once __DIR__ . '/../project_role_base.class.php';

/**
 * Represents the portraits of the role in scales.
 */
class ProjectRoleSeasons extends ProjectRoleBase {
	public function __construct($args) {
		parent::__construct($args);

		if (!isset($args['projectRole']) || !($args['projectRole'] instanceof TwackComponent)) {
			throw new ComponentNotInitializedException('ProjectRoleSeasons', 'No project-role found.');
		}
		$this->projectRole = $args['projectRole'];
	}
}
