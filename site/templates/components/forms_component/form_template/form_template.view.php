<?php
namespace ProcessWire;

if (!empty($this->fields)) {
	?>
	<div class="form_template-wrapper">
		<form method="post" class="form form_template" novalidate="novalidate" data-page="<?= $this->page->id; ?>">
			<fieldset class="all-formelements" <?= !empty($this->evaluationResponse['submission_blocked']) && $this->evaluationResponse['submission_blocked'] ? 'disabled="true"' : ''; ?>>
				<div class="alerts">
					<?php
						if(!empty($this->evaluationResponse['success']) && is_array($this->evaluationResponse['success'])){
							foreach($this->evaluationResponse['success'] as $msg){
								?>
								<div class="alert alert-success" role="alert">
									<?= $msg; ?>
								</div>
								<?php
							}
						}
						if(!empty($this->evaluationResponse['error']) && is_array($this->evaluationResponse['error'])){
							foreach($this->evaluationResponse['error'] as $msg){
								?>
								<div class="alert alert-danger" role="alert">
									<?= $msg; ?>
								</div>
								<?php
							}
						}
					?>
				</div>

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

				<?= wire('session')->CSRF->renderInput($this->formOrigin); ?>
				<div class="alerts">
					<?php
						if(!empty($this->evaluationResponse['success']) && is_array($this->evaluationResponse['success'])){
							foreach($this->evaluationResponse['success'] as $msg){
								?>
								<div class="alert alert-success" role="alert">
									<?= $msg; ?>
								</div>
								<?php
							}
						}
						if(!empty($this->evaluationResponse['error']) && is_array($this->evaluationResponse['error'])){
							foreach($this->evaluationResponse['error'] as $msg){
								?>
								<div class="alert alert-danger" role="alert">
									<?= $msg; ?>
								</div>
								<?php
							}
						}
					?>
				</div>

				<input type="text" name="information" class="info-field"/>
				<input type="hidden" name="form-origin" value="<?= $this->formOrigin; ?>">

				<button class="btn btn-project-primary btn-form-send" type="submit"><?= __('Send Request'); ?></button>
			</fieldset>
		</form>
	</div>
	<?php
}
?>