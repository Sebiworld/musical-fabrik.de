<?php
namespace ProcessWire;

?>
<div class="filtereinstellungen">
	<div class="form-row">
		<?php
		if ($this->schlagwoerter) {
			?>
			<div class="col-12 col-md-6">
				<label>Wählen Sie Schlagwörter aus:</label>
				<?= $this->schlagwoerter; ?>
			</div>
			<?php
		}
		?>

		<div class="col-12 col-md-6">
			<div class="form-group">
				<label for="freitextsuche">Freitextsuche</label>
				<input type="text" class="form-control" id="freitextsuche" name="freitextsuche" placeholder="Suchbegriff eingeben" value="<?= $this->freitextsuche; ?>"/>
			</div>
			<button type="submit" class="btn btn-projekt-primary" name="suchen">Suchen</button>
		</div>
	</div>
</div>