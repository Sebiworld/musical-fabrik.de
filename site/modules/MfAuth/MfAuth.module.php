<?php
namespace ProcessWire;

class MfAuth extends WireData implements Module {
	const logName = 'mf_auth';

	const tableRegistrations = 'mfauth_registrations';
	const tablePasswordForgot = 'mfauth_password_forgot';

	public static function getModuleInfo() {
		return [
			'title' => 'MF Auth',
			'summary' => 'Module that adds a register and password forgot api',
			'version' => '1.0.0',
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
		parent::___install();

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

	public function init() {
		$module = $this->wire('modules')->get('AppApi');
		$module->registerRoute(
			'auth',
			[
				'register' => [
					['OPTIONS', '', ['POST']],
					['POST', '', MfAuth::class, 'register', ['handle_authentication' => false]]
				],
				'register_confirm' => [
					['OPTIONS', '', ['POST']],
					['POST', '', MfAuth::class, 'registerConfirm', ['handle_authentication' => false]]
				]
			]
		);
	}

	public static function register($data) {
		if (empty(wire('sanitizer')->email($data->email))) {
			throw new BadRequestException(
				'Please provide an email address.',
				400,
				[
					'code' => 'missing email'
				]
			);
		}

		$user = wire('users')->get('email=' . $data->email);
		if ($user instanceof User && $user->id && ($user->roles->count() > 1 || $user->hasRole('app-user'))) {
			throw new ForbiddenException(
				'The email is already in use by an existing user. Did you forget your password?',
				403,
				[
					'code' => 'email already in use'
				]
			);
		}

		$missingFields = [];
		if (empty($data->password)) {
			$missingFields[] = 'password';
		}
		if (empty($data->firstname)) {
			$missingFields[] = 'firstname';
		}
		if (empty($data->lastname)) {
			$missingFields[] = 'lastname';
		}
		if (empty($data->birthdate)) {
			$missingFields[] = 'birthdate';
		}

		if (count($missingFields) >= 1) {
			throw new BadRequestException(
				'Please provide all necessary datafields: ' . implode(', ', $missingFields),
				400,
				[
					'code' => 'missing data',
					'fields' => $missingFields
				]
			);
		}

		if (!($user instanceof User) && !$user->id) {
			$username = wire('sanitizer')->pageName(strtolower($data->firstname) . '_' . strtolower($data->lastname));
			$user = wire('users')->get('name=' . $username);
			$user->email = wire('sanitizer')->email($data->email);

			if ($user instanceof User && $user->id && $user->hasRole('app-user')) {
				throw new ForbiddenException(
					'The username is already in use by an existing user. Did you forget your password?',
					403,
					[
						'code' => 'username already in use'
					]
				);
			} else {
				$user = wire('users')->add($username);
			}
		}

		$of = $user->of();
		$user->of(false);
		$user->pass = $data->password;
		$user->first_name = wire('sanitizer')->text($data->firstname);
		$user->last_name = wire('sanitizer')->text($data->lastname);
		$user->nickname = wire('sanitizer')->text($data->nickname);
		$user->short_description = wire('sanitizer')->text($data->rolestext);

		if ($birthdate = wire('sanitizer')->date($data->birthdate)) {
			$user->birthdate = $birthdate;
		}

		$user->save();
		$user->of($of);

		$token = SELF::addRegToken($user->id);

		try {
			$email = wireMail();
			$email->header('X-Mailer', wire('pages')->get(1)->httpUrl . '');
			$email->to(wire('sanitizer')->email($data->email));

			$email->subject('Willkommen in der Musical-Fabrik App!');

			$url = 'https://app.musical-fabrik.de/confirmRegister?code=' . $token;

			$plainContent= file_get_contents(wire('config')->paths->MfAuth . '/templates/registration-confirm.txt');
			if (!empty($plainContent)) {
				$email->body(str_replace('{{url}}', $url, $plainContent));
			}

			$htmlContent= file_get_contents(wire('config')->paths->MfAuth . '/templates/registration-confirm.html');
			if (!empty($htmlContent)) {
				$email->bodyHTML(str_replace('{{url}}', $url, $htmlContent));
			}

			$email->send();
		} catch (\Exception $e) {
			throw new InternalServererrorException(
				'The registration could not be finished. Please try again later.',
				500,
				[
					'code' => 'could not send mail'
				]
			);
		}

		return [
			'success' => true
		];
	}

	private static function addRegToken($userId) {
		if (empty($userId)) {
			throw new InternalServererrorException(
				'The registration could not be finished. Please try again later.',
				500,
				[
					'code' => 'could not create user id'
				]
			);
		}

		try {
			$db = wire('database');
			$queryVars = [
				':created' => date('Y-m-d G:i:s'),
				':user_id' => $userId,
				':token' => AppApiHelper::generateRandomString(12)
			];
			$query = $db->prepare('DELETE FROM `' . MfAuth::tableRegistrations . '` WHERE `user_id`=:user_id; INSERT INTO `' . MfAuth::tableRegistrations . '` (`id`, `created`, `user_id`, `token`) VALUES (NULL, :created, :user_id, :token);');
			$query->closeCursor();
			$query->execute($queryVars);
			return $queryVars[':token'];
		} catch (\Exception $e) {
			throw new InternalServererrorException(
				'The registration could not be finished. Please try again later.',
				500,
				[
					'code' => 'could not save reg token'
				]
			);
		}
		return true;
	}

	public static function checkRegistrationConfirm($data) {
		$token = $data->token;
		if (empty($token)) {
			return false;
		}

		try {
			// Search for token in db
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM ' . MfAuth::tableRegistrations . ' WHERE `token`=:token;');
			$query->closeCursor();

			$query->execute([
				':token' => $token
			]);
			$result = $query->fetch(\PDO::FETCH_ASSOC);

			if (empty($result['user_id'])) {
				return false;
			}

			$user = wire('users')->get('id=' . $result['user_id']);
			if (!($user instanceof User) && !$user->id) {
				return false;
			}

			$of = $user->of();
			$user->of(false);
			$user->addRole('app-user');
			$user->save();
			$user->of($of);

			try {
				// Send Email to admin
				$email = wireMail();
				$email->header('X-Mailer', wire('pages')->get(1)->httpUrl . '');
				$email->to('sebastian@musical-fabrik.de');

				$email->subject('Neue Registrierung für die Musical-Fabrik App!');

				$plainContent= file_get_contents(wire('config')->paths->MfAuth . '/templates/registration-admin-notification.txt');
				if (!empty($plainContent)) {
					$plainContent = str_replace('{{first_name}}', $user->first_name, $plainContent);
					$plainContent = str_replace('{{last_name}}', $user->last_name, $plainContent);
					$plainContent = str_replace('{{nickname}}', $user->nickname, $plainContent);
					$plainContent = str_replace('{{email}}', $user->email, $plainContent);
					$plainContent = str_replace('{{roles_text}}', $user->short_description, $plainContent);
					$plainContent = str_replace('{{edit_url}}', $user->editUrl(['http' => true]), $plainContent);

					$email->body($plainContent);
				}

				$email->send();
			} catch (\Exception $e) {
			}

			// Delete Reg Token
			$queryVars = [
				':id' => $result['id']
			];
			$query = $db->prepare('DELETE FROM `' . MfAuth::tableRegistrations . '` WHERE `id`=:id;');
			$query->closeCursor();
			$query->execute($queryVars);

			return true;
		} catch (\Exception $e) {
		}

		return false;
	}
}
