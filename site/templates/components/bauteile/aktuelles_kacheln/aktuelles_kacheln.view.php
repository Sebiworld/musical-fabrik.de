<?php
namespace ProcessWire;

// Filter ausgeben, wenn vorhanden:
if ($this->filtereinstellungen) {
	echo $this->filtereinstellungen;
}

?>
<div class="aktuelles-kacheln">
	<?php
	if ($this->gesamtAnzahl) {
		?>
		<i class="gesamtanzahl">insgesamt <?= $this->gesamtAnzahl; ?> Beiträge</i>
		<?php
	}
	?>

	<?php
	if ($this->childComponents && count($this->childComponents) > 0) {
		?>
		<div class="masonry-grid">
			<div class="masonry-grid-sizer"></div>
			<?php
			foreach ($this->childComponents as $beitrag) {
				?>
				<div class="masonry-grid-item">
					<?= $beitrag; ?>
				</div>
				<?php
			}
			?>
		</div>

		<?php
		if ($this->hatMehr) {
			?>
			<div class="btn-group" role="group">
				<button type="button" class="btn btn-projekt-primary" data-aktion="weitere_laden" data-offset="<?= $this->letztesElementIndex + 1; ?>">Weitere laden...</button>
			</div>
			<?php
		}
	} else {
		?>
		<div class="masonry-grid">
			<div class="masonry-grid-sizer"></div>
		</div>

		<div class="alert alert-info keine-ergebnisse" role="alert">
			<strong>Keine Beiträge gefunden.</strong><br/>
			Erweitern Sie die Filtereinstellungen, um mehr Ergebnisse zu erhalten.
		</div>
		<?php
	}
	?>
</div>