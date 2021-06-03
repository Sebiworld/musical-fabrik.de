<?php
namespace ProcessWire;

// Does this role have any portraits at all?
if (!empty($this->portraits)) {
	?>
	<div class="container-fluid project_role_as_block">
		<div class="row portraits-row">
			<?php
			foreach ($this->portraits->find('season_'.$this->season->id.'_project_role_'.$this->page->id.'=1') as $portrait) {
				?>
				<div class="col col-6 col-sm-6 col-lg-4 col-xxl-3 portrait-block">
					<?= $portrait; ?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}