<?php
namespace ProcessWire;

$besetzungen = new WireArray();
foreach($this->alleBesetzungen as $besetzung){
	// Prüfen, ob diese Besetzung in dieser Staffel überhaupt vorkommt:
	if($this->portraits->find('staffel_'.$this->staffel->id.'_'.$besetzung->id.'=1')->count > 0){
		$besetzungen->add($besetzung);
	}
}

// Hat diese Rolle überhaupt Portraits?
if (!empty($this->portraits) && !empty($besetzungen)) {
	// Zuerst werden alle Root-Portraits ausgegeben, also die Portraits, die direkt zu dieser Rolle gehören:
	?>

	<div class="besetzungen-als-block">
		<?php
		$bCounter = 1;
		foreach ($besetzungen as $index => $besetzung) {
			?>
			<div class="besetzung-block">
				<div class="titel">
					<strong><?= $besetzung->title; ?></strong>
					<?= ($besetzung->einleitung) ? '<br/>'.$besetzung->einleitung : ''; ?>
				</div>

				<div class="container-fluid">
					<div class="row portraits-reihe">
						<?php
						$pCounter = 1;
						foreach ($this->portraits->find('staffel_'.$this->staffel->id.'_'.$besetzung->id.'=1, root=1') as $portrait) {
							?>
							<div class="col-6 col-sm-6 col-lg-4 col-xl-3 portrait-block">
								<?= $portrait ?>
							</div>
							<?php
							if ($pCounter % 2 === 0) {
								echo '<div class="clearfix hidden-md-up"></div>';
							}
							if ($pCounter % 3 === 0) {
								echo '<div class="clearfix hidden-lg-down.hidden-lg-up"></div>';
							}
							if ($pCounter % 4 === 0) {
								echo '<div class="clearfix hidden-xl-down"></div>';
							}

							$pCounter++;
						}
						?>
					</div>
				</div>
			</div>
			<?php
			$bCounter++;
		}
		?>
	</div>

	<?php
}