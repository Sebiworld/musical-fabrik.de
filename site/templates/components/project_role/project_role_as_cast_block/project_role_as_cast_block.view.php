<?php
namespace ProcessWire;

$casts = new WireArray();
foreach($this->allCasts as $cast){
	// Check if this cast is at all in this season:
	if($this->portraits->find('season_'.$this->season->id.'_'.$cast->id.'=1')->count > 0){
		$casts->add($cast);
	}
}

// Does this role even have portraits?
if (!empty($this->portraits) && !empty($casts)) {
	// First, all root portraits are output, i.e. the portraits that belong directly to this role:
	?>

	<div class="project_role_as_cast_block">
		<?php
		$bCounter = 1;
		foreach ($casts as $index => $cast) {
			?>
			<div class="cast-block">
				<div class="title">
					<strong><?= $cast->title; ?></strong>
					<?= ($cast->text) ? '<div class="description">'.$cast->text.'</div>' : ''; ?>
				</div>

				<div class="container-fluid">
					<div class="row portraits-row">
						<?php
						$pCounter = 1;
						foreach ($this->portraits->find('season_'.$this->season->id.'_'.$cast->id.'=1, root=1') as $portrait) {
							?>
							<div class="col col-6 col-sm-6 col-lg-4 col-xxl-3 portrait-block">
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