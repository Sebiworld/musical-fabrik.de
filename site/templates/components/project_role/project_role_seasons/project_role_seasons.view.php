<?php
namespace ProcessWire;

// Does this role have seasons at all?
if ($this->viewType && !empty($this->seasons) && !empty($this->portraits)) {
	$elementId = $this->idService->getID('season');
	?>
	<div class="seasons-block project_role_seasons">
		<ul class="nav nav-pills justify-content-center" id="<?= $elementId; ?>" role="tablist">
			<?php
			$counter = 0;
			foreach ($this->seasons as $season) {
				$counter++;
				?>
				<li class="nav-item">
					<a class="nav-link <?= $counter >= $this->seasons->count ? 'active' : ''; ?>" id="<?= "{$elementId}_{$counter}_tab"; ?>" data-toggle="pill" href="#<?= "{$elementId}_{$counter}"; ?>" role="tab" aria-controls="<?= "{$elementId}_{$counter}"; ?>" aria-selected="<?= $counter >= $this->seasons->count ? 'true' : 'false'; ?>">
						<?= $season->title; ?>
					</a>
				</li>
				<?php
			}
			?>
		</ul>
		<div class="tab-content">
			<?php
			$counter = 0;
			foreach ($this->seasons as $season) {
				$counter++;
				?>
				<div class="tab-pane fade <?= $counter >= $this->seasons->count ? 'show active' : ''; ?>" id="<?= "{$elementId}_{$counter}"; ?>" role="tabpanel" aria-labelledby="<?= "{$elementId}_{$counter}_tab"; ?>">
					<?php
					echo $this->projectRole->renderView('', array(
						'parameters' => array(
							'portraits' => $this->portraits,
							'season' => $season
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