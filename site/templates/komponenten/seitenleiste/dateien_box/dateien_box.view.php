<?php
namespace ProcessWire;

if ($this->dateien && count($this->dateien) > 0) {
	?>
	<div class="box dateien-box">
		<?php
		if ($this->titel) {
			?>
			<h4 class="titel"><?= $this->titel; ?></h4>
			<?php
		}
		?>
		<ul class="dateien">
			<?php
			foreach ($this->dateien as $datei) {
				?>
				<li>
					<a target="_blank" href="<?= $datei->url; ?>" title="Datei herunterladen" rel="noopener">
						<h4 class="titel"><?= $datei->description ? $datei->description : $datei->name; ?></h4>
					</a>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
	<?php
}
?>