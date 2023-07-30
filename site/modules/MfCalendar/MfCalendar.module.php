<?php
namespace ProcessWire;

require_once __DIR__ . '/classes/CalendarEvent.php';
require_once __DIR__ . '/classes/CalendarTimespan.php';
require_once __DIR__ . '/classes/CalendarStatus.php';
require_once __DIR__ . '/classes/CalendarCategory.php';

class MfCalendar extends Process implements Module {
	const logName = 'mf_calendar';

	const managePermission = 'mfcalendar_manage';

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
				'mfcalendar_manage' => 'Manage Calendar'
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
		`title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
		`created` datetime NOT NULL,
    `created_user` int(11),
		`modified` datetime NOT NULL,
    `modified_user` int(11),
		`status` varchar(128),
		`description` mediumtext,
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
		`title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
		`created` datetime NOT NULL,
    `created_user` int(11),
		`modified` datetime NOT NULL,
    `modified_user` int(11),
		`status` varchar(128),
		`description` mediumtext,
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
				['GET', '', SELF::class, 'apiCalendarEvents', [], [
					'summary' => 'Get a list of current calendar events.',
					'operationId' => 'getCurrentUser',
					'tags' => ['Authentication'],
					'security' => [
						['apiKey' => []],
						['bearerAuth' => []]
					]
				]]
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

	public static function apiCalendarEvents() {
		$output = [
			'events' => [],
			'stati' => [],
			'categories' => []
		];

		foreach (CalendarEvent::getAll() as $event) {
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
}
