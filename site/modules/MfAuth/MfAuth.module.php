<?php
namespace ProcessWire;

class MfAuth extends WireData implements Module {
	const logName = 'mf_auth';

	const tableRegistrations = 'mfauth_registrations';
	const tablePasswordForgot = 'mfauth_password_forgot';

	public static function getModuleInfo() {
		return [
			'title' => 'MF Auth',
			'summary' => 'Settings for the app login',
			'version' => '1.1.0',
			'author' => 'Sebastian Schendel',
			'icon' => 'user-plus',
			'requires' => [
				'PHP>=7.2.0',
				'ProcessWire>=3.0.98'
			],
			'autoload' => true,
			'singular' => true
		];
	}

	public function ___install() {
		$this->createDBTables();
	}

	private function createDBTables() {
		$statement = 'CREATE TABLE IF NOT EXISTS `' . self::tableRegistrations . '` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `created` datetime NOT NULL,
    `user_id` int(11) NOT NULL,
		`token` varchar(100) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tablePasswordForgot . '` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `created` datetime NOT NULL,
    `user_id` int(11) NOT NULL,
		`token` varchar(100) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';

		try {
			$database = wire('database');
			$database->exec($statement);
			$this->notices->add(new NoticeMessage('Created db-tables.'));
		} catch (\Exception $e) {
			$this->error('Error creating db-tables: ' . $e->getMessage());
		}
	}
}
