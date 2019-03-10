<?php
namespace ProcessWire;

if ($this->schlagwoerter) {
	?>
	<div class="box schlagwoerter <?= $this->selektierbar ? 'selektierbar' : ''; ?>">
		<div class="wolke">
			<?php
			$aktuelleURL = $this->aktuellesSeite->url;

			foreach ($this->schlagwoerter as $schlagwort) {

				$schlagwortseite = wire('pages')->get($schlagwort['id']);
				if(!($schlagwortseite instanceof Page) || !$schlagwortseite->id || !$schlagwortseite->listable()){
					continue;
				}

				$params = $_GET;
				if (isset($schlagwort['schlagwoerter_on_klick']) && !empty($schlagwort['schlagwoerter_on_klick'])) {
					$params['schlagwoerter'] = $schlagwort['schlagwoerter_on_klick'];
				} else {
					unset($params['schlagwoerter']);
				}

				$url = $aktuelleURL . '?' . http_build_query($params);
				?>
				<a
				data-id="<?= $schlagwort['id']; ?>"
				href="<?= $url; ?>"

				<?php
				if (isset($schlagwort['farbe'])) {
					echo "style=\"background-color: #{$schlagwort['farbe']}\"";
				}
				?>

				class="badge <?= isset($schlagwort['farbe']) ? 'hat-hintergrund' : ''; ?> wort <?= $this->css_anzahl_klasse_hinzufuegen ? 'w-' . ((int)ceil($schlagwort['anzahl'] / ($schlagwort['maximum'] / 6))) : '' ?> <?= $schlagwort['aktiv'] ? 'aktiv' : '' ?>">
				<?= $schlagwort['title']; ?> <?= $this->anzahl_anzeigen ? '('.$schlagwort['anzahl'].')' : ''; ?>
			</a>
			<?php
			}
		?>
	</div>
</div>
<?php
}
?>