<?php
namespace ProcessWire;

class SeitenFooter extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$footerseite = wire('pages')->get('template.name=footer');

		// Footerbild
		// Mitglied werden! - Calltoaction
		if ($footerseite->template->hasField('inhalte')) {
			$this->inhalte = $this->addComponent('Inhalte', ['directory' => '', 'page' => $footerseite, 'nutzefeld' => 'inhalte']);
		}

		$this->breadcrumbs = $this->addComponent('BreadcrumbKomponente', ['name' => 'breadcrumbs', 'directory' => 'bauteile']);

		// Header-Menü, Kontakt, Schlagwörter-Wolke
		$this->menue = new PageArray();
		$this->menue->add($this->getGlobalComponent('header')->navigation);
		$this->menue->add($this->getGlobalComponent('header')->sekundaere_navigation);

		$this->adresse = $footerseite->adresse;

		$this->schlagwoerter = $this->addComponent('SchlagwoerterBox', ['directory' => 'bauteile', 'limit' => 20]);

		// Socialmedia-Icons
		if ($footerseite->template->hasField('socialmedia_links') && count($footerseite->socialmedia_links) > 0) {
			$this->socialmedia_links = $footerseite->socialmedia_links;
		}

		//  Startseite, Impressum, Datenschutz, Sitemap links / Copyright rechts
		$this->tertiaere_navigation = new PageArray();
		if ($footerseite->template->hasField('hauptnavigation') && count($footerseite->hauptnavigation) > 0) {
			foreach ($footerseite->hauptnavigation as $navigationspunkt) {
				if ($navigationspunkt->type === 'seite') {
					$navigationspunkt->link = '';
					if ($navigationspunkt->template->hasField('seite') && $navigationspunkt->seite->id) {
						$navigationspunkt->link .= $navigationspunkt->seite->url;
					}
					if ($navigationspunkt->template->hasField('sektion_name') && $navigationspunkt->sektion_name) {
						$navigationspunkt->link .= '#' . $navigationspunkt->sektion_name;
					}
					$this->tertiaere_navigation->add($navigationspunkt);
				} elseif ($navigationspunkt->type === 'link') {
					$this->tertiaere_navigation->add($navigationspunkt);
				}
			}
		}

		$suchseite = wire('pages')->get('template.name=suche');
		if ($suchseite instanceof Page && $suchseite->id) {
			$this->suchseite = $suchseite;
		}

		$this->copyright = "&copy;&nbsp;Copyright " . date('Y') . ' - ' . $footerseite->kurztext;
	}
}
