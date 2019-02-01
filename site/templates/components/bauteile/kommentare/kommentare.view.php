<?php
namespace ProcessWire;

if ($this->kommentare) {
	?>
	<div class="kommentare">
		<div class="liste">
			<?= $this->kommentare->render(); ?>
		</div>
		<div class="formular">
			<?= $this->kommentare->renderForm(); ?>
		</div>
	</div>
	<?php
}
?>