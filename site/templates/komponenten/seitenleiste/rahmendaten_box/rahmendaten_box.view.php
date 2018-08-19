<?php
namespace ProcessWire;

if ($this->daten && count($this->daten) > 0) {
	?>

	<div class="box rahmendaten-box">
		<?php
		if ($this->titel) {
			?>
			<h4 class="titel"><?= $this->titel; ?></h4>
			<?php
		}
		?>

		<?php
		foreach ($this->daten as $datensatz) {
			?>
			<div class="datensatz ebene-<?= $datensatz->ebene; ?>">
				<?php
				if (isset($datensatz->link)) {
					?>
					<a class="datensatz-link" href="<?= $datensatz->link; ?>" <?= isset($datensatz->linktitle) ? 'title="' . $datensatz->linktitle . '"' : ''; ?>>
						<?= $datensatz->label; ?>
					</a>
					<?php
				} else {
					echo $datensatz->label;
				}
				?>
			</div>
			<?php
		}
		?>

	</div>
	<?php
}
?>