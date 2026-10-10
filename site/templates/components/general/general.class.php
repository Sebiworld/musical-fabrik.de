<?php
namespace ProcessWire;

class General extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->configurationService = $this->getService('ConfigurationService');

		// Additional meta data is collected here:
		$this->metas = new WireData();

		// general should be globally available
		wire('twack')->makeComponentGlobal($this, 'general');

		// Add main scripts for all pages:
		$this->addStyle('bootstrap.css', [
			'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true
		]);
		$this->addStyle('swiper.css', [
			'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true
		]);
		$this->addStyle('lightgallery.css', [
			'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true
		]);
		$this->addStyle('starability.css', [
			'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true
		]);
		$this->addStyle('ionicons.css', [
			'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true
		]);
		$this->addStyle('hamburgers.css', [
			'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true
		]);
		$this->addStyle('main.css', [
			'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true
		]);

		$this->addScript('general.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		]);
		$this->addScript('legacy/general.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		]);

		// Add cookie scripts:
		$this->addScript('cookies.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		]);
		$this->addScript('legacy/cookies.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		]);

		// Comment-Assets:
		$this->addStyle('comments.css', [
			'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true
		]);
		$this->addScript('comments.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		]);
		$this->addScript('legacy/comments.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		]);

		// Custom Dev output
		$devOutput = $this->addComponent('DevOutput', ['globalName' => 'dev_output']);
		wire('twack')->registerDevEchoComponent($devOutput);

		// Create Layout Components:
		$this->addComponent('HeaderComponent', ['globalName' => 'header']);
		$this->addComponent('FooterComponent', ['globalName' => 'footer']);
		$this->addComponent('SidebarComponent', ['globalName' => 'sidebar', 'directory' => '']);
		$this->breadcrumbs = $this->addComponent('BreadcrumbsComponent', ['name' => 'breadcrumbs', 'directory' => 'partials']);


		$this->addComponent('FormsComponent', ['globalName' => 'forms', 'directory' => '']);

		$this->projectComponent = null;
		$this->initPasswordLock();

		// Add default component automatically. Can be removed by $general->resetComponents(); again
		// A locked page without access gets no content components at all.
		$projectservice = $this->getService('ProjectService');
		if ($projectservice->isProjectPage()) {
			$projectComponent       = $this->addComponent('ProjectPage', ['directory' => 'pages']);
			$this->projectComponent = $projectComponent;
			if (!$this->locked) {
				$projectComponent->addComponent('DefaultPage', ['directory' => 'pages']);
			}
		} elseif (!$this->locked) {
			$this->addComponent('DefaultPage', ['directory' => 'pages']);
		}

		if (!$this->locked) {
			$this->addComponent('Alerts', ['directory' => 'partials', 'name' => 'alerts', 'useField' => 'alerts']);
		}
	}

	/**
	 * Password protection (module PageAccessPassword) of the page itself:
	 * - locked: the page is protected and the current user has no access, so
	 *   only its header data is delivered.
	 * - unlockKey: for a protected page the user has access to, a key for the
	 *   file requests of the page (images cannot send the user's token). A
	 *   valid key from the query parameter "unlock" is passed on unchanged,
	 *   so a key is never extended without the password. A new key is only
	 *   created for editors and account grants that send no valid key.
	 */
	protected function initPasswordLock() {
		$this->locked    = false;
		$this->unlockKey = null;

		$passwordModule = $this->wire('modules')->get('PageAccessPassword');
		if (!$passwordModule || !$passwordModule->isLocked($this->page)) {
			return;
		}

		$unlockKey = $this->wire('input')->get('unlock');
		if (!is_string($unlockKey) || $unlockKey === '') {
			$unlockKey = null;
		}

		if ($unlockKey !== null && $passwordModule->hasAccess($this->page, $unlockKey, $this->wire('users')->getGuestUser())) {
			// A valid key is passed on unchanged, also for editors and account grants,
			// so the answer (hash) and the file URLs stay the same until it expires.
			$this->unlockKey = [
				'unlock_key' => $unlockKey,
				'expires'    => (int) explode('.', $unlockKey, 2)[0]
			];
		} elseif ($passwordModule->hasAccess($this->page)) {
			// Editor or account grant without a valid key
			$this->unlockKey = $passwordModule->createUnlockKey($this->page);
		} else {
			$this->locked = true;
		}
	}

	/**
	 * Adds an additional meta tag
	 * @param string $metatag  	Metatag string (including html)
	 */
	public function addMeta($metaname, $metatag) {
		if (is_string($metaname) && !empty($metaname) && is_string($metatag) && !empty($metatag)) {
			$this->metas->{$metaname} = $metatag;
		}
	}

	public function getAjax($ajaxArgs = []) {
		if (!empty($this->wire('input')->get->text('showOnly'))) {
			$ajaxArgs['showOnly'] = $this->wire('input')->text('showOnly');
		}

		if ($this->locked) {
			return $this->getLockedAjax($ajaxArgs);
		}

		$output = $this->getAjaxOf($this->page);
		$output['locked'] = false;
		if (!empty($this->unlockKey)) {
			$output['unlock_key'] = $this->unlockKey['unlock_key'];
			$output['expires']    = $this->unlockKey['expires'];
		}

		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax($ajaxArgs);
				if (empty($ajax)) {
					continue;
				}
				$output = array_merge($output, $ajax);
			}
		}

		if (!empty($this->componentLists)) {
			$listKeys = $this->componentLists->getKeys();
			foreach ($listKeys as $listname) {
				if (empty($listname)) {
					continue;
				}

				$list = $this->componentLists[$listname];
				if (empty($list) || empty($listname)) {
					continue;
				}

				$output[$listname] = [];
				foreach ($list as $component) {
					$ajax = $component->getAjax($ajaxArgs);
					if (empty($ajax)) {
						continue;
					}
					$output[$listname][] = $ajax;
				}
			}
		}

		// Project infos
		$projectPage = $this->getService('ProjectService')->getProjectPage($this->page);
		if ($projectPage instanceof Page && !!$projectPage->id) {
			$output['project_id'] = $projectPage->id;
		}

		$alertsComponent = $this->getComponent('alerts');
		if ($alertsComponent) {
			$output['alerts'] = $alertsComponent->getAjax();
		}

		if ($this->breadcrumbs && $this->breadcrumbs instanceof TwackComponent) {
			$output = array_merge($output, $this->breadcrumbs->getAjax($ajaxArgs));
		}

		$output['language'] = wire('user')->language->name;

		$output['seo'] = $this->getService('SeoService')->getSeoAjax($this->page);

		return $output;
	}

	/**
	 * Header data of a locked page the current user has no access to. Lists
	 * every field on purpose, so that no content of child components gets in.
	 */
	protected function getLockedAjax($ajaxArgs = []) {
		$output = $this->getAjaxOf($this->page);

		if ($this->page->template->hasField('headline') && !empty((string) $this->page->headline)) {
			$output['title'] = $this->page->headline;
		}

		$output['locked'] = true;

		if ($this->projectComponent instanceof TwackComponent) {
			$projectAjax = $this->projectComponent->getAjax($ajaxArgs);
			foreach (['isProjectPage', 'project', 'color'] as $key) {
				if (array_key_exists($key, $projectAjax)) {
					$output[$key] = $projectAjax[$key];
				}
			}
		}

		$projectPage = $this->getService('ProjectService')->getProjectPage($this->page);
		if ($projectPage instanceof Page && !!$projectPage->id) {
			$output['project_id'] = $projectPage->id;
		}

		if ($this->page->template->hasField('datetime_from') && !empty($this->page->datetime_from)) {
			$output['datetime_from'] = $this->page->getUnformatted('datetime_from');
		}

		if ($this->breadcrumbs && $this->breadcrumbs instanceof TwackComponent) {
			$output = array_merge($output, $this->breadcrumbs->getAjax($ajaxArgs));
		}

		$output['language'] = wire('user')->language->name;

		// The description may fall back to the intro of the page, so only the site wide default is used.
		$seoService         = $this->getService('SeoService');
		$seo                = $seoService->getSeoAjax($this->page);
		$seo['description'] = $seoService->getDefaultSeoAjax()['description'];
		$seo['image']       = null;
		$seo['noindex']     = true;
		$output['seo']      = $seo;

		return $output;
	}
}
