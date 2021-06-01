<?php
namespace ProcessWire;

// Does this role even have portraits?
if (!empty($this->portraits)) {
	?>
	<div class="container-fluid project_role_as_block_with_roles">
		<div class="row portraits-row">
			<?php
			// First output all portraits that belong to this role:
			foreach ($this->portraits->find('season_'.$this->season->id.'=1, root=1') as $portrait) {
				?>
				<div class="col col-6 col-sm-6 col-lg-4 col-xxl-3 portrait-block">
					<?= $portrait; ?>
				</div>
				<?php
			}

			// Output portraits that belong to subroles:
			if(!empty($this->subroles)){
				foreach($this->subroles as $projectRole){
					foreach ($this->portraits->find('season_'.$this->season->id.'_project_role_'.$projectRole->id.'=1, root!=1') as $portrait) {
						?>
						<div class="col col-6 col-sm-6 col-lg-4 col-xxl-3 portrait-block">
							<?= $portrait->renderWithSubtitle($projectRole->title); ?>
						</div>
						<?php
					}
				}
			}
			?>
		</div>
	</div>
	<?php
}