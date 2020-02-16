<?php
namespace ProcessWire;

$casts = new WireArray();
$castInfosAvailable = false;
foreach($this->allCasts as $cast){
	// Check if this cast is at all in this season:
	if($this->portraits->find('season_'.$this->season->id.'_'.$cast->id.'=1')->count > 0){
		$casts->add($cast);
		if(!empty($cast->text)){
			$castInfosAvailable = true;
		}
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
					<i>
						<?= $cast->title; ?>
						<?php 
						if($castInfosAvailable){
							?>
								&nbsp; <a href="#casts-description_<?= $this->season->id; ?>"><span class="icon ion-ios-information-circle-outline"></span></a>
							<?php
						}
						?>
					</i>
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
					if($projectRole->text){
						?>
						<div class="description">
							<?= $projectRole->text; ?>
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
								<i>
									<?= $cast->title; ?>
									<?php 
									if($castInfosAvailable){
										?>
										 &nbsp; <a href="#casts-description_<?= $this->season->id; ?>"><span class="icon ion-ios-information-circle-outline"></span></a>
										<?php
									}
									?>
								</i>
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
	
    if ($castInfosAvailable) {
		?>
		<div class="casts-description card bg-light" id="casts-description_<?= $this->season->id; ?>">
			<div class="card-body">
				<h2 class="card-title">Unsere Besetzungen</h2>
			</div>
			<ul class="list-group list-group-flush">
				<?php
				foreach ($casts as $index => $cast) {
					if ($this->portraits->find('season_' . $this->season->id . '_' . $cast->id . '=1')->count < 1) {
						continue;
					}
					?>
					<li class="list-group-item bg-light">
						<h3 class="cast-title"><?= $cast->title; ?></h3>
						<div class="cast-description"><?= $cast->text; ?></div>
					</li>
				<?php
				}
				?>
			</ul>
		</div>
		<?php
    }
}