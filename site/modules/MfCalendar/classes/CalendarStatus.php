<?php
namespace ProcessWire;

class CalendarStatus extends WireData {
	private $initiated = false;

	public function __construct($import = []) {
		$this->set('name', '');
		$this->set('title', '');

		if (is_array($import) && wireCount($import) > 0) {
			$this->import($import);
		}

		if (!$this->isValid()) {
			throw new \Exception('You cannot create a status without a name and title.');
		}

		$this->initiated = true;
		parent::__construct();
	}

	protected function import(array $values) {
		if (!isset($values['name'])) {
			throw new \Exception('You cannot import a status without a name.');
		}

		if (isset($values['name'])) {
			$this->___setName($values['name']);
		}

		if (isset($values['title'])) {
			$this->___setTitle($values['title']);
		}
	}

	public function ___isSaveable() {
		if (!$this->isValid()) {
			return false;
		}
		return true;
	}

	public function ___isValid() {
		return $this->isNameValid() && $this->isTitleValid();
	}

	public function ___setName($name) {
		$name = $this->sanitizer->pageName($name);
		if (!$this->isNameValid($name)) {
			throw new \Exception('No valid name');
		}
		$this->set('name', $name);
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->name;
	}

	public function isNameValid($value = false) {
		if ($value === false) {
			$value = $this->name;
		}
		return is_string($value) && strlen($value) > 0 && $value = $this->sanitizer->pageName($value);
	}

	public function getName() {
		return $this->name;
	}

	public function ___setTitle($title) {
		$title = $this->sanitizer->text($title);
		if (!$this->isTitleValid($title)) {
			throw new \Exception('No valid title');
		}
		$this->set('title', $title);
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
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

	public function ___delete() {
		try {
			$db = wire('database');
			$queryVars = [
				':name' => $this->getName()
			];
			$preparedQuery = 'DELETE FROM `' . MfCalendar::tableStatus . '` WHERE `name`=:name;';
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
			':name' => $this->getName(),
			':title' => $this->getTitle()
		];

		try {
			$query = $db->prepare('INSTERT INTO `' . MfCalendar::tableStatus . '` (`name`, `title`) VALUES(:name, :title) ON DUPLICATE KEY UPDATE title=:title;');
			$query->closeCursor();
			$query->execute($queryVars);
		} catch (\Exception $e) {
			$this->error('The status [' . $this->getID() . '] could not be saved: ' . $e->getMessage());
			return false;
		}

		return true;
	}

	public function ___getData() {
		return [
			'name' => $this->getName(),
			'title' => $this->getTitle()
		];
	}

	public static function getAll() {
		$options = new WireArray();
		try {
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM ' . MfCalendar::tableStatus . ';');
			$query->closeCursor();
			$query->execute();
			$queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);

			foreach ($queueRaw as $queueItem) {
				if (!isset($queueItem['name']) || empty($queueItem['name'])) {
					continue;
				}

				try {
					$status = new CalendarStatus($queueItem);

					if ($status->isValid()) {
						$options->add($status);
					}
				} catch (\Exception $e) {
				}
			}
		} catch (\Exception $e) {
		}
		return $options;
	}
}
