<?php namespace ProcessWire;

class SebiMusicalFabrikFunktionen extends WireData implements Module {

	public static function getModuleInfo() {
		return array(
			'title' => 'MusicalFabrik-Funktionen',
			'version' => '0.4.2',
			'summary' => 'Hooks und Basisfunktionen für die MusicalFabrik-Seite',
			'singular' => true,
			'autoload' => true,
			'icon' => 'anchor',
			'requires' => array('Authenticator', 'IncognitoPatchHelper', 'PHP>=5.5.0', 'ProcessWire>=3.0.0'),
			);
	}

	public function init() {
		$this->pages->addHookAfter('save', $this, 'hookPageSave');
		$this->pages->addHookAfter('saveReady', $this, 'hookPageSaveReady');
		$this->addHookBefore('Page::render', $this, 'hookPageRender');
	}

	public function hookPageSave(HookEvent $event) {
		$seite = $event->arguments[0];
		if (!$seite instanceof Page || !$seite->id) {
			return;
		}

		if ($this->startsWith($seite->template->name, 'projekt')) {
			// if ($seite->children('template.name=staffeln_container')->count <= 0) {
			// 	$patchHelperModul = wire('modules')->get('IncognitoPatchHelper');
			// 	$patch = $patchHelperModul->getNewPatchHelper('musicalfabrik_funktionen', 'skip', true);

			// 	$patch->createPageWithParent('staffeln', 'staffeln_container', $seite, [
			// 		'published' => true,
			// 		'title' => 'Staffeln'
			// 		]);

			// 	$staffelnSeite = $seite->children('template.name=staffeln_container')->first;

			// 	$patch->createPageWithParent('staffel-1', 'staffel', $staffelnSeite, [
			// 		'published' => true,
			// 		'title' => 'Staffel 1'
			// 		]);
			// 	$patch->createPageWithParent('staffel-2', 'staffel', $staffelnSeite, [
			// 		'published' => true,
			// 		'title' => 'Staffel 2'
			// 		]);
			// }

			if ($seite->numChildren > 0) {
				return;
			}

			$patchHelperModul = wire('modules')->get('IncognitoPatchHelper');
			$patch = $patchHelperModul->getNewPatchHelper('musicalfabrik_funktionen', 'skip', true);

			$patch->createPageWithParent('tickets-und-infos', 'standardseite', $seite, [
				'published' => true,
				'title' => 'Tickets & Infos'
				]);

			$patch->createPageWithParent('aktuelles', 'beitraege_uebersicht', $seite, [
				'published' => true,
				'title' => 'Aktuelles'
				]);

			$patch->createPageWithParent('mitwirkenden_portraits', 'portraits_container', $seite, [
				'published' => true,
				'title' => 'Mitwirkenden-Portraits'
				]);

			$patch->createPageWithParent('rollen', 'rollen_container', $seite, [
				'published' => true,
				'title' => 'Rollen'
				]);

			$patch->createPageWithParent('besetzungen', 'besetzungen_container', $seite, [
				'published' => true,
				'title' => 'Besetzungen'
				]);

			$patch->createPageWithParent('staffeln', 'staffeln_container', $seite, [
				'published' => true,
				'title' => 'Staffeln'
				]);

			if ($seite->children('template.name=staffeln_container')->count > 0) {
				$staffelnSeite = $seite->children('template.name=staffeln_container')->first;

				$patch->createPageWithParent('staffel-1', 'staffel', $staffelnSeite, [
					'published' => true,
					'title' => 'Staffel 1'
					]);
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
			$schlagwortFarbe = false;
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

					// Freischaltungszeitpunkte übernehmen, wenn gesetzt:
					if ($projektseite->freischaltungszeitpunkt_zeitpunkt_ab_aktivieren) {
						$schlagwortSeite->freischaltungszeitpunkt_zeitpunkt_ab_aktivieren = $projektseite->freischaltungszeitpunkt_zeitpunkt_ab_aktivieren;
						$schlagwortSeite->freischaltungszeitpunkt_zeitpunkt_ab = $projektseite->freischaltungszeitpunkt_zeitpunkt_ab;
					}
					if ($projektseite->freischaltungszeitpunkt_zeitpunkt_bis_aktivieren) {
						$schlagwortSeite->freischaltungszeitpunkt_zeitpunkt_bis_aktivieren = $projektseite->freischaltungszeitpunkt_zeitpunkt_bis_aktivieren;
						$schlagwortSeite->freischaltungszeitpunkt_zeitpunkt_bis = $projektseite->freischaltungszeitpunkt_zeitpunkt_bis;
					}
				}

				if ($schlagwortFarbe) {
					$schlagwortSeite->farbe = $schlagwortFarbe;
				}

				$schlagwortSeite->save(null, ['adjustName' => true]);

				if (!$seite->schlagwoerter->has('id='.$schlagwortSeite)) {
					$seite->schlagwoerter->add($schlagwortSeite);
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
