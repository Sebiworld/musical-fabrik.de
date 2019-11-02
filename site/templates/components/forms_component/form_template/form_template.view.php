<?php
namespace ProcessWire;

if (!empty($this->fields)) {
	?>
	<div class="form_template-wrapper">
		<form action="<?= $this->formAction; ?>" method="post" class="form form_template" novalidate="novalidate" data-page="<?= $this->page->id; ?>">
			<fieldset class="all-formelements">
				<div class="alerts"></div>

				<div class="inputfields-wrapper">
					<div class="inputfields">
						<div class="form-section">
							<div class="form-row">
								<?php
								$contentsInFieldset = false;
								foreach ($this->fields as $field) {
									if ($field->type instanceof FieldtypeFieldsetClose) {
									} elseif ($field->type instanceof FieldtypeFieldsetOpen) {
										if ($contentsInFieldset) {
											echo '</div>';
											echo '</div>';
											echo '<div class="form-section">';
											echo '<div class="form-row">';
										}
									} else {
										$contentsInFieldset = true;
									}
									echo $field->inputHtml;
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<?= wire('session')->CSRF->renderInput($this->formName); ?>
				<div class="alerts"></div>

				<button class="btn btn-project-primary btn-form-send" type="submit"><?= __('Send Request'); ?></button>
			</fieldset>
		</form>
	</div>
	<?php
}
?>