<?php
namespace ProcessWire;

if ($this->sponsors && count($this->sponsors) > 0) {
	?>

	<div class="box sponsors_box">
		<?php
		if ($this->title) {
			?>
			<h4 class="title"><?= $this->title; ?></h4>
			<?php
		}
		?>

		<?= implode(', ', $this->sponsors); ?>

	</div>
	<?php
}
?>