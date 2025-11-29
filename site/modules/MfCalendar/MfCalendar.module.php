<?php
namespace ProcessWire;

require_once __DIR__ . '/classes/CalendarEvent.php';
require_once __DIR__ . '/classes/CalendarTimespan.php';
require_once __DIR__ . '/classes/CalendarStatus.php';
require_once __DIR__ . '/classes/CalendarCategory.php';

class MfCalendar extends Process implements Module {
	const logName = 'mf_calendar';

	const managePermission = 'calendar-manage';

	const tableEvents = 'mfcalendar_events';
	const tableStatus = 'mfcalendar_status';
	const tableTimespans = 'mfcalendar_timespans';
	const tableTimespanPermissions = 'mfcalendar_timespan_permissions';
	const tableCategories = 'mfcalendar_categories';
	const tableCategoryRefs = 'mfcalendar_category_refs';
	const tableCategoryPermissions = 'mfcalendar_category_permissions';

	public static function getModuleInfo() {
		return [
			'title' => 'MF Calendar',
			'summary' => 'Module that adds a calendar api',
			'version' => '1.0.0',
			'author' => 'Sebastian Schendel',
			'icon' => 'calendar',
			'requires' => [
				'PHP>=7.2.0',
				'ProcessWire>=3.0.98'
			],
			'autoload' => true,
			'singular' => true,
			'permissions' => [
				'calendar-manage' => 'Manage Calendar'
			],
			'page' => [
				'name' => 'mfcalendar',
				'parent' => 'setup',
				'title' => 'MF Calendar',
				'icon' => 'calendar'
			],
		];
	}

	public function ___install() {
		parent::___install();

		$this->createDBTables();
	}

	private function createDBTables() {
		$statement = 'CREATE TABLE IF NOT EXISTS `' . self::tableEvents . '` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
		`title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
		`created` datetime NOT NULL,
    `created_user` int(11),
		`modified` datetime NOT NULL,
    `modified_user` int(11),
		`status` varchar(128),
		`description` mediumtext,
		`project` int(11),
		`linked_page` int(11),
    PRIMARY KEY (`id`),
		KEY `created` (`created`),
		KEY `modified` (`modified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tableStatus . '` (
    `name` varchar(128) NOT NULL,
    `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tableTimespans . '` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
		`event_id` int(11) NOT NULL,
		`title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
		`created` datetime NOT NULL,
    `created_user` int(11),
		`modified` datetime NOT NULL,
    `modified_user` int(11),
		`status` varchar(128),
		`description` mediumtext,
		`participants` mediumtext,
		`linked_page` int(11),
		`time_from` datetime,
		`time_until` datetime,
		`location` int(11),
    PRIMARY KEY (`id`),
		KEY `created` (`created`),
		KEY `modified` (`modified`),
		KEY `time_from` (`time_from`),
		KEY `time_until` (`time_until`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tableTimespanPermissions . '` (
    `timespan` int(11) NOT NULL,
		`permission` int(11) NOT NULL,
    PRIMARY KEY (`timespan`, `permission`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tableCategories . '` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
		`title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
		`description` mediumtext,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tableCategoryRefs . '` (
		`timespan` int(11) NOT NULL,
    `category` int(11) NOT NULL,
    PRIMARY KEY (`timespan`, `category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tableCategoryPermissions . '` (
    `category` int(11) NOT NULL,
		`permission` int(11) NOT NULL,
    PRIMARY KEY (`category`, `permission`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

		try {
			$database = wire('database');
			$database->exec($statement);
			$this->notices->add(new NoticeMessage('Created db-tables.'));
		} catch (\Exception $e) {
			$this->error('Error creating db-tables: ' . $e->getMessage());
		}
	}

	public function init() {
		$module = $this->wire('modules')->get('AppApi');
		$module->registerRoute(
			'calendar',
			[
				['OPTIONS', '', ['GET']],
				['GET', '', self::class, 'getApiCalendar', [], [
					'summary' => 'Get a list of current calendar events, stati and categories.',
					'operationId' => 'getCalendarEvents',
					'tags' => ['Authentication'],
					'security' => [
						['apiKey' => []],
						['bearerAuth' => []]
					]
				]],

				'stati' => [
					['OPTIONS', '', ['GET']],
					['GET', '', self::class, 'getApiCalendarStati', [], [
						'summary' => 'Get a list of all possible calendar stati.',
						'operationId' => 'getCalendarStati',
						'tags' => ['Authentication'],
						'security' => [
							['apiKey' => []],
							['bearerAuth' => []]
						]
					]]
				],

				'categories' => [
					['OPTIONS', '', ['GET']],
					['GET', '', self::class, 'getApiCalendarCategories', [], [
						'summary' => 'Get a list of all possible calendar categories.',
						'operationId' => 'getCalendarCategories',
						'tags' => ['Authentication'],
						'security' => [
							['apiKey' => []],
							['bearerAuth' => []]
						]
					]]
				],

				// Events-Api
				'events' => [
					['OPTIONS', '', ['GET', 'POST']],
					['OPTIONS', '{id:\d+}', ['GET', 'POST', 'UPDATE', 'DELETE']],
					['GET', '', self::class, 'getApiCalendarEvents', [], [
						'summary' => 'Get a list of current calendar events.',
						'operationId' => 'getCalendarEvents',
						'tags' => ['Authentication'],
						'security' => [
							['apiKey' => []],
							['bearerAuth' => []]
						]
					]],
					['POST', '', self::class, 'saveApiCalendarEvent', [], [
						'summary' => 'Create a new calendar event.',
						'operationId' => 'setCalendarEvent',
						'tags' => ['Authentication'],
						'security' => [
							['apiKey' => []],
							['bearerAuth' => []]
						]
					]],
					['GET', '{id:\d+}', self::class, 'getApiCalendarEvent', [], [
						'summary' => 'Get data of a calendar event.',
						'operationId' => 'getCalendarEvent',
						'tags' => ['Authentication'],
						'security' => [
							['apiKey' => []],
							['bearerAuth' => []]
						]
					]],
					['POST', '{id:\d+}', self::class, 'saveApiCalendarEvent', [], [
						'summary' => 'Update a calendar event.',
						'operationId' => 'setCalendarEvent',
						'tags' => ['Authentication'],
						'security' => [
							['apiKey' => []],
							['bearerAuth' => []]
						]
					]],
					['DELETE', '{id:\d+}', self::class, 'deleteApiCalendarEvent', [], [
						'summary' => 'Delete a calendar event.',
						'operationId' => 'deleteCalendarEvent',
						'tags' => ['Authentication'],
						'security' => [
							['apiKey' => []],
							['bearerAuth' => []]
						]
					]]
				]
			]
		);
	}

	public function ___execute() {
		$this->headline($this->_('MF Calendar'));

		return [
			'events' => CalendarEvent::getAll(),
			'stati' => CalendarStatus::getAll(),
			'categories' => CalendarCategory::getAll(),
			'module' => $this
		];
	}

	public function ___executeEvent() {
		$this->headline($this->_('MfCalendar') . ' ' . $this->_('Event'));
		$this->breadcrumb($this->wire('page')->url, $this->_('MfCalendar'));

		$action = $this->sanitizer->text($this->input->urlSegment2);

		$stati = CalendarStatus::getAll();
		$statusOptions = [];
		foreach ($stati as $status) {
			$statusOptions[$status->getName()] = $status->getTitle();
		}

		$categories = CalendarCategory::getAll();
		$categoryOptions = [];
		foreach ($categories as $category) {
			$categoryOptions[$category->getID()()] = $category->getTitle();
		}

		$templateParams = [
			'event' => false,
			'action' => $action,
			'statusOptions' => $statusOptions,
			'categoryOptions' => $categoryOptions,
		];

		if ($action === 'new') {
			return $templateParams;
		}

		$id = $this->sanitizer->int($this->input->urlSegment3);
		if ($this->input->urlSegment3 === '' || empty($id)) {
			$templateParams['locked'] = true;
			$templateParams['message'] = 'Missing ID';
			return $templateParams;
		}

		try {
			$event = CalendarEvent::getById($id);
			if ($action === 'edit') {
				$templateParams['event'] = $event;

				if ($this->input->urlSegment4 === 'timespan') {
					$subaction = $this->sanitizer->text($this->input->urlSegment5);
					$templateParams['subarea'] = 'timespan';
					$templateParams['action'] = $subaction;
					$templateParams['timespan'] = false;
					if ($subaction === 'new') {
						return $templateParams;
					}

					$subId = $this->sanitizer->int($this->input->urlSegment6);
					if ($this->input->urlSegment6 === '' || empty($subId)) {
						$templateParams['locked'] = true;
						$templateParams['message'] = 'Missing ID';
						return $templateParams;
					}

					$timespan = CalendarTimespan::getById($subId);
					if ($action === 'edit') {
						$templateParams['timespan'] = $timespan;

						return $templateParams;
					} elseif ($action === 'delete') {
						$timespan->delete();
						$this->notices->add(new NoticeMessage(sprintf($this->_('The timespan was successfully deleted: %s'), $subId)));
						$this->session->redirect($this->wire('page')->url . 'event/edit/' . $event->getID());

						$templateParams['locked'] = true;
						$templateParams['message'] = sprintf($this->_('The timespan was successfully deleted: %s'), $subId);

						return $templateParams;
					}
				}

				return $templateParams;
			} elseif ($action === 'delete') {
				$event->delete();
				$this->notices->add(new NoticeMessage(sprintf($this->_('The event was successfully deleted: %s'), $id)));
				$this->session->redirect($this->wire('page')->url);

				$templateParams['locked'] = true;
				$templateParams['message'] = sprintf($this->_('The event was successfully deleted: %s'), $id);

				return $templateParams;
			}
		} catch (\Exception $e) {
			$templateParams['locked'] = true;
			$templateParams['message'] = $e->getMessage();

			return $templateParams;
		}

		$templateParams['locked'] = true;

		return $templateParams;
	}

	public static function getApiCalendar($data) {
		$output = [
			'events' => [],
			'stati' => [],
			'categories' => []
		];

		$queryParams = [];
		if (isset($data->limit)) {
			$limit = wire('sanitizer')->intUnsigned($data->limit);
			if (!empty($limit)) {
				$queryParams['limit'] = $limit;
			}
		}

		if (isset($data->offset)) {
			$offset = wire('sanitizer')->intUnsigned($data->offset);
			if (!empty($offset)) {
				$queryParams['offset'] = $offset;
			}
		}

		foreach (CalendarEvent::getAll($queryParams) as $event) {
			if (!$event) {
				continue;
			}
			$output['events'][] = $event->getData();
		}

		foreach (CalendarStatus::getAll() as $status) {
			if (!$status) {
				continue;
			}
			$output['stati'][] = $status->getData();
		}

		foreach (CalendarCategory::getAll() as $category) {
			if (!$category) {
				continue;
			}
			$output['categories'][] = $category->getData();
		}

		return $output;
	}

	public static function getApiCalendarStati() {
		$output = [
			'stati' => []
		];

		foreach (CalendarStatus::getAll() as $status) {
			if (!$status) {
				continue;
			}
			$output['stati'][] = $status->getData();
		}

		return $output;
	}

	public static function getApiCategories() {
		$output = [
			'categories' => []
		];

		foreach (CalendarCategory::getAll() as $category) {
			if (!$category) {
				continue;
			}
			$output['categories'][] = $category->getData();
		}

		return $output;
	}

	public static function getApiCalendarEvents() {
		$output = [
			'events' => []
		];

		foreach (CalendarEvent::getAll() as $event) {
			if (!$event) {
				continue;
			}
			$output['events'][] = $event->getData();
		}

		return $output;
	}

	public static function getApiCalendarEvent($data) {
		$id = wire('sanitizer')->intUnsigned($data->id);
		if (empty($id)) {
			throw new BadRequestException('No valid id provided', 400, [
				'errorcode' => 'event_id_not_valid'
			]);
		}

		$event = null;
		try {
			$event = CalendarEvent::getById($id);
		} catch (\Exception $e) {
		}

		if (!($event instanceof CalendarEvent)) {
			throw new NotFoundException('Event not found', 404, [
				'errorcode' => 'event_not_found'
			]);
		}

		$output = [
			'event' => $event->getData(),
		];

		return $output;
	}

	public static function saveApiCalendarEvent($data) {
		if (empty($data->event)) {
			throw new BadRequestException('Event data not valid', 400, [
				'errorcode' => 'event_data_not_valid'
			]);
		}

		$id = null;
		if (!empty($data->id)) {
			$id = wire('sanitizer')->intUnsigned($data->id);
		}

		$event = new CalendarEvent();
		if ($id) {
			try {
				$event = CalendarEvent::getById($id);
			} catch (\Exception $e) {
			}
		}

		if (!($event instanceof CalendarEvent)) {
			throw new NotFoundException('Event not found', 404, [
				'errorcode' => 'event_not_found'
			]);
		}

		$event->importData($data->event);
		$event->setStatus('published');
		$success = $event->save();

		return [
			'success' => $success,
			'event' => $event->getData()
		];
	}

	public static function deleteApiCalendarEvent($data) {
		$id = null;
		if (!empty($data->id)) {
			$id = wire('sanitizer')->intUnsigned($data->id);
		}

		if (empty($id)) {
			throw new BadRequestException('ID not valid', 400, [
				'errorcode' => 'id_not_valid'
			]);
		}

		$event = new CalendarEvent();
		if ($id) {
			try {
				$event = CalendarEvent::getById($id);
			} catch (\Exception $e) {
			}
		}

		if (!($event instanceof CalendarEvent)) {
			throw new NotFoundException('Event not found', 404, [
				'errorcode' => 'event_not_found'
			]);
		}

		$success = $event->delete();

		return [
			'success' => $success
		];
	}
}
