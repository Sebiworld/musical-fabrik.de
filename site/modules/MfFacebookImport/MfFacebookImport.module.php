<?php
namespace ProcessWire;

class MfFacebookImport extends Process implements Module {
	const managePermission = 'facebook_import_manage';
	const logName = 'mf_facebook_import';

	public static function getModuleInfo() {
		return [
			'title' => 'MF Facebook Import',
			'summary' => 'Module that imports Facebook posts',
			'version' => '1.0.0',
			'author' => 'Sebastian Schendel',
			'icon' => 'facebook-official',
			'requires' => [
				'PHP>=7.2.0',
				'ProcessWire>=3.0.98'
			],
			'autoload' => true,
			'singular' => true,
			'permissions' => [
				'facebook_import_manage' => 'Manage Facebook Import'
			],
			'page' => [
				'name' => 'mf_facebook_import',
				'parent' => 'setup',
				'title' => 'Facebook Import',
				'icon' => 'facebook-official'
			],
		];
	}

	public function ___execute() {
		$this->headline('Facebook Import');

		return [
			'module' => $this,
			'existingLogs' => $this->wire('log')->getLogs(),
			'configUrl' => $this->wire('config')->urls->admin . 'module/edit?name=MfFacebookImport'
		];
	}

	public function ___executePosts() {
		$this->headline($this->_('Facebook Import') . ' ' . $this->_('Posts'));

		$this->config->scripts->add(
			$this->config->urls->MfFacebookImport . 'assets/MfFacebookImport.js'
		);

		$response = [];
		$posts = [];
		$alerts = [];

		try {
			$response = @$this->wire('modules')->getConfig('MfFacebookImport', 'response_data');
			$lastRequest = @$this->wire('modules')->getConfig('MfFacebookImport', 'request_timestamp');
			if (empty($response) || (!!$this->input->post('action-refresh', 'int') && (!$lastRequest || $lastRequest < $this->input->post('action-refresh', 'int')))) {
				$response = $this->getPosts();
			}

			$posts = $response['published_posts']['data'] ?? [];
			$posts = $this->filterPosts($posts);

			$importId = $this->input->get('import', 'text');
			$modificationId = $this->input->get('mid', 'text');
			$aborted = false;
			if (!empty($importId)) {
				$importData = null;
				foreach ($posts as $post) {
					if (empty($post['id']) || $post['id'] !== $importId) {
						continue;
					}
					$pageFound = $this->pages->findOne('include=all,external_id=' . $post['id']);

					if (!($pageFound instanceof NullPage) && $pageFound->id) {
						if ($modificationId && $pageFound->external_modification_id === $modificationId) {
							// $alerts[] = [
							// 	'type' => 'info',
							// 	'message' => $this->_('Post already imported.')
							// ];
							$aborted = true;

							break;
						}

						if (empty($modificationId) && $pageFound->external_modification_hash && $pageFound->external_modification_hash === md5(json_encode($post))) {
							$alerts[] = [
								'type' => 'info',
								'message' => $this->_('Post already imported.')
							];
							$aborted = true;

							break;
						}
					}

					$importData = $post;
				}
				if (empty($importData)) {
					if (!$aborted) {
						$alerts[] = [
							'type' => 'error',
							'message' => $this->_('No matching post id found.')
						];
					}
				} else {
					try {
						$this->importPost($importData, $modificationId);
						$alerts[] = [
							'type' => 'success',
							'message' => $this->_('Post import successfull.')
						];
					} catch (\Exception $e) {
						$alerts[] = [
							'type' => 'error',
							'message' => $e->getMessage()
						];
					}
				}
			}
		} catch (\Exception $e) {
			$alerts[] = [
				'type' => 'error',
				'message' => $e->getMessage()
			];
		}

		return [
			'response' => $response,
			'posts' => $posts,
			'alerts' => $alerts
		];
	}

	private function filterPosts($posts) {
		if (empty($posts) || !is_array($posts)) {
			return [];
		}
		return array_filter($posts, function ($v, $k) {
			return !empty($v['message']) && $v['is_published'] === true && $v['is_hidden'] !== true && isset($v['privacy']['value']) && $v['privacy']['value'] === 'EVERYONE';
		}, ARRAY_FILTER_USE_BOTH);
	}

	private function importPost($post, $modificationId = '') {
		if (empty($post['id'])) {
			throw new WireException($this->_('Import: No post id found.'));
		}

		$page = $this->pages->findOne('include=all,external_id=' . $post['id']);
		if ($page instanceof NullPage || !$page->id) {
			$page = new Page();
			$page->parent = $this->pages->get('/aktuelles/');
			$page->template = 'article';
			$page->name = 'fb-post';
		}

		if (!empty($post['message_tags']) && is_array($post['message_tags'])) {
			// Set different parent if a specific tag is present

			foreach ($post['message_tags'] as $tag) {
				if (empty($tag['name'])) {
					continue;
				}
				if (strpos(strtolower($tag['name']), 'wie im himmel') !== false || strpos(strtolower($tag['name']), 'wieimhimmel') !== false) {
					$page->parent = $this->pages->get('/projekte/wie-im-himmel/aktuelles/');
					break;
				} else if (strpos(strtolower($tag['name']), 'claus') !== false) {
					$page->parent = $this->pages->get('/projekte/claus/aktuelles/');
					break;
				} else if (strpos(strtolower($tag['name']), 'medicus') !== false) {
					$page->parent = $this->pages->get('/projekte/der-medicus/aktuelles/');
					break;
				} else if (strpos(strtolower($tag['name']), 'päpstin') !== false || strpos(strtolower($tag['name']), 'paepstin') !== false) {
					$page->parent = $this->pages->get('/projekte/die-paepstin/aktuelles/');
					break;
				} else if (strpos(strtolower($tag['name']), 'zauberer von oz') !== false || strpos(strtolower($tag['name']), 'zauberervonoz') !== false) {
					$page->parent = $this->pages->get('/projekte/der-zauberer-von-oz/aktuelles/');
					break;
				} else if (strpos(strtolower($tag['name']), 'oliver') !== false) {
					$page->parent = $this->pages->get('/projekte/oliver/aktuelles/');
					break;
				} else if (strpos(strtolower($tag['name']), 'schöne und das biest') !== false || strpos(strtolower($tag['name']), 'schoene und das biest') !== false) {
					$page->parent = $this->pages->get('/projekte/die-schoene-und-das-biest/aktuelles/');
					break;
				}
			}
		}

		$text = $post['message'];

		$page->title = 'Facebook Post: "' . $this->getWords($this->removeEmoji($text), 4) . '..."';
		$page->external_id = $post['id'];
		$page->external_type = 'Facebook';
		$page->external_modification_hash = md5(json_encode($post));
		$page->external_modification_id = $modificationId;
		$page->external_link = $post['permalink_url'];
		$page->intro = $text;
		$page->datetime_from = $this->datetime->strtotime($post['created_time']);

		$of = $page->of();
		$page->of(false);
		$page->save([
			'adjustName' => true
		]);

		if (!empty($post['full_picture'])) {
			try {
				$page->main_image->removeAll();
				$page->main_image->add($post['full_picture']);

				$page->save();
			} catch (\Exception $e) {
			}
		}

		$page->of($of);
	}

	public function importPosts() {
		$logCollection = [];
		$moduleUrl = $this->wire('config')->urls->admin . 'setup/mf_facebook_import/';

		$msg = 'importPosts() started';
		$logCollection[] = $msg;
		$this->wire('log')->save(MfFacebookImport::logName, $msg, ['url' => $moduleUrl]);

		try {
			$response = $this->getPosts();
			$posts = $response['published_posts']['data'] ?? [];
			$posts = $this->filterPosts($posts);

			$aborted = false;
			$logCollection[] = '';
			$importedPostsCounter = 0;

			foreach ($posts as $post) {
				if (empty($post['id'])) {
					continue;
				}

				$logCollection[] = '---------------------';

				$msg = 'Importing post: ' . $post['id'] . ' [' . md5(json_encode($post)) . ' / ' . $pageFound->external_modification_hash . ']';
				$logCollection[] = $msg;
				$this->wire('log')->save(MfFacebookImport::logName, $msg, ['url' => $moduleUrl]);

				$pageFound = $this->pages->findOne('include=all,external_id=' . $post['id']);

				if (!($pageFound instanceof NullPage) && $pageFound->id) {
					if ($pageFound->external_modification_hash && $pageFound->external_modification_hash === md5(json_encode($post))) {
						$msg = 'Post [' . $post['id'] . '] already imported.';
						$logCollection[] = $msg;
						$this->wire('log')->save(MfFacebookImport::logName, $msg, ['url' => $moduleUrl]);

						continue;
					}
				}

				try {
					$this->importPost($post);
					$importedPostsCounter++;

					$msg = 'Post successfully imported: ' . $post['id'];
					$logCollection[] = $msg;
					$this->wire('log')->save(MfFacebookImport::logName, $msg, ['url' => $moduleUrl]);
				} catch (\Exception $e) {
					$msg = 'Post [' . $post['id'] . '] import failed: ' . $e->getMessage();
					$logCollection[] = $msg;
					$this->wire('log')->save(MfFacebookImport::logName, $msg, ['url' => $moduleUrl]);
				}
			}
			$logCollection[] = '---------------------';
			$logCollection[] = '';

			$msg = 'importPosts() finished.';
			$logCollection[] = $msg;
			$this->wire('log')->save(MfFacebookImport::logName, $msg, ['url' => $moduleUrl]);

			if ($importedPostsCounter === 0) {
				$logCollection[] = 'NO EVENTS';
			}
		} catch (\Exception $e) {
			$msg = 'Import failed: ' . $e->getMessage();
			$logCollection[] = $msg;
			$this->wire('log')->save(MfFacebookImport::logName, $msg, ['url' => $moduleUrl]);
		}

		return $logCollection;
	}

	private function getPosts($requestPathParam = null, $accessTokenParam = null) {
		$requestPath = $requestPathParam ?? @$this->wire('modules')->getConfig('MfFacebookImport', 'request_path');
		$accessToken = $accessTokenParam ?? @$this->wire('modules')->getConfig('MfFacebookImport', 'access_token');

		if (empty($requestPath)) {
			throw new WireException($this->_('Error: Request path not set. Please provide a valid facebook request path in the module config.'));
		}

		if (empty($accessToken)) {
			throw new WireException($this->_('Error: Access token not set. Please provide a valid facebook access token in the module config.'));
		}

		$requestPath = trim($requestPath, '/');

		// get cURL resource
		$ch = curl_init();


		$fieldsQuery = 'published_posts.limit(20).offset(0){id,call_to_action,event,created_time,updated_time,from,message,message_tags,privacy,place,parent_id,is_published,permalink_url,full_picture,width,height,target,story,story_tags,status_type,is_hidden,is_expired}';

		// set url
		curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/' . $requestPath . '?access_token=' . $accessToken . '&fields=' . urlencode($fieldsQuery));

		// set method
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

		// return the transfer as a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// send the request and save response to $response
		$response = curl_exec($ch);

		if (!$response) {
			throw new WireException('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
		}

		$jsonResponse = json_decode($response, true);
		if (!$jsonResponse) {
			throw new WireException($this->_('Error: No valid json response.'));
		}

		// echo 'HTTP Status Code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
		// echo 'Response Body: ' . $response . PHP_EOL;

		// close curl resource to free up system resources
		curl_close($ch);

		$config = @$this->wire('modules')->getConfig('MfFacebookImport') ?? [];
		$config['request_timestamp'] = time();
		$config['response_data'] = $jsonResponse;

		@$this->wire('modules')->saveConfig('MfFacebookImport', $config);

		return $jsonResponse;
	}

	private function getWords($text, $count) {
		return implode(' ', array_slice(explode(' ', $text), 0, $count));
	}

	private function removeEmoji($string) {

		// Match Emoticons
		$regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$clear_string = preg_replace($regex_emoticons, '', $string);

		// Match Miscellaneous Symbols and Pictographs
		$regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$clear_string = preg_replace($regex_symbols, '', $clear_string);

		// Match Transport And Map Symbols
		$regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
		$clear_string = preg_replace($regex_transport, '', $clear_string);

		// Match Miscellaneous Symbols
		$regex_misc = '/[\x{2600}-\x{26FF}]/u';
		$clear_string = preg_replace($regex_misc, '', $clear_string);

		// Match Dingbats
		$regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
		$clear_string = preg_replace($regex_dingbats, '', $clear_string);

		return $clear_string;
	}
}
