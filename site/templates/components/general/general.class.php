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

		// Add default component automatically. Can be removed by $general->resetComponents(); again
		$projectservice = $this->getService('ProjectService');
		if ($projectservice->isProjectPage()) {
			$projectComponent = $this->addComponent('ProjectPage', ['directory' => 'pages']);
			$projectComponent->addComponent('DefaultPage', ['directory' => 'pages']);
		} else {
			$this->addComponent('DefaultPage', ['directory' => 'pages']);
		}

		$this->addComponent('Alerts', ['directory' => 'partials', 'name' => 'alerts', 'useField' => 'alerts']);
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

		$output = $this->getAjaxOf($this->page);

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
}
