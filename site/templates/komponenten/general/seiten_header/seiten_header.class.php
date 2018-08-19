<?php
namespace ProcessWire;

class SeitenHeader extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$headerseite = wire('pages')->get('template=header');

		// Titelbild:
		$this->logo = $headerseite->titelbild;

		// Überschrift:
		$this->ueberschrift = $headerseite->ueberschrift;

		// Navigation holen:
		$this->navigation = new PageArray();
		if ($headerseite->template->hasField('hauptnavigation') && count($headerseite->hauptnavigation) > 0) {
			foreach ($headerseite->hauptnavigation as $navigationspunkt) {
				if ($navigationspunkt->type === 'seite') {
					$navigationspunkt->link = '';

					if ($navigationspunkt->template->hasField('seite') && $navigationspunkt->seite->id) {
						if (!$navigationspunkt->seite->viewable()) {
							continue;
						}
						$navigationspunkt->link .= $navigationspunkt->seite->url;

						if ($navigationspunkt->seite->id == $this->page->id) {
							$navigationspunkt->aktiv = true;
						}
					}

					if ($navigationspunkt->template->hasField('sektion_name') && $navigationspunkt->sektion_name) {
						$navigationspunkt->link .= '#' . $navigationspunkt->sektion_name;

						// Bei Hashvalues wird die Aktivierung von Menüpunkten per Javascript geregelt:
						$navigationspunkt->aktiv = false;
					}

					$this->navigation->add($navigationspunkt);
				} elseif ($navigationspunkt->type === 'link') {
					$this->navigation->add($navigationspunkt);
				}
			}
		}

		$this->idProvider = $this->getProvider('IdProvider');
		$this->hauptmenueID = $this->idProvider->getID('hauptmenue');
		$this->dropdownID = $this->idProvider->getID('header_dropdown');
		$this->dropdownLabelID = $this->idProvider->getID('header_dropdown_label');

		// Sekundäre Navigation holen:
		$this->sekundaere_navigation = new PageArray();
		if ($headerseite->template->hasField('sekundaere_navigation') && count($headerseite->sekundaere_navigation) > 0) {
			foreach ($headerseite->sekundaere_navigation as $navigationspunkt) {
				if ($navigationspunkt->type === 'seite') {
					$navigationspunkt->link = '';
					if ($navigationspunkt->template->hasField('seite') && $navigationspunkt->seite->id) {
						if (!$navigationspunkt->seite->viewable()) {
							continue;
						}
						$navigationspunkt->link .= $navigationspunkt->seite->url;
					}
					if ($navigationspunkt->template->hasField('sektion_name') && $navigationspunkt->sektion_name) {
						$navigationspunkt->link .= '#' . $navigationspunkt->sektion_name;
					}
					$this->sekundaere_navigation->add($navigationspunkt);
				} elseif ($navigationspunkt->type === 'link') {
					$this->sekundaere_navigation->add($navigationspunkt);
				}
			}
		}

		if ($headerseite->template->hasField('domain_alternativen') && $headerseite->domain_alternativen->count() > 0) {
			foreach ($headerseite->domain_alternativen as $alternative) {
				if (!$alternative->regex || empty($alternative->regex)) {
					continue;
				}

				if (!preg_match($alternative->regex, wire('input')->httpUrl(true))) {
					continue;
				}

				// Der Regex passt! Feldwerte überschreiben.
				if ($alternative->template->hasField('titelbild') && $alternative->titelbild) {
					$this->heroTitelbild = $alternative->titelbild;
				}

				if ($alternative->template->hasField('hintergrundbild') && $alternative->hintergrundbild) {
					$this->heroHintergrund = $alternative->hintergrundbild;
				}

				break;
			}
		}
		
		$this->addScript(wire('config')->urls->templates . 'assets/js/animated-header.min.js', true);
	}

	public function getLogo() {
		return $this->logo;
	}
}
