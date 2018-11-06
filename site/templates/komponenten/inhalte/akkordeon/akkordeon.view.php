<?php
namespace ProcessWire;

if ($this->tabs && count($this->tabs) > 0) {
	?>
	<div class="inhalte-akkordeon <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
		<?php
		if (!empty($this->titel)) {
			$headingDepth = 2;
			if ($this->page->depth && intval($this->page->depth)) {
				$headingDepth = $headingDepth + intval($this->page->depth);
			}
			?>
			<h<?= $headingDepth; ?> class="block-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->titel; ?>
			</h<?= $headingDepth; ?>>
			<?php
		}
		?>
		<div class="akkordeon" id="<?= $this->id; ?>">
			<?php
			// Einzelne Akkordeon-Tabs darstellen:
			foreach ($this->tabs as $tab) {
				?>
				<div class="card">
					<div class="card-header bg-dark" id="heading-<?= $tab->id; ?>">
						<h2 class="mb-0">
							<a data-toggle="collapse" data-target="#<?= $tab->id; ?>" data-parent="#<?= $this->id; ?>" href="#<?= $tab->id; ?>" aria-controls="<?= $tab->id; ?>" data-expanded="false">
								<?= $tab->titel; ?>
							</a>
						</h2>
					</div>

					<div id="<?= $tab->id; ?>" class="collapse" role="tabpanel" aria-labelledby="heading-<?= $tab->id; ?>" data-parent="#<?= $this->id; ?>">
						<div class="card-block">
							<?= $tab->inhalt; ?>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
?>