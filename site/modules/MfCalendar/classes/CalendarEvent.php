<?php
namespace ProcessWire;

class CalendarEvent extends WireData {
	private $initialized = false;

	public function __construct(array $data = []) {
		$this->set('id', null);
		$this->set('title', '');
		$this->set('created', time());
		$this->set('createdUser', null);
		$this->set('modified', time());
		$this->set('modifiedUser', null);
		$this->set('status', null);
		$this->set('description', '');
		$this->set('linkedPage', null);
		$this->set('project', null);
		$this->set('timespans', null);

		if (is_array($data) && wireCount($data) > 0) {
			$this->initializeWithArray($data);
		}

		if ($this->isNew()) {
			$this->set('created', time());
			$this->set('createdUser', $this->wire('user'));
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}
		$this->initialized = true;
	}

	protected function initializeWithArray(array $values) {
		if (!isset($values['id'])) {
			throw new \Exception('You cannot initialize an event without an id.');
		}
		$this->id = (int)$values['id'];

		if (isset($values['title'])) {
			$this->___setTitle($values['title']);
		}

		if (isset($values['created'])) {
			$this->___setCreated($values['created']);
		}
		if (isset($values['created_user'])) {
			$this->___setCreatedUser($values['created_user']);
		}

		if (isset($values['modified'])) {
			$this->___setModified($values['modified']);
		}
		if (isset($values['modified_user'])) {
			$this->___setModifiedUser($values['modified_user']);
		}

		if (isset($values['status'])) {
			$this->___setStatus($values['status']);
		}

		if (isset($values['description'])) {
			$this->___setDescription($values['description']);
		}

		if (isset($values['linked_page'])) {
			$this->___setLinkedPage($values['linked_page']);
		}

		if (isset($values['project'])) {
			$this->___setProject($values['project']);
		} else if (isset($values['project_id'])) {
			$this->___setProject($values['project_id']);
		}
	}

	public function ___importData($data) {
		if (is_object($data)) {
			if (isset($data->title)) {
				$this->setTitle($data->title);
			}
			if (isset($data->status)) {
				$this->setStatus($data->status);
			}
			if (isset($data->description)) {
				$this->setDescription($data->description);
			}
			if (isset($data->project)) {
				$this->setProject($data->project);
			} else if (isset($data->project_id)) {
				$this->setProject($data->project_id);
			}
			if (isset($data->timespans) && is_array($data->timespans)) {
				$this->setTimespans($data->timespans);
			}
		}
	}

	public function ___isValid() {
		return $this->isIDValid() && $this->isTitleValid() && $this->isCreatedValid() && $this->isCreatedUserValid() && $this->isModifiedValid() && $this->isModifiedUserValid() && $this->isStatusValid() && $this->isDescriptionValid() && $this->isLinkedPageValid() && $this->isProjectValid() && $this->areTimespansValid();
	}

	public function ___isNew() {
		return empty($this->id);
	}

	public function getID() {
		return $this->id;
	}

	public function isIDValid($value = false) {
		if ($value === false) {
			$value = $this->id;
		}
		return $value === null || (is_integer($value) && $value >= 0);
	}

	public function ___setTitle($title) {
		$title = $this->sanitizer->text($title);

		if (!$this->isTitleValid($title)) {
			throw new \Exception('No valid title');
		}

		if ($this->title === $title) {
			return $this->title;
		}

		$this->set('title', $title);
		if ($this->initialized) {
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}

		return $this->title;
	}

	public function isTitleValid($value = false) {
		if ($value === false) {
			$value = $this->title;
		}
		return is_string($value);
	}

	public function getTitle() {
		return $this->title;
	}

	public function ___setCreated($created) {
		if (is_string($created)) {
			$created = strtotime($created);
		}

		if (!$this->isCreatedValid($created)) {
			throw new \Exception('No valid modified date');
		}

		if ($this->created === $created) {
			return $this->created;
		}

		$this->set('created', $created);
		return $this->created;
	}

	public function isCreatedValid($value = false) {
		if ($value === false) {
			$value = $this->created;
		}
		return is_integer($value) && $value > 0;
	}

	public function getCreated() {
		return $this->created;
	}

	public function ___setCreatedUser($createdUser) {
		if (!$createdUser instanceof User || !$createdUser->id) {
			$createdUser = wire('users')->get($createdUser);
		}

		if (!$this->isCreatedUserValid($createdUser)) {
			throw new \Exception('No valid user');
		}

		if ($this->createdUser instanceof User && $this->createdUser->id && $this->createdUser->id === $createdUser->id) {
			return $this->createdUser;
		}

		$this->set('createdUser', $createdUser);
		return $this->createdUser;
	}

	public function isCreatedUserValid($value = false) {
		if ($value === false) {
			$value = $this->createdUser;
		}
		return $value instanceof User && $value->id;
	}

	public function getCreatedUser() {
		if (!$this->isCreatedUserValid()) {
			return wire('users')->getGuestUser();
		}
		return $this->createdUser;
	}

	public function getCreatedUserLink() {
		$createdUser = $this->getCreatedUser();
		$createdUserString = $createdUser->name . ' (' . $createdUser->id . ')';
		if ($createdUser->editable()) {
			$createdUserString = '<a href="' . $createdUser->editUrl . '" target="_blank">' . $createdUserString . '</a>';
		}
		return $createdUserString;
	}

	public function ___setModified($modified) {
		if (is_string($modified)) {
			$modified = strtotime($modified);
		}

		if (!$this->isModifiedValid($modified)) {
			throw new \Exception('No valid modified date');
		}

		if ($this->modified === $modified) {
			return $this->modified;
		}

		$this->set('modified', $modified);
		return $this->modified;
	}

	public function isModifiedValid($value = false) {
		if ($value === false) {
			$value = $this->modified;
		}
		return is_integer($value) && $value > 0;
	}

	public function getModified() {
		return $this->modified;
	}

	public function ___setModifiedUser($modifiedUser) {
		if (!$modifiedUser instanceof User || !$modifiedUser->id) {
			$modifiedUser = wire('users')->get($modifiedUser);
		}

		if (!$this->isModifiedUserValid($modifiedUser)) {
			throw new \Exception('No valid user');
		}

		if ($this->modifiedUser instanceof User && $this->modifiedUser->id && $this->modifiedUser->id === $modifiedUser->id) {
			return $this->modifiedUser;
		}

		$this->set('modifiedUser', $modifiedUser);
		return $this->modifiedUser;
	}

	public function isModifiedUserValid($value = false) {
		if ($value === false) {
			$value = $this->modifiedUser;
		}
		return $value instanceof User && $value->id;
	}

	public function getModifiedUser() {
		if (!$this->isModifiedUserValid()) {
			return wire('users')->getGuestUser();
		}
		return $this->modifiedUser;
	}

	public function getModifiedUserLink() {
		$modifiedUser = $this->getModifiedUser();
		$modifiedUserString = $modifiedUser->name . ' (' . $modifiedUser->id . ')';
		if ($modifiedUser->editable()) {
			$modifiedUserString = '<a href="' . $modifiedUser->editUrl . '" target="_blank">' . $modifiedUserString . '</a>';
		}
		return $modifiedUserString;
	}

	public function ___setStatus($status) {
		if (!$this->isStatusValid($status)) {
			throw new \Exception('No valid status');
		}

		if ($this->status === $status) {
			return $this->status;
		}

		$this->set('status', $this->sanitizer->text($status));
		if ($this->initialized) {
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}
		return $this->status;
	}

	public function isStatusValid($value = false) {
		if ($value === false) {
			$value = $this->status;
		}
		return !empty($value);
	}

	public function getStatus() {
		return $this->status;
	}

	public function ___setDescription($description) {
		$description = $this->sanitizer->purify($description);

		if (!$this->isDescriptionValid($description)) {
			throw new \Exception('No valid description');
		}

		if ($this->description === $description) {
			return $this->description;
		}

		$this->set('description', $description);
		if ($this->initialized) {
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}
		return $this->description;
	}

	public function isDescriptionValid($value = false) {
		if ($value === false) {
			$value = $this->description;
		}
		return is_string($value);
	}

	public function getDescription() {
		return $this->description;
	}

	public function ___setLinkedPage($linkedPage) {
		if (!$linkedPage instanceof Page || !$linkedPage->id) {
			$linkedPage = wire('pages')->findOne('id=' . $linkedPage);
		}
		if (!$this->isLinkedPageValid($linkedPage)) {
			throw new \Exception('No linked page');
		}

		if ($this->linkedPage instanceof Page && $this->linkedPage->id && $this->linkedPage->id === $linkedPage->id) {
			return $this->linkedPage;
		}

		$this->set('linkedPage', $linkedPage);
		if ($this->initialized) {
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}
		return $this->linkedPage;
	}

	public function isLinkedPageValid($value = false) {
		if ($value === false) {
			$value = $this->linkedPage;
		}
		return $value === null || ($value instanceof Page && $value->id);
	}

	public function getLinkedPage() {
		if (!$this->isLinkedPageValid()) {
			return null;
		}
		return $this->linkedPage;
	}

	public function ___setProject($project) {
		if (empty($project)) {
			$project = null;
		} else if (!$project instanceof Page || !$project->id || $project->template->name !== 'project') {
			$project = wire('pages')->findOne('id=' . $project . ',template.name=project');
		}

		if (!$this->isProjectValid($project)) {
			throw new \Exception('No valid project');
		}

		if ($this->project instanceof Page && $this->project->id && $this->project->id === $project->id) {
			return $this->project;
		}

		$this->set('project', $project);
		if ($this->initialized) {
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}
		return $this->project;
	}

	public function isProjectValid($value = false) {
		if ($value === false) {
			$value = $this->project;
		}
		return $value === null || ($value instanceof Page && $value->id && $value->template->name === 'project');
	}

	public function getProject() {
		if (!$this->isProjectValid()) {
			return null;
		}
		return $this->project;
	}

	public function ___setTimespans($timespans) {
		if (!($timespans instanceof WireArray)) {
			$newTimespans = new WireArray();
			if (is_array($timespans)) {
				foreach ($timespans as $timespan) {
					$id = null;
					if (!empty($timespan->id)) {
						$id = wire('sanitizer')->intUnsigned($timespan->id);
					}

					$item = new CalendarTimespan();
					if ($id) {
						try {
							$item = CalendarTimespan::getById($id);
						} catch (Exception $e) {
							throw new NotFoundException('Timespan not found', 404, [
								'errorcode' => 'timespan_not_found',
								'data' => $timespan
							]);
						}
					}
					$item->importData($timespan);
					$newTimespans->add($item);
				}
			}
			$timespans = $newTimespans;
		}

		if (!$this->areTimespansValid($timespans)) {
			throw new \Exception('No valid timespans');
		}

		$this->set('timespans', $timespans);
		return $this->timespans;
	}

	public function ___areTimespansValid($values = false) {
		if ($values === false) {
			$values = $this->timespans;
		}
		if (!($values instanceof WireArray) || wireCount($values) <= 0) {
			return false;
		}

		foreach ($values as $value) {
			if (!($value instanceof CalendarTimespan)) {
				return false;
			}
			if (!$value->isValid()) {
				return false;
			}
		}

		return true;
	}

	public function ___getTimespans() {
		if (!$this->areTimespansValid()) {
			return new WireArray();
		}
		return $this->timespans;
	}

	private function restoreTimespans() {
		if ($this->isNew()) {
			return new WireArray();
		}

		$timespans = new WireArray();
		$db = wire('database');
		$query = $db->prepare('SELECT * FROM ' . MfCalendar::tableTimespans . ' WHERE `event_id`=:event_id;');
		$query->closeCursor();
		$query->execute([
			':event_id' => $this->getID()
		]);
		$queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);

		foreach ($queueRaw as $queueItem) {
			if (!isset($queueItem['id']) || empty($queueItem['id'])) {
				continue;
			}

			try {
				$timespan = new CalendarTimespan($queueItem);
				if ($timespan->isValid()) {
					$timespans->add($timespan);
				}
			} catch (\Exception $e) {
			}
		}

		$this->timespans = $timespans;
		return $timespans;
	}

	public function ___getTimespan($value) {
		if ($value instanceof CalendarTimespan) {
			$value = $value->getID();
		}
		return $this->getTimespans()->findOne('id=' . $value);
	}

	public function ___hasTimespan($value) {
		return $this->getTimespan($value) instanceof Timespan;
	}

	public function ___removeTimespan($value) {
		if ($value instanceof Timespan) {
			$value = $value->getID();
		}
		$timespan = $this->getTimespan($value);
		if (!$timespan instanceof Timespan) {
			return true;
		}

		return $timespan->delete();
	}

		public function ___getData() {
			$output =  [
				'id' => $this->getID(),
				'title' => $this->getTitle(),
				'description' => $this->getDescription(),
				'created' => $this->getCreated(),
				'created_user' => AppApi::getAjaxOf($this->getCreatedUser()),
				'modified' => $this->getModified(),
				'modified_user' => AppApi::getAjaxOf($this->getModifiedUser()),
				'status' => $this->getStatus(),
				'project_id' => !empty($this->getProject()) ? $this->getProject()->id : null,
				'timespans' => [],
				'saveable' => $this->isSaveable(),
				'deletable' => $this->isDeletable()
			];

			foreach ($this->getTimespans() as $timespan) {
				if (!$timespan->isValid()) {
					continue;
				}
				$output['timespans'][] = $timespan->getData();
			}

			return $output;
		}

	public function ___saveAllowed() {
		return $this->wire('user')->hasPermission(MfCalendar::managePermission);
	}

	public function ___isSaveable() {
		if (!$this->isValid()) {
			return false;
		}

		// Check user rights
		if (!$this->saveAllowed()) {
			return false;
			// throw new ForbiddenException('Saving not allowed', 403, [
			// 	'errorcode' => 'save_forbidden'
			// ]);
		}

		return true;
	}

	public function ___save() {
		if (!$this->isSaveable()) {
			return false;
		}

		$db = wire('database');
		$queryVars = [
			':title' => $this->getTitle(),
			':created_user' => $this->getCreatedUser()->id,
			':created' => date('Y-m-d G:i:s', $this->getCreated() === null ? 0 : $this->getCreated()),
			':modified_user' => $this->getModifiedUser()->id,
			':modified' => date('Y-m-d G:i:s', $this->getModified() === null ? 0 : $this->getModified()),
			':status' => $this->getStatus(),
			':description' => $this->getDescription(),
			':linked_page' => $this->getLinkedPage(),
			':project' => $this->getProject()
		];

		if (!$this->isNew()) {
			// This event already exists in db and shall be updated.

			$queryVars[':id'] = $this->getID();

			try {
				$updateStatement = 'UPDATE `' . MfCalendar::tableEvents . '` SET `title`=:title, `created_user`=:created_user, `created`=:created, `modified_user`=:modified_user, `modified`=:modified, `status`=:status, `description`=:description, `linked_page`=:linked_page, `project`=:project WHERE `id`=:id;';

				$query = $db->prepare($updateStatement);
				$query->closeCursor();
				$query->execute($queryVars);
				return $this->saveTimespans();
			} catch (\Exception $e) {
				$this->error('The event [' . $this->getID() . '] could not be saved: ' . $e->getMessage());
				return false;
			}

			return true;
		}

		// New event should be saved into db:
		try {
			$createStatement = 'INSERT INTO `' . MfCalendar::tableEvents . '` (`id`, `title`, `created_user`, `created`,`modified_user`, `modified`, `status` , `description`, `linked_page`, `project`) VALUES (NULL, :title, :created_user, :created, :modified_user, :modified, :status, :description, :linked_page, :project);';

			$query = $db->prepare($createStatement);
			$query->closeCursor();
			$query->execute($queryVars);
			$this->id = (int) $db->lastInsertId();
			return $this->saveTimespans();
		} catch (\Exception $e) {
			$this->error('The event could not be saved: ' . $e->getMessage());
			return false;
		}

		return true;
	}

	public function ___saveTimespans() {
		if (!$this->isIDValid()) {
			throw new \Exception('You cannot save timespans without an event-id.');
		}

		$success = true;
		foreach ($this->timespans as $timespan) {
			if (!($timespan instanceof CalendarTimespan)) {
				continue;
			}
			$timespan->setEventID($this->id);
			$result = $timespan->save();
			if (!$result) {
				$success = false;
			}
		}

		return $success;
	}

	public function ___deleteAllowed() {
		return $this->wire('user')->hasPermission(MfCalendar::managePermission);
	}

	public function ___isDeletable() {
		if (!$this->isIDValid()) {
			return false;
		}

		// Check user rights
		if (!$this->deleteAllowed()) {
			return false;
			// throw new ForbiddenException('Deleting not allowed', 403, [
			// 	'errorcode' => 'delete_forbidden'
			// ]);
		}

		return true;
	}

	/**
	 * Deletes the event and all associated timespans
	 * TODO delete permissions, status, ...
	 *
	 * @return boolean
	 */
	public function ___delete() {
		if ($this->isNew()) {
			return true;
		}

		try {
			$db = wire('database');
			$queryVars = [
				':id' => $this->getID()
			];

			$preparedQuery = 'DELETE FROM `' . MfCalendar::tableTimespans . '` WHERE `event_id`=:id;';
			$preparedQuery .= 'DELETE FROM `' . MfCalendar::tableEvents . '` WHERE `id`=:id;';

			$query = $db->prepare($preparedQuery);
			$query->closeCursor();
			$query->execute($queryVars);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	public static function getAll($params = []) {
		if (!is_array($params)) {
			$params = [];
		}

		$events = new WireArray();
		try {
			$preparedQuery = 'SELECT * FROM ' . MfCalendar::tableEvents;
			if (!empty($params['limit'])) {
				$preparedQuery .= ' LIMIT ' . $params['limit'];
			}
			if (!empty($params['offset'])) {
				$preparedQuery .= ' OFFSET ' . $params['offset'];
			}

			$db = wire('database');
			$query = $db->prepare($preparedQuery);
			$query->closeCursor();
			$query->execute();
			$queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);

			foreach ($queueRaw as $queueItem) {
				if (!isset($queueItem['id']) || empty($queueItem['id'])) {
					continue;
				}

				try {
					$event = new CalendarEvent($queueItem);
					$event->restoreTimespans();

					if ($event->isValid()) {
						$events->add($event);
					}
				} catch (\Exception $e) {
				}
			}
		} catch (\Exception $e) {
		}
		return $events;
	}

	public static function getById($id) {
		$event = false;
		$eventID = wire('sanitizer')->int($id);
		if (!empty($id)) {
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM `' . MfCalendar::tableEvents . '` WHERE `id`=:id');
			$query->closeCursor();

			$query->execute([
				':id' => $eventID
			]);
			$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);

			if (!$queueRaw) {
				throw new Wire404Exception();
			}

			$event = new CalendarEvent($queueRaw);
			$event->restoreTimespans();
		}

		return $event;
	}
}
