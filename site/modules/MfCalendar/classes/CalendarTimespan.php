<?php
namespace ProcessWire;

class CalendarTimespan extends WireData {
	private $initiated = false;

	public function __construct($import = [], $event = null) {
		$this->set('id', null);
		$this->set('eventID', null);
		$this->set('title', '');
		$this->set('created', time());
		$this->set('createdUser', null);
		$this->set('modified', time());
		$this->set('modifiedUser', null);
		$this->set('status', null);
		$this->set('description', '');
		$this->set('linkedPage', null);
		$this->set('timeFrom', null);
		$this->set('timeUntil', null);
		$this->set('location', null);

		if ($event instanceof CalendarEvent) {
			$this->set('eventID', $event->getID());
		} elseif (is_integer($event)) {
			$this->set('eventID', $event);
		} elseif (is_string($event)) {
			$this->set('eventID', (int)$event);
		}

		if (is_array($import) && wireCount($import) > 0) {
			$this->import($import);
		}

		if (!$this->isEventIDValid()) {
			throw new \Exception('You cannot create a timespan without an event-id.');
		}

		if ($this->isNew()) {
			$this->set('created', time());
			$this->set('createdUser', $this->wire('user'));
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}
		$this->initiated = true;
	}

	protected function import(array $values) {
		if (!isset($values['event_id'])) {
			throw new \Exception('You cannot import a timespan without an event-id.');
		}

		if (!isset($values['id'])) {
			throw new \Exception('You cannot import a timespan without an id.');
		}
		$this->set('eventID', (int) $values['event_id']);
		$this->set('id', (int) $values['id']);

		if (isset($values['title'])) {
			$this->___setTitle($values['title']);
		}

		if (isset($values['created'])) {
			$this->___setCreated($values['created']);
		}
		if (isset($values['created_user_id'])) {
			$this->___setCreatedUser($values['created_user_id']);
		}

		if (isset($values['modified'])) {
			$this->___setModified($values['modified']);
		}
		if (isset($values['modified_user_id'])) {
			$this->___setModifiedUser($values['modified_user_id']);
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

		if (isset($values['time_from'])) {
			$this->___setTimeFrom($values['time_from']);
		}

		if (isset($values['time_until'])) {
			$this->___setTimeUntil($values['time_until']);
		}

		if (isset($values['location'])) {
			$this->___setLocation($values['location']);
		}
	}

	public function ___isSaveable() {
		if (!$this->isValid()) {
			return false;
		}
		return true;
	}

	public function ___isValid() {
		return $this->isEventIDValid() && $this->isIDValid() && $this->isTitleValid() && $this->isCreatedValid() && $this->isCreatedUserValid() && $this->isModifiedValid() && $this->isModifiedUserValid() && $this->isStatusValid() && $this->isDescriptionValid() && $this->isLinkedPageValid() && $this->isTimeFromValid() && $this->isTimeUntilValid() && $this->isLocationValid();
	}

	public function ___isAccessable() {
		// TODO Check permissions
		return $this->isValid();
	}

	public function ___isNew() {
		return empty($this->id);
	}

	public function getEventID() {
		return $this->eventID;
	}

	public function isEventIDValid($value = false) {
		if ($value === false) {
			$value = $this->eventID;
		}
		return is_integer($value) && $value >= 0;
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
		$this->set('title', $title);
		if ($this->initiated) {
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}
		return $this->title;
	}

	public function isTitleValid($value = false) {
		if ($value === false) {
			$value = $this->title;
		}
		return is_string($value) && strlen($value) > 0;
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
		$this->set('status', $this->sanitizer->text($status));
		if ($this->initiated) {
			$this->set('modified', time());
			$this->set('modifiedUser', $this->wire('user'));
		}
		return $this->status;
	}

	public function isStatusValid($value = false) {
		// TODO
		return true;
	}

	public function getStatus() {
		return $this->status;
	}

	public function ___setDescription($description) {
		$description = $this->sanitizer->textarea($description);
		if (!$this->isDescriptionValid($description)) {
			throw new \Exception('No valid description');
		}
		$this->set('description', $description);
		if ($this->initiated) {
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
		$this->set('linkedPage', $linkedPage);
		if ($this->initiated) {
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

	public function ___delete() {
		if ($this->isNew()) {
			return true;
		}

		try {
			$db = wire('database');
			$queryVars = [
				':id' => $this->getID()
			];
			$preparedQuery = 'DELETE FROM `' . MfCalendar::tableCategoryRefs . '` WHERE `timespan`=:id;';
			$preparedQuery .= 'DELETE FROM `' . MfCalendar::tableTimespans . '` WHERE `id`=:id;';

			$query = $db->prepare($preparedQuery);
			$query->closeCursor();
			$query->execute($queryVars);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	public function ___save() {
		if (!$this->isSaveable()) {
			return false;
		}

		$db = wire('database');
		$queryVars = [
			':event_id' => $this->getEventID(),
			':title' => $this->getTitle(),
			':created_user_id' => $this->getCreatedUser()->id,
			':created' => date('Y-m-d G:i:s', $this->getCreated() === null ? 0 : $this->getCreated()),
			':modified_user_id' => $this->getModifiedUser()->id,
			':modified' => date('Y-m-d G:i:s', $this->getModified() === null ? 0 : $this->getModified()),
			':status' => $this->getStatus(),
			':description' => $this->getDescription(),
			':linked_page' => $this->getLinkedPage(),
			':time_until' => $this->getTimeUntil() === null ? null : date('Y-m-d G:i:s', $this->getTimeUntil()),
			':time_from' => $this->getTimeFrom() === null ? null : date('Y-m-d G:i:s', $this->getTimeFrom()),
			':location' => $this->getLocation(),
		];

		if (!$this->isNew()) {
			// This timespan already exists in db and shall be updated.

			$queryVars[':id'] = $this->getID();

			try {
				$query = $db->prepare('UPDATE `' . MfCalendar::tableTimespans . '` SET `event_id`=:event_id, `title`=:title, `created_user_id`=:created_user_id, `created`=:created, `modified_user_id`=:modified_user_id, `modified`=:modified, `status`=:status, `description`=:description, `linked_page`=:linked_page, `timespan_until`=:timespan_until, `timespan_from`=:timespan_from, `location`=:location WHERE `id`=:id;');
				$query->closeCursor();
				$query->execute($queryVars);
			} catch (\Exception $e) {
				$this->error('The timespan [' . $this->getID() . '] could not be saved: ' . $e->getMessage());
				return false;
			}

			return true;
		}

		// New timespan should be saved into db:
		try {
			$query = $db->prepare('INSERT INTO `' . MfCalendar::tableTimespans . '` (`event_id`, `id`, `title`, `created_user_id`, `created`,`modified_user_id`, `modified`, `status`, `description`, `linked_page`,`timespan_until`, `timespan_from`, `location`) VALUES (:event_id, NULL, :title, :created_user_id, :created, :modified_user_id, :modified, :status, :description, :linked_page, :timespan_until, :timespan_from, :location);');
			$query->closeCursor();
			$query->execute($queryVars);
			$this->id = $db->lastInsertId();
		} catch (\Exception $e) {
			$this->error('The timespan could not be saved: ' . $e->getMessage());
			return false;
		}

		return true;
	}

	public function ___getData() {
		return [
			'id' => $this->getID(),
			'title' => $this->getTitle(),
			'description' => $this->getDescription()
		];
	}

	public static function getAll() {
		$timespans = new WireArray();
		try {
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM ' . MfCalendar::tableTimespans . ';');
			$query->closeCursor();
			$query->execute();
			$queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);
			if (!$queueRaw) {
				throw new Wire404Exception();
			}

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
		} catch (\Exception $e) {
		}
		return $timespans;
	}

	public static function getAllForEvent($id) {
		$timespans = new WireArray();

		$eventId = wire('sanitizer')->int($id);
		if (!empty($id)) {
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM ' . MfCalendar::tableTimespans . ' WHERE `event_id`=:event_id;');
			$query->closeCursor();

			$query->execute([
				':event_id' => $eventId
			]);
			$queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);

			if (!$queueRaw) {
				throw new Wire404Exception();
			}

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
		}

		return $timespans;
	}

	public static function getById($id) {
		$timespan = false;
		$timespanId = wire('sanitizer')->int($id);
		if (!empty($id)) {
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM ' . MfCalendar::tableTimespans . ' WHERE `id`=:id;');
			$query->closeCursor();

			$query->execute([
				':id' => $timespanId
			]);
			$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);

			if (!$queueRaw) {
				throw new Wire404Exception();
			}
			$timespan = new CalendarTimespan($queueRaw);
		}

		return $timespan;
	}
}
