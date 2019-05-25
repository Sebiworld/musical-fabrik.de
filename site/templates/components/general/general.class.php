<?php
namespace ProcessWire;

class General extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->konfigurationService = $this->getService('KonfigurationService');

		// Hier werden zusätzliche Meta-Angaben gesammelt:
		$this->metaangaben = new WireData();

		// general soll global verfügbar sein
		$this->twack->makeComponentGlobal($this, 'general');

		// Main-Scripte für alle Seiten hinzufügen:
		$this->addStyle(wire('config')->urls->templates . 'assets/css/bootstrap.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/swiper.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/lightgallery.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/starability.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/ionicons.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/hamburgers.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/main.min.css', true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/general.min.js', true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/general.legacy.min.js', true);

		// Cookie-Skripte hinzufügen:
		// $this->addStyle(wire('config')->urls->templates . 'assets/css/cookies.min.css', true, false);
		$this->addScript(wire('config')->urls->templates . 'assets/js/cookies.min.js', true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/cookies.legacy.min.js', true);

		// Kommentar-Javascripts:
		$this->addStyle(wire('config')->urls->templates . 'assets/css/comments.min.css', true, false);
		$this->addScript(wire('config')->urls->templates . 'assets/js/comments.min.js', true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/comments.legacy.min.js', true);
		// $this->addStyle(wire('config')->urls->FieldtypeComments . 'comments.css', true);
		// $this->addScript(wire('config')->urls->FieldtypeComments . 'comments.min.js', true);
		// $this->addScript(wire('config')->urls->FieldtypeComments . 'comments.legacy.min.js', true);

		// Eigene Dev-Ausgabe
		$devAusgabe = $this->addComponent('DevAusgabe', ['globalName' => 'dev_ausgabe']);
		$this->twack->registerDevEchoComponent($devAusgabe);

		// Layout-Komponenten anlegen:
		$this->addComponent('SeitenHeader', ['globalName' => 'header']);
		$this->addComponent('SeitenFooter', ['globalName' => 'footer']);
		$this->addComponent('Seitenleiste', ['globalName' => 'seitenleiste', 'directory' => '']);

		$modalKomponente = $this->addComponent('Modals', ['globalName' => 'modals', 'directory' => '']);
		$bildModal = $modalKomponente->addComponent('BildModal', [
			'id' => 'einzelbild_modal',
			'globalName' => 'einzelbild_modal',
			'einzelbild' => true
			]);
		$this->addGlobalParameters(['einzelbildModalID' => $bildModal->getID()]);

		$this->addComponent('Formulare', ['globalName' => 'formulare', 'directory' => '']);

		// Standard-Komponente automatisch hinzufügen. Kann durch $general->resetKomponenten(); wieder entfernt werden
		$this->addComponent('Projektseiten', ['directory' => 'seiten']);
		$this->addComponent('Standardseite', ['directory' => 'seiten']);

		$this->setSeoTags();
	}

	protected function setSeoTags() {
		$konfigurationsseite = $this->konfigurationService->getKonfigurationsseite();

		$metaangaben = array(
			'title' => "Musical-Fabrik - " . $this->page->title,
			'site_name' => '',
			'author' => '',
			'description' => '',
			'canonical' => '',
			'keywords' => '',
			'image' => '',
			'robots' => '',
			'type' => 'website'
		);

		$metaangaben['site_name'] = $konfigurationsseite->kurztext;
		$metaangaben['author'] = $konfigurationsseite->kurztext;

		// Description aus Einleitungs-Feld erzeugen:
		if ($this->page->hasField('einleitung') && !empty($this->page->einleitung)) {
			$metaangaben['description'] = Twack::wordLimiter($this->page->einleitung, 160);
		} elseif ($konfigurationsseite->kurzbeschreibung && !empty($konfigurationsseite->kurzbeschreibung)) {
			$metaangaben['description'] = $konfigurationsseite->kurzbeschreibung;
		}

		$metaangaben['canonical'] = $this->page->httpUrl;

		// Titelbild als Image einsetzen, wenn vorhanden:
		if ($this->page->hasField('titelbild') && $this->page->titelbild && !empty($this->page->titelbild)) {
			$metaangaben['image'] = $this->page->titelbild->httpUrl;
		} elseif ($konfigurationsseite->titelbild && !empty($konfigurationsseite->titelbild)) {
			$metaangaben['image'] = $konfigurationsseite->titelbild->httpUrl;
		}

		// Einstellungen aus dem SEO-Modul übernehmen, wenn vorhanden:
		if (is_object($this->page->seo)) {
			$seo = $this->page->seo;
			if (isset($seo->title) && is_string($seo->title) && !empty($seo->title)) {
				$metaangaben['title'] = $seo->title;
			}
			if (isset($seo->site_name) && is_string($seo->site_name) && !empty($seo->site_name)) {
				$metaangaben['site_name'] = $seo->site_name;
			}
			if (isset($seo->description) && is_string($seo->description) && !empty($seo->description)) {
				$metaangaben['description'] = $seo->description;
			}
			if (isset($seo->author) && is_string($seo->author) && !empty($seo->author)) {
				$metaangaben['author'] = $seo->author;
			}
			if (isset($seo->keywords) && is_string($seo->keywords) && !empty($seo->keywords)) {
				$metaangaben['keywords'] = $seo->keywords;
			}
			if (isset($seo->image) && is_string($seo->image) && !empty($seo->image)) {
				$metaangaben['image'] = $seo->image;
			}
			if (isset($seo->canonical) && is_string($seo->canonical) && !empty($seo->canonical)) {
				$metaangaben['canonical'] = $seo->canonical;
			}
			if (isset($seo->robots) && is_string($seo->robots) && !empty($seo->robots)) {
				$metaangaben['robots'] = $seo->robots;
			}
			if (isset($seo->generator) && is_string($seo->generator) && !empty($seo->generator)) {
				$metaangaben['generator'] = $seo->generator;
			}
			if (isset($seo->{'og:site_name'}) && is_string($seo->{'og:site_name'}) && !empty($seo->{'og:site_name'})) {
				$metaangaben['site_name'] = $seo->{'og:site_name'};
			}
			if (isset($seo->{'twitter:site'}) && is_string($seo->{'twitter:site'}) && !empty($seo->{'twitter:site'})) {
				$metaangaben['twitter:site'] = $seo->{'twitter:site'};
			}
			if (isset($seo->custom) && is_array($seo->custom) && !empty($seo->custom)) {
				$metaangaben = array_merge($metaangaben, $seo->custom);
			}
		}

		// Twack::devEcho($metaangaben);

		// Meta-Tags generieren:
		foreach ($metaangaben as $metaname => $metacontent) {
			if (empty($metacontent)) {
				continue;
			}

			if ($metaname == 'canonical') {
				$this->addMeta('canonical-link', "<link rel=\"canonical\" href=\"{$metacontent}\" />");
			} elseif ($metaname == 'title') {
				$this->addMeta('title-tag', "<title>{$metacontent}</title>");
				$this->addMeta('title-og', "<meta property=\"og:title\" content=\"{$metacontent}\" />");
				$this->addMeta('title-twitter', "<meta name=\"twitter:title\" content=\"{$metacontent}\" />");
			} elseif ($metaname == 'site_name') {
				$this->addMeta('site_name', "<meta name=\"site_name\" content=\"{$metacontent}\" />");
				$this->addMeta('site_name-og', "<meta property=\"og:site_name\" content=\"{$metacontent}\" />");
				$this->addMeta('site-twitter', "<meta name=\"twitter:site\" content=\"{$metacontent}\" />");
			} elseif ($metaname == 'description') {
				$this->addMeta('description', "<meta name=\"description\" content=\"{$metacontent}\" />");
				$this->addMeta('description-og', "<meta property=\"og:description\" content=\"{$metacontent}\" />");
				$this->addMeta('description-twitter', "<meta name=\"twitter:description\" content=\"{$metacontent}\" />");
			} elseif ($metaname == 'image') {
				$this->addMeta('image', "<meta name=\"image\" content=\"{$metacontent}\" />");
				$this->addMeta('image-og', "<meta property=\"og:image\" content=\"{$metacontent}\" />");
				$this->addMeta('image-twitter', "<meta name=\"twitter:image\" content=\"{$metacontent}\" />");
			} else {
				$this->addMeta($metaname, "<meta name=\"{$metaname}\" content=\"{$metacontent}\" />");
			}
		}
		$this->addMeta('type-og', "<meta property=\"og:type\" content=\"website\" />");
		$this->addMeta('url-og', "<meta property=\"og:url\" content=\"{$this->page->httpUrl}\" />");
		$this->addMeta('card-twitter', "<meta name=\"twitter:card\" content=\"summary\" />");
		$this->addMeta('url-twitter', "<meta name=\"twitter:url\" content=\"{$this->page->httpUrl}\" />");
	}

	/**
	 * Fügt ein zusätzliches Metatag hinzu
	 * @param string $metatag  	Metatag-String (inklusive Html)
	 */
	public function addMeta($metaname, $metatag) {
		if (is_string($metaname) && !empty($metaname) && is_string($metatag) && !empty($metatag)) {
			$this->metaangaben->{$metaname} = $metatag;
		}
	}

	public function getAjax() {
		$output = $this->getAjaxOf($this->page);

		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax();
				if(empty($ajax)) continue;
				$output = array_merge($output, $ajax);
			}
		}

		return $output;
	}
}
