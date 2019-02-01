<?php
namespace ProcessWire;

if ($this->isProjektseite) {
	if ($this->titelbild_html) {
		?>
		<div class="container-fluid projekt-titel titelbild" <?= $this->projektseite->farbe ? 'style="background-color: #' . $this->projektseite->farbe . '"' : ''; ?>>
			<a href="<?= $this->projektseite->url; ?>">
				<div class="seitenverhaeltnis sv-3-1">
					<?= $this->titelbild_html; ?>
				</div>

				<?php
				if ($this->titelbild->caption && !empty($this->titelbild->caption.'')) {
					echo '<div class="bild-caption-top">'.$this->titelbild->caption.'</div>';
				}
				?>
			</a>
			<?php
			if ($this->infoOverlay) {
				?>
				<div class="info-overlay">
					<?= $this->infoOverlay; ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	} else {
		// Kein Titelbild vorhanden
		?>
		<div class="container-fluid projekt-titel">
			<a class="projekt-titel-link" href="<?= $this->projektseite->url; ?>">
				<div class="titel-text">
					<h1 class="display-1">
						<?= $this->projektseite->title; ?>
					</h1>
					<?= $this->projektseite->kurzbeschreibung ? '<p class="lead">'.$this->projektseite->kurzbeschreibung.'</p>' : ''?>
				</div>
			</a>
			<?php
			if ($this->infoOverlay) {
				?>
				<br/><br/>
				<div class="info-overlay">
					<?= $this->infoOverlay; ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
}
?>