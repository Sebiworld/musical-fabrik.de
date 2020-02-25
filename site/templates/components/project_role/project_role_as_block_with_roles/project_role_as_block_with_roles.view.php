<?php
namespace ProcessWire;

// Does this role even have portraits?
if (!empty($this->portraits)) {
	?>
	<div class="container-fluid project_role_as_block_with_roles">
		<div class="row portraits-row">
			<?php
			// First output all portraits that belong to this role:
			$counter = 1;
			foreach ($this->portraits->find('season_'.$this->season->id.'=1, root=1') as $portrait) {
				?>
				<div class="col col-6 col-sm-6 col-lg-4 col-xxl-3 portrait-block">
					<?= $portrait; ?>
				</div>

				<?php
				if ($counter % 2 === 0) {
					echo '<div class="clearfix hidden-md-up"></div>';
				}
				if ($counter % 3 === 0) {
					echo '<div class="clearfix hidden-lg-down.hidden-lg-up"></div>';
				}
				if ($counter % 4 === 0) {
					echo '<div class="clearfix hidden-xl-down"></div>';
				}
				$counter++;
			}

			// Output portraits that belong to subroles:
			if(!empty($this->subroles)){
				foreach($this->subroles as $projectRole){
					$counter = 1;
					foreach ($this->portraits->find('season_'.$this->season->id.'_project_role_'.$projectRole->id.'=1, root!=1') as $portrait) {
						?>
						<div class="col col-6 col-sm-6 col-lg-4 col-xxl-3 portrait-block">
							<?= $portrait->renderWithSubtitle($projectRole->title); ?>
						</div>

						<?php
						if ($counter % 2 === 0) {
							echo '<div class="clearfix hidden-md-up"></div>';
						}
						if ($counter % 3 === 0) {
							echo '<div class="clearfix hidden-lg-down.hidden-lg-up"></div>';
						}
						if ($counter % 4 === 0) {
							echo '<div class="clearfix hidden-xl-down"></div>';
						}
						$counter++;
					}
				}
			}
			?>
		</div>
	</div>
	<?php
}