<?php
namespace ProcessWire;

class CalendarCategory extends WireData {
	private $initiated = false;

	public function __construct($import = []) {
		$this->set('id', null);
		$this->set('title', '');
		$this->set('name', '');
		$this->set('description', '');

		if (is_array($import) && wireCount($import) > 0) {
			$this->import($import);
		}

		if (!$this->isEventIDValid()) {
			throw new \Exception('You cannot create a timespan without an event-id.');
		}

		$this->initiated = true;
	}

	protected function import(array $values) {
		if (!isset($values['id'])) {
			throw new \Exception('You cannot import a category without an id.');
		}
		$this->set('id', (int) $values['id']);

		if (isset($values['title'])) {
			$this->___setTitle($values['title']);
		}

		if (isset($values['name'])) {
			$this->___setName($values['name']);
		}

		if (isset($values['description'])) {
			$this->___setDescription($values['description']);
		}
	}

	public function ___isSaveable() {
		if (!$this->isValid()) {
			return false;
		}
		return true;
	}

	public function ___isValid() {
		return $this->isIDValid() && $this->isTitleValid() && $this->isNameValid() && $this->isDescriptionValid();
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
		$this->set('title', $title);
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

	public function ___setName($name) {
		$name = $this->sanitizer->pageName($name);
		if (!$this->isNameValid($name)) {
			throw new \Exception('No valid name');
		}
		$this->set('name', $name);
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

	public function ___setDescription($description) {
		$description = $this->sanitizer->textarea($description);
		if (!$this->isDescriptionValid($description)) {
			throw new \Exception('No valid description');
		}
		$this->set('description', $description);
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

	public function ___delete() {
		if ($this->isNew()) {
			return true;
		}

		try {
			$db = wire('database');
			$queryVars = [
				':id' => $this->getID()
			];
			$preparedQuery = 'DELETE FROM `' . MfCalendar::tableCategoryRegs . '` WHERE `category`=:id;';
			$preparedQuery .= 'DELETE FROM `' . MfCalendar::tableCategoryPermissions . '` WHERE `category`=:id;';
			$preparedQuery .= 'DELETE FROM `' . MfCalendar::tableCategories . '` WHERE `id`=:id;';

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
			':title' => $this->getTitle(),
			':name' => $this->getName(),
			':description' => $this->getDescription()
		];

		if (!$this->isNew()) {
			// This category already exists in db and shall be updated.

			$queryVars[':id'] = $this->getID();

			try {
				$query = $db->prepare('UPDATE `' . MfCalendar::tableCategories . '` SET `title`=:title, `name`=:name, `description`=:description WHERE `id`=:id;');
				$query->closeCursor();
				$query->execute($queryVars);
			} catch (\Exception $e) {
				$this->error('The category [' . $this->getID() . '] could not be saved: ' . $e->getMessage());
				return false;
			}

			return true;
		}

		// New category should be saved into db:
		try {
			$query = $db->prepare('INSERT INTO `' . MfCalendar::tableCategories . '` (`id`, `title`, `name`, `description`) VALUES (NULL, :title, :name, :description);');
			$query->closeCursor();
			$query->execute($queryVars);
			$this->id = $db->lastInsertId();
		} catch (\Exception $e) {
			$this->error('The category could not be saved: ' . $e->getMessage());
			return false;
		}

		return true;
	}

	public function ___getData() {
		return [
			'id' => $this->getID(),
			'name' => $this->getName(),
			'title' => $this->getTitle(),
			'description' => $this->getDescription()
		];
	}

	public static function getAll() {
		$options = new WireArray();
		try {
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM ' . MfCalendar::tableCategories . ';');
			$query->closeCursor();
			$query->execute();
			$queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);

			foreach ($queueRaw as $queueItem) {
				if (!isset($queueItem['id']) || empty($queueItem['id'])) {
					continue;
				}

				try {
					$status = new CalendarCategory($queueItem);

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
