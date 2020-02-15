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
	<div class="casts-row project_role_by_cast">
		<?php
		$bCounter = 1;
		foreach ($casts as $index => $cast) {
			if($this->portraits->find('season_'.$this->season->id.'_'.$cast->id.'=1, root=1')->count < 1){
				continue;
			}
			?>
			<div class="cast-block">
				<div class="title">
					<i><?= $cast->title; ?></i>
				</div>

				<div class="container-fluid">
					<div class="row portraits-row <?= $bCounter === 1 ? 'justify-content-end' : ($bCounter === count($casts) ? 'justify-content-start' : 'justify-content-around'); ?>">
						<?php
						$pCounter = 1;
						foreach ($this->portraits->find('season_'.$this->season->id.'_'.$cast->id.'=1, root=1') as $portrait) {
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
	// After the root portraits, the portraits are output for each subrole:
	if(!empty($this->subroles)){
		foreach($this->subroles as $projectRole){
			?>
			<div class="project_role">
				<div class="role-head">
					<h2 class="title">
						<?= $projectRole->title; ?>
					</h2>
					<?php
					if($projectRole->intro){
						?>
						<div class="intro">
							<?= $projectRole->intro; ?>
						</div>
						<?php
					}
					?>
					<a class="btn btn-sm btn-light role-more" href="<?= $projectRole->url; ?>" title="<?= sprintf(__('Jump to role description %1$s'), $projectRole->title); ?>">
						<?= __('Role description'); ?>
					</a>
				</div>
				<div class="casts-row">
					<?php
					$bCounter = 1;
					foreach ($casts as $index => $cast) {
						if($this->portraits->find('season_'.$this->season->id.'_'.$cast->id.'_'.$projectRole->id.'=1')->count < 1){
							continue;
						}
						?>
						<div class="cast-block">
							<div class="title">
								<i><?= $cast->title; ?></i>
							</div>

							<div class="row portraits-row <?= $bCounter === 1 ? 'justify-content-end' : ($bCounter === count($casts) ? 'justify-content-start' : 'justify-content-around'); ?>">
								<?php
								$pCounter = 1;
								foreach ($this->portraits->find('season_'.$this->season->id.'_'.$cast->id.'_'.$projectRole->id.'=1') as $portrait) {
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