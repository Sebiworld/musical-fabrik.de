<?php
namespace ProcessWire;

// Hat diese Rolle überhaupt Staffeln?
if ($this->anzeigemodus && !empty($this->staffeln) && !empty($this->portraits)) {
	$elementID = $this->idService->getID('staffeln');
	?>
	<div class="staffeln-block">
		<ul class="nav nav-pills justify-content-center" id="<?= $elementID; ?>" role="tablist">
			<?php
			$counter = 0;
			foreach ($this->staffeln as $staffel) {
				$counter++;
				?>
				<li class="nav-item">
					<a class="nav-link <?= $counter >= $this->staffeln->count ? 'active' : ''; ?>" id="<?= "{$elementID}_{$counter}_tab"; ?>" data-toggle="pill" href="#<?= "{$elementID}_{$counter}"; ?>" role="tab" aria-controls="<?= "{$elementID}_{$counter}"; ?>" aria-selected="<?= $counter >= $this->staffeln->count ? 'true' : 'false'; ?>">
						<?= $staffel->title; ?>
					</a>
				</li>
				<?php
			}
			?>
		</ul>
		<div class="tab-content">
			<?php
			$counter = 0;
			foreach ($this->staffeln as $staffel) {
				$counter++;
				?>
				<div class="tab-pane fade <?= $counter >= $this->staffeln->count ? 'show active' : ''; ?>" id="<?= "{$elementID}_{$counter}"; ?>" role="tabpanel" aria-labelledby="<?= "{$elementID}_{$counter}_tab"; ?>">
					<?php
					echo $this->rolle->renderView('', array(
						'parameters' => array(
							'portraits' => $this->portraits,
							'staffel' => $staffel
						)
					));
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}