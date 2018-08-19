<?php
namespace ProcessWire;

if ($this->schlagwoerter) {
	?>
	<div class="schlagwoerter">
		<?php
		foreach ($this->schlagwoerter as $schlagwort) {
			if ($schlagwort->viewable()) {
				?>
				<a href="<?= $schlagwort->url; ?>">
					<span class="badge badge-primary">
						<?= $schlagwort->title; ?>
					</span>
				</a>
				<?php
			} else {
				?>
				<span class="badge badge-primary">
					<?= $schlagwort->title; ?>
				</span>
				<?php
			}
		}
		?>
	</div>
	<?php
}
?>