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

		$this->addComponent('FormsComponent', ['globalName' => 'forms', 'directory' => '']);

		// Add default component automatically. Can be removed by $general->resetComponents(); again
		$projectservice = $this->getService('ProjectService');
		if ($projectservice->isProjectPage()) {
			$projectComponent = $this->addComponent('ProjectPage', ['directory' => 'pages']);
			$projectComponent->addComponent('DefaultPage', ['directory' => 'pages']);
		} else {
			$this->addComponent('DefaultPage', ['directory' => 'pages']);
		}

		if ($this->wire('config')->noindex === true && $this->page->template->hasField('seo')) {
			$this->page->seo->robots_noIndex = true;
			$this->page->seo->robots_noFollow = true;

			$field = $this->wire('fields')->get('name=seo');
			$field->robots_noIndex = 1;
			$field->robots_noFollow = 1;
			$field->sitemap_include = 0;
			$field->save();
		}

		// $this->setSeoTags();

		$this->addComponent('Alerts', ['directory' => 'partials', 'name' => 'alerts', 'useField' => 'alerts']);
	}

	protected function setSeoTags() {
		$configPage = $this->configurationService->getConfigurationPage();

		$this->metaData = [
			'title'       => 'Musical-Fabrik - ' . $this->page->title,
			'site_name'   => '',
			'author'      => '',
			'description' => '',
			'canonical'   => '',
			'keywords'    => '',
			'image'       => '',
			'robots'      => '',
			'type'        => 'website'
		];

		$this->metaData['site_name'] = $configPage->short_text;
		$this->metaData['author']    = $configPage->short_text;

		// Description aus Einleitungs-Feld erzeugen:
		if ($this->page->hasField('intro') && !empty($this->page->intro)) {
			$this->metaData['description'] = Twack::wordLimiter($this->page->intro, 160);
		} elseif ($configPage->short_description && !empty($configPage->short_description)) {
			$this->metaData['description'] = $configPage->short_description;
		}

		$this->metaData['canonical'] = $this->page->httpUrl;

		// Use main image as image, if available:
		if ($this->page->hasField('main_image') && $this->page->main_image && !empty($this->page->main_image)) {
			$this->metaData['image'] = $this->page->main_image->httpUrl;
		} elseif ($configPage->main_image && !empty($configPage->main_image)) {
			$this->metaData['image'] = $configPage->main_image->httpUrl;
		}

		// Accept settings from the SEO module, if available:
		if (is_object($this->page->seo)) {
			var_dump($this->page->seo);
			die();
			$seo = $this->page->seo;
			if (isset($seo->title) && is_string($seo->title) && !empty($seo->title)) {
				$this->metaData['title'] = $seo->title;
			}
			if (isset($seo->site_name) && is_string($seo->site_name) && !empty($seo->site_name)) {
				$this->metaData['site_name'] = $seo->site_name;
			}
			if (isset($seo->description) && is_string($seo->description) && !empty($seo->description)) {
				$this->metaData['description'] = $seo->description;
			}
			if (isset($seo->author) && is_string($seo->author) && !empty($seo->author)) {
				$this->metaData['author'] = $seo->author;
			}
			if (isset($seo->keywords) && is_string($seo->keywords) && !empty($seo->keywords)) {
				$this->metaData['keywords'] = $seo->keywords;
			}
			if (isset($seo->image) && is_string($seo->image) && !empty($seo->image)) {
				$this->metaData['image'] = $seo->image;
			}
			if (isset($seo->canonical) && is_string($seo->canonical) && !empty($seo->canonical)) {
				$this->metaData['canonical'] = $seo->canonical;
			}
			if (isset($seo->robots) && is_string($seo->robots) && !empty($seo->robots)) {
				$this->metaData['robots'] = $seo->robots;
			}
			if (isset($seo->generator) && is_string($seo->generator) && !empty($seo->generator)) {
				$this->metaData['generator'] = $seo->generator;
			}
			if (isset($seo->{'og:site_name'}) && is_string($seo->{'og:site_name'}) && !empty($seo->{'og:site_name'})) {
				$this->metaData['site_name'] = $seo->{'og:site_name'};
			}
			if (isset($seo->{'twitter:site'}) && is_string($seo->{'twitter:site'}) && !empty($seo->{'twitter:site'})) {
				$this->metaData['twitter:site'] = $seo->{'twitter:site'};
			}
			if (isset($seo->custom) && is_array($seo->custom) && !empty($seo->custom)) {
				$this->metaData = array_merge($this->metaData, $seo->custom);
			}
		}

		// Generate Meta-Tags:
		foreach ($this->metaData as $metaname => $metacontent) {
			if (empty($metacontent)) {
				continue;
			}

			if ($metaname == 'canonical') {
				$this->addMeta('canonical-link', "<link rel=\"canonical\" href=\"{$metacontent}\" />");
			} elseif ($metaname == 'title') {
				$metacontent = htmlspecialchars($metacontent);
				$this->addMeta('title-tag', "<title>{$metacontent}</title>");
				$this->addMeta('title-og', "<meta property=\"og:title\" content=\"{$metacontent}\" />");
				$this->addMeta('title-twitter', "<meta name=\"twitter:title\" content=\"{$metacontent}\" />");
			} elseif ($metaname == 'site_name') {
				$metacontent = htmlspecialchars($metacontent);
				$this->addMeta('site_name', "<meta name=\"site_name\" content=\"{$metacontent}\" />");
				$this->addMeta('site_name-og', "<meta property=\"og:site_name\" content=\"{$metacontent}\" />");
				$this->addMeta('site-twitter', "<meta name=\"twitter:site\" content=\"{$metacontent}\" />");
			} elseif ($metaname == 'description') {
				$metacontent = htmlspecialchars($metacontent);
				$this->addMeta('description', "<meta name=\"description\" content=\"{$metacontent}\" />");
				$this->addMeta('description-og', "<meta property=\"og:description\" content=\"{$metacontent}\" />");
				$this->addMeta('description-twitter', "<meta name=\"twitter:description\" content=\"{$metacontent}\" />");
			} elseif ($metaname == 'image') {
				$this->addMeta('image', "<meta name=\"image\" content=\"{$metacontent}\" />");
				$this->addMeta('image-og', "<meta property=\"og:image\" content=\"{$metacontent}\" />");
				$this->addMeta('image-twitter', "<meta name=\"twitter:image\" content=\"{$metacontent}\" />");
			} else {
				$metacontent = htmlspecialchars($metacontent);
				$this->addMeta($metaname, "<meta name=\"{$metaname}\" content=\"{$metacontent}\" />");
			}
		}
		$this->addMeta('type-og', '<meta property="og:type" content="website" />');
		$this->addMeta('url-og', "<meta property=\"og:url\" content=\"{$this->page->httpUrl}\" />");
		$this->addMeta('card-twitter', '<meta name="twitter:card" content="summary" />');
		$this->addMeta('url-twitter', "<meta name=\"twitter:url\" content=\"{$this->page->httpUrl}\" />");
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

		$output['language'] = wire('user')->language->name;

		return $output;
	}
}
