<?php
namespace ProcessWire;

if (!empty($this->felder)) {
	?>
	<div class="template-formular-wrapper">
		<form action="<?= $this->page->url; ?>" method="post" class="formular template-formular" novalidate="novalidate" data-seite="<?= $this->page->id; ?>">
			<fieldset class="alle-formularelemente">
				<div class="hinweise"></div>

				<div class="inputfelder-wrapper">
					<div class="inputfelder">
						<div class="formular-sektion">
							<div class="formular-reihe">
								<?php
								$inhalteImFieldset = false;
								foreach ($this->felder as $feld) {
									if ($feld->type instanceof FieldtypeFieldsetClose) {
									} elseif ($feld->type instanceof FieldtypeFieldsetOpen) {
										if ($inhalteImFieldset) {
											echo '</div>';
											echo '</div>';
											echo '<div class="formular-sektion">';
											echo '<div class="formular-reihe">';
										}
									} else {
										$inhalteImFieldset = true;
									}
									echo $feld->inputHtml;
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<?= wire('session')->CSRF->renderInput($this->formularName); ?>
				<div class="hinweise"></div>

				<button class="btn btn-projekt-primary btn-formular-senden" type="submit">Anfrage abschicken</button>
			</fieldset>
		</form>
	</div>
	<?php
}
?>