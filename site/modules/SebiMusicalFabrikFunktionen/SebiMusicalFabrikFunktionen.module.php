<?php
namespace ProcessWire;

class SebiMusicalFabrikFunktionen extends WireData implements Module {

	public static function getModuleInfo() {
		return array(
			'title' => 'MusicalFabrik-Funktionen',
			'version' => '0.4.2',
			'summary' => 'Hooks und Basisfunktionen für die MusicalFabrik-Seite',
			'singular' => true,
			'autoload' => true,
			'icon' => 'anchor',
			'requires' => array('PHP>=5.5.0', 'ProcessWire>=3.0.0'),
		);
	}

	public function init() {
		// Fügt automatisch Unterseiten hinzu:
		$this->pages->addHookAfter('added', $this, 'hookPagesAfterAdded');

		// Felder vorm Speichern ergänzen:
		$this->pages->addHookAfter('saveReady', $this, 'hookPageSaveReady');

		// Schlagwort mit Projekt synchronisieren:
		$this->pages->addHookAfter('save', $this, 'hookPagesAfterSave');

		// Backend-JS nachladen:
		$this->addHookBefore('Page::render', $this, 'hookPageRender');
	}

	public function hookPagesAfterAdded(HookEvent $event) {
		$seite = $event->arguments[0];
		if (!$seite instanceof Page || !$seite->id) {
			return;
		}

		if ($this->startsWith($seite->template->name, 'projekt')) {
			if ($seite->numChildren > 0) {
				// Die Seite hat schon Kindseiten (vlt. andere Seite dupliziert?)
				return;
			}

			// Tickets & Infos Seite anlegen:
			$page = $this->wire(new Page());
			$p->template = 'standardseite';
			$p->parent = $seite;
			$p->name = 'tickets-und-infos';
			$p->title = 'Tickets & Infos';
			$p->published = true;
			$p->save();

			// Aktuelles-Übersicht anlegen:
			$page = $this->wire(new Page());
			$p->template = 'beitraege_uebersicht';
			$p->parent = $seite;
			$p->name = 'aktuelles';
			$p->title = 'Aktuelles';
			$p->published = true;
			$p->save();

			// Portraits-Container anlegen:
			$page = $this->wire(new Page());
			$p->template = 'portraits_container';
			$p->parent = $seite;
			$p->name = 'mitwirkenden_portraits';
			$p->title = 'Mitwirkenden-Portraits';
			$p->published = true;
			$p->save();

			// Rollen-Container anlegen:
			$page = $this->wire(new Page());
			$p->template = 'rollen_container';
			$p->parent = $seite;
			$p->name = 'rollen';
			$p->title = 'Rollen';
			$p->published = true;
			$p->save();

			// Besetzungen-Container anlegen:
			$page = $this->wire(new Page());
			$p->template = 'besetzungen_container';
			$p->parent = $seite;
			$p->name = 'besetzungen';
			$p->title = 'Besetzungen';
			$p->published = true;
			$p->save();

			// Staffeln-Container anlegen:
			$page = $this->wire(new Page());
			$p->template = 'staffeln_container';
			$p->parent = $seite;
			$p->name = 'staffeln';
			$p->title = 'Staffeln';
			$p->published = true;
			$p->save();

			// Erste Staffel automatisch anlegen:
			$staffelnSeite = $seite->children('template.name=staffeln_container')->first;

			if($staffelnSeite instanceof Page && $staffelnSeite->id){
				$page = $this->wire(new Page());
				$p->template = 'staffel';
				$p->parent = $staffelnSeite;
				$p->name = 'spielzeit-1';
				$p->title = 'Spielzeit 1';
				$p->published = true;
				$p->save();
			}

		} elseif ($seite->template->name == 'zeitraum') {
			$elternseiten = wire('pages')->find('zeitraeume='.$seite->id);
			foreach ($elternseiten as $elternseite) {
				$zeitpunktVon = false;
				if (!$elternseite->template->hasField('zeitraeume') || $seite->zeitraeume->count <= 0) {
					$zeitpunktVon = '';
				} else {
					$start = $seite->zeitraeume->filter('sort=zeitpunkt_von')->first();
					$zeitpunktVon = $start->getUnformatted('zeitpunkt_von');
				}

				if ($elternseite->getUnformatted('zeitpunkt_von') != $zeitpunktVon) {
					$elternseite->of(false);
					$elternseite->save();
				}
			}
		}
	}

	public function hookPageSaveReady(HookEvent $event) {
		$seite = $event->arguments[0];

		// Werte vor dem Speichern der Seite ausfüllen:
		if ($seite->template->name == 'termin' && $seite->template->hasField('zeitraeume')) {
			if ($seite->zeitraeume->count > 0) {
				$start = $seite->zeitraeume->filter('sort=zeitpunkt_von')->first();
				$seite->zeitpunkt_von = $start->getUnformatted('zeitpunkt_von');

				$ende = $seite->zeitraeume->filter('sort=-zeitpunkt_bis')->first();
				$seite->zeitpunkt_bis = $ende->getUnformatted('zeitpunkt_bis');
			} else {
				$seite->zeitpunkt_von = '';
				$seite->zeitpunkt_bis = '';
			}
		}

		// Wenn Projektseite: Projektname als Schlagwort setzen. Sonst: "Verein"
		if ($seite->template->hasField('schlagwoerter')) {
			$projektseite = false;
			if ($this->startsWith($seite->template->name, 'projekt')) {
				$projektseite = $seite;
			} else {
				$projektseite = $seite->closest('template^=projekt');
			}

			$schlagwortTitel = 'Verein';
			if ($projektseite instanceof Page && $projektseite->id) {
				$schlagwortTitel = $projektseite->title;
			}

			$schlagwortContainer = wire('pages')->get('template.name=schlagwoerter_container');
			if ($schlagwortContainer instanceof Page && $schlagwortContainer->id) {
				$schlagwortSeite = $schlagwortContainer->get('title='.$schlagwortTitel);

				if (!($schlagwortSeite instanceof Page) || !$schlagwortSeite->id) {
					// Schlagwort neu anlegen:
					$schlagwortSeite = new Page(wire('templates')->get('name=schlagwort'));
					$schlagwortSeite->title = $schlagwortTitel;
				}
				$schlagwortSeite->of(false);

				if ($projektseite instanceof Page && $projektseite->id) {
					// Farbe ins Schlagwort übernehmen, wenn gesetzt:
					if ($projektseite->farbe) {
						$schlagwortSeite->farbe = $projektseite->farbe;
					}

					// Freischaltungszeitpunkte von der Projektseite zum Schlagwort übernehmen, wenn gesetzt:
					$schlagwortSeite->releasetime_start_activate = $projektseite->releasetime_start_activate;
					$schlagwortSeite->releasetime_start = $projektseite->getUnformatted('releasetime_start');

					$schlagwortSeite->releasetime_end_activate = $projektseite->releasetime_end_activate;
					$schlagwortSeite->releasetime_end = $projektseite->getUnformatted('releasetime_end');
				}

				$schlagwortSeite->save(null, ['adjustName' => true]);

				if (!$seite->schlagwoerter->has('id='.$schlagwortSeite)) {
					$seite->schlagwoerter->add($schlagwortSeite);
				}
			}
		}
	}

	public function hookPagesAfterSave(HookEvent $event) {
		$seite = $event->arguments[0];
		if (!$seite instanceof Page || !$seite->id) {
			return;
		}

		if ($this->startsWith($seite->template->name, 'projekt')) {
			$schlagwortContainer = wire('pages')->get('template.name=schlagwoerter_container');
			if ($schlagwortContainer instanceof Page && $schlagwortContainer->id) {
				$schlagwortSeite = $schlagwortContainer->get('title='.$seite->title);
				if($schlagwortSeite instanceof Page && $schlagwortSeite->id){
					// Es gibt ein Schlagwort passend zum Projekt

					$of = $schlagwortSeite->of();
					$schlagwortSeite->of(false);

					// Farbe ins Schlagwort übernehmen, wenn gesetzt:
					if ($seite->farbe) {
						$schlagwortSeite->farbe = $seite->farbe;
					}

					// Freischaltungszeitpunkte von der Projektseite zum Schlagwort übernehmen, wenn gesetzt:
					$schlagwortSeite->releasetime_start_activate = $seite->releasetime_start_activate;
					$schlagwortSeite->releasetime_start = $seite->getUnformatted('releasetime_start');

					$schlagwortSeite->releasetime_end_activate = $seite->releasetime_end_activate;
					$schlagwortSeite->releasetime_end = $seite->getUnformatted('releasetime_end');

					$schlagwortSeite->save();
					$schlagwortSeite->of($of);
				}
			}
		}
	}

	public function hookPageRender(HookEvent $event) {
		$seite = $event->object;
		if ($seite->process === 'ProcessPageEdit') {
			$seite = wire('pages')->get(wire('input')->get->id);
			if (!$seite->id) {
				return;
			}
			if ($seite->template->name === 'portrait') {
				wire('config')->scripts->add(wire('config')->urls->siteModules . 'SebiMusicalFabrikFunktionen/portrait-title-ausfuellen.js');
			}
		}
		return;
	}

	protected function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}
