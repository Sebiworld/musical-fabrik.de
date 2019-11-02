<?php
namespace ProcessWire;

require_once __DIR__ . '/../project_role_base.class.php';

/**
 * Represents the portraits, sorted by occupations
 */
class ProjectRoleAsCastBlock extends ProjectRoleBase {

	public function __construct($args) {
		parent::__construct($args);
	}
}
