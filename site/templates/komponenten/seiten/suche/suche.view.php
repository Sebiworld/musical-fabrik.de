<?php
namespace ProcessWire;

?>

<form action="<?= $this->suchseite->url; ?>" method="GET">
	<div class="input-group">
		<input type="text" class="form-control" placeholder="Suche nach..." name="suchbegriff" value="<?= $this->suchbegriff; ?>">
		<span class="input-group-btn">
			<button class="btn btn-primary" type="submit">Suchen</button>
		</span>
	</div>
</form>

<p class="lead">
	Es wurden <?= $this->ergebnisse->count; ?> Ergebnisse gefunden.
</p>

<div class="list-group ohne-rand">
	<?php
	foreach ($this->ergebnisse as $ergebnis) {
		?>
		<a href="<?= $ergebnis->url; ?>" class="list-group-item list-group-item-action flex-column align-items-start">
			<div class="d-flex w-100 justify-content-between">
				<h5><?= $ergebnis->title; ?></h5>
				<small><?= $ergebnis->zeitpunkt_von; ?></small>
			</div>
			<?php
			if ($ergebnis->einleitung) {
				?>
				<p>
					<?= $ergebnis->einleitung; ?>
				</p>
				<?php
			}
			if ($ergebnis->autoren_lesbar) {
				?>
				<small>Von <?= $ergebnis->autoren_lesbar; ?></small>
				<?php
			}
			?>
		</a>
		<?php
	}
	?>
</div>