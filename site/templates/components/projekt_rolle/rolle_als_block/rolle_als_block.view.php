<?php
namespace ProcessWire;

// Hat diese Rolle überhaupt Portraits?
if (!empty($this->portraits)) {
	?>
	<div class="container-fluid">
		<div class="row portraits-reihe">
			<?php
			$counter = 1;
			foreach ($this->portraits->find('staffel_'.$this->staffel->id.'_rolle_'.$this->page->id.'=1') as $portrait) {
				?>
				<div class="col-6 col-sm-6 col-lg-4 col-xl-3 portrait-block">
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
			?>
		</div>
	</div>
	<?php
}