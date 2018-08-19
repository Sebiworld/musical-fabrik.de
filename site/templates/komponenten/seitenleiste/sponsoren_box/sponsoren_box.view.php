<?php
namespace ProcessWire;

if ($this->sponsoren && count($this->sponsoren) > 0) {
	?>

	<div class="box sponsoren-box">
		<?php
		if ($this->titel) {
			?>
			<h4 class="titel"><?= $this->titel; ?></h4>
			<?php
		}
		?>

		<?= implode(', ', $this->sponsoren); ?>

	</div>
	<?php
}
?>