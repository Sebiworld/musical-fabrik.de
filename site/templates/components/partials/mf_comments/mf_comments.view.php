<?php
namespace ProcessWire;

if ($this->comments) {
	?>
	<div class="mf_comments">
		<div class="comments-list">
			<?= $this->comments->render(); ?>
		</div>
		<div class="comments-form">
			<?= $this->comments->renderForm(); ?>
		</div>
	</div>
	<?php
}
?>