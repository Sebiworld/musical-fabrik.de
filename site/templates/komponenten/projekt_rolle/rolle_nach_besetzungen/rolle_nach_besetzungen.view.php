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
	<div class="besetzungen-reihe">
		<?php
		$bCounter = 1;
		foreach ($besetzungen as $index => $besetzung) {
			if($this->portraits->find('staffel_'.$this->staffel->id.'_'.$besetzung->id.'=1, root=1')->count < 1){
				continue;
			}
			?>
			<div class="besetzung-block">
				<div class="titel">
					<i><?= $besetzung->title; ?></i>
				</div>

				<div class="container-fluid">
					<div class="row portraits-reihe <?= $bCounter === 1 ? 'justify-content-end' : ($bCounter === count($besetzungen) ? 'justify-content-start' : 'justify-content-around'); ?>">
						<?php
						$pCounter = 1;
						foreach ($this->portraits->find('staffel_'.$this->staffel->id.'_'.$besetzung->id.'=1, root=1') as $portrait) {
							?>
							<div class="col-12 col-xl-6 portrait-block">
								<?= $portrait ?>
							</div>
							<?php
							if ($pCounter % 2 === 0) {
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
	// Nach den Root-Portraits werden pro Unterrolle die Portraits ausgegeben:
	if(!empty($this->unterrollen)){
		foreach($this->unterrollen as $rolle){
			?>
			<div class="rollen-seite">
				<a class="titel-link" href="<?= $rolle->url; ?>">
					<h2 class="titel">
						<?= $rolle->title; ?>
					</h2>
					<?php
					if($rolle->einleitung){
						?>
						<div class="einleitung">
							<?= $rolle->einleitung; ?>
						</div>
						<?php
					}
					?>
				</a>
				<div class="besetzungen-reihe">
					<?php
					$bCounter = 1;
					foreach ($besetzungen as $index => $besetzung) {
						if($this->portraits->find('staffel_'.$this->staffel->id.'_'.$besetzung->id.'_'.$rolle->id.'=1')->count < 1){
							continue;
						}
						?>
						<div class="besetzung-block">
							<div class="titel">
								<i><?= $besetzung->title; ?></i>
							</div>

							<div class="row portraits-reihe <?= $bCounter === 1 ? 'justify-content-end' : ($bCounter === count($besetzungen) ? 'justify-content-start' : 'justify-content-around'); ?>">
								<?php
								$pCounter = 1;
								foreach ($this->portraits->find('staffel_'.$this->staffel->id.'_'.$besetzung->id.'_'.$rolle->id.'=1') as $portrait) {
									?>
									<div class="col-12 col-xl-6 portrait-block">
										<?= $portrait ?>
									</div>
									<?php
									if ($pCounter % 2 === 0) {
										?>
										<div class="clearfix hidden-xl-down"></div>
										<?php
									}

									$pCounter++;
								}
								?>
							</div>
						</div>
						<?php
						$bCounter++;
					}
					?>
				</div>
			</div>
			<?php
		}
	}
}