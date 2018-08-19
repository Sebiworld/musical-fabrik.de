<?php
namespace ProcessWire;

class Suche extends TwackComponent {

	protected $uebersichtsseitenProvider;

	public function __construct($args) {
		parent::__construct($args);

		$this->uebersichtsseitenProvider = $this->getProvider('UebersichtsseitenProvider');

		$durchsuchbareTemplates = array('beitrag', 'projekt', 'home', 'standardseite');

		$suchseite = wire('pages')->get('template.name=suche');
		if ($suchseite instanceof Page && $suchseite->id) {
			$this->suchseite = $suchseite;
		}

		$this->suchbegriff = wire('sanitizer')->text(wire('input')->get('suchbegriff'));
		$this->ergebnisse = wire('pages')->find('template.name=' . implode('|', $durchsuchbareTemplates) . ', title%='.$this->suchbegriff);
		$this->ergebnisse = $this->uebersichtsseitenProvider->formatieren(
			$this->ergebnisse,
			['limit' => 150]
		);
	}
}
