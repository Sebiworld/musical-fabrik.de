<?php
namespace ProcessWire;

class SchlagwoerterBox extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (isset($args['aktiv'])) {
			// Es wurden aktive Schlagwörter übergeben.
			if (is_string($args['aktiv'])) {
				$args['aktiv'] = explode(',', $args['aktiv']);
			}
			if (is_array($args['aktiv'])) {
				foreach (array_keys($args['aktiv'], "") as $k) {
					unset($args['aktiv'][$k]); // Leere Elemente entfernen
				}
				$this->aktiv = $args['aktiv'];
			}
		}

		$this->selektierbar = isset($args['selektierbar']) && !!$args['selektierbar'];

		$this->schlagwoerter = $this->getSchlagwoerterGewichtet($args);

		// usort($this->parameter['schlagwoerter'], function ($a, $b) {
		// 	return strcmp($a['title'], $b['title']);
		// }); // Schlagwörter alphabetisch sortieren

		// Schlagwörter zufällig anordnen?
		if (isset($args['geordnet']) && $args['geordnet'] === false) {
			shuffle($this->schlagwoerter);
		}

		$aktuellesService = $this->getService('AktuellesService');
		$this->aktuellesSeite = wire('pages')->get('/')->children('template.name=beitraege_uebersicht')->first();

		$this->anzahl_anzeigen = isset($args['anzahl_anzeigen']) && !!$args['anzahl_anzeigen'];
		$this->css_anzahl_klasse_hinzufuegen = !isset($args['css_anzahl_klasse_hinzufuegen']) || !!$args['css_anzahl_klasse_hinzufuegen'];

		// $this->addStyle(wire('config')->urls->templates . 'assets/css/schlagwoerter-box.min.css', true, true);
	}

	public function getAjax() {

		$schlagwoerter = $this->getSchlagwoerterGewichtet();

		return array(
			'schlagwoerter' => $schlagwoerter
		);
	}

	/**
	 * Liefert die IDs der momentan aktiven Schlagwörter als Array
	 */
	public function getAktive() {
		if (!isset($this->aktiv)) {
			return array();
		}
		return $this->aktiv;
	}

	/**
	 * Liefert die Schlagwörter, gewichtet mit der Anzahl ihres Vorkommens auf allen Seiten.
	 * @param  array  $args Mögliche Argumente sind:
	 *                      'nutze_seite' Eine ProcessWire-Page, deren Schlagwörter ausgegeben werden sollen. Alternativ werden alle Schlagwörter geliefert.
	 *                      'anzahl' Die Maximalanzahl der Schlagwörter, die ausgegeben werden sollen
	 * @return array('id', 'title', 'farbe', 'name', 'maximum', 'limit')
	 */
	private function getSchlagwoerterGewichtet($args = array()) {
		if (isset($args['nutze_seite']) && $args['nutze_seite'] instanceof Page) {
			$seite = $args['nutze_seite'];
		}

		$output = array();
		try {
			$result = wire('db')->query("
				SELECT
				field_schlagwoerter.data as id,
				field_title.data as title,
				field_farbe.data as farbe,
				pages.name as name,
				pages.status as status,
				(SELECT MAX(anzahl) FROM (
				SELECT count(pages_id) as anzahl FROM field_schlagwoerter GROUP BY data
				) as anzahl_sub) as maximum,
				count(CASE seite.status WHEN seite.status < 1025 THEN 1 else NULL end) as anzahl
				". (isset($seite) ? ", SUM(CASE WHEN seite.id=".$seite->id." THEN 1 ELSE 0 END) as enthalten": '') ."
				FROM field_schlagwoerter
				INNER JOIN field_title ON (field_schlagwoerter.data=field_title.pages_id)
				INNER JOIN pages ON (field_schlagwoerter.data=pages.id)
				INNER JOIN pages AS seite ON (field_schlagwoerter.pages_id=seite.id)
				LEFT JOIN field_farbe ON (field_schlagwoerter.data=field_farbe.pages_id)
				GROUP BY field_schlagwoerter.data
				HAVING anzahl > 0 AND status < 1025
				"
				.(isset($seite) ? ' AND enthalten > 0': '')
				." ORDER BY anzahl DESC, title ASC"
				.(isset($args['limit']) ? ' LIMIT '.$args['limit'] : ''));

			while ($row = $result->fetch_assoc()) {
				$row['aktiv'] = in_array($row['id'], $this->getAktive());
				$row['schlagwoerter_on_klick'] = $row['id'];

				if ($this->selektierbar) {
					$row['schlagwoerter_on_klick'] = $this->getAktive();
					if ($row['aktiv']) {
						// Das ausgewählte Schlagwort ist aktiv, beim Klick soll es also entfernt werden.
						$row['schlagwoerter_on_klick'] = array_diff($row['schlagwoerter_on_klick'], [$row['id']]);
					} elseif (!empty($this->getAktive())) {
						// Das ausgewählte Schlagwort ist nicht aktiv, aber es gibt aktive Schlagwörter. Beim Klick muss das Schlagwort hinzugefügt werden.
						$row['schlagwoerter_on_klick'][] = $row['id'];
					}
				}
				$output[] = $row;
			}
		} catch (\Exception $e) {
			Twack::devEcho($e->getMessage());
		}

		return $output;
	}
}
