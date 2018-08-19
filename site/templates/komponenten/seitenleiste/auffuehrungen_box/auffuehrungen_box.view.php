<?php
namespace ProcessWire;

if (($this->auffuehrungen && count($this->auffuehrungen) > 0) || ($this->auffuehrungenAlt && count($this->auffuehrungenAlt) > 0)) {
	?>

	<div class="box auffuehrungen-box">
		<?php
		if ($this->titel) {
			?>
			<h4 class="titel"><?= $this->titel; ?></h4>
			<?php
		}
		?>

		<?php
		// TODO: Gruppierung in Staffeln
		if ($this->auffuehrungen && count($this->auffuehrungen) > 0) {
			foreach ($this->auffuehrungen as $auffuehrung) {
				?>
				<div class="auffuehrung">
					<h6 class="auffuehrung-titel">
						<?php
						if (count($auffuehrung->staffeln) > 0) {
							foreach ($auffuehrung->staffeln as $staffel) {
								?>
								<span class="badge badge-light"><?= $staffel->title; ?></span>
								<?php
							}
						}
						?>
						<br/><?= $auffuehrung->wochentag; ?>, <?= $auffuehrung->datum; ?> - <?= $auffuehrung->uhrzeit; ?> Uhr&nbsp;
					</h6>
					<p class="auffuehrung-besetzung"><?= $auffuehrung->besetzung; ?></p>
				</div>
				<?php
			}
		} else {
			?>
			Keine zukünftigen Aufführungen
			<?php
		}
		?>

		<?php
		if ($this->ticketSeite && $this->auffuehrungen && count($this->auffuehrungen) > 0) {
			?>
			<a class="btn btn-projekt-primary-inverse" href="<?= $this->ticketSeite->url; ?>">Jetzt Tickets sichern</a>
			<?php
		}
		?>

		<?php
		if ($this->auffuehrungenAlt && count($this->auffuehrungenAlt) > 0) {
			?>
			<br/>
			<button class="btn btn-light btn-sm" data-funktion="vergangene-veranstaltungen-anzeigen">Vergangene Aufführungen anzeigen</button>

			<div class="vergangen" style="display: none;">
				<h5 class="subtitel">bereits vergangen:</h5>

				<?php
				foreach ($this->auffuehrungenAlt as $auffuehrung) {
					?>
					<div class="auffuehrung">
						<h6 class="auffuehrung-titel">
							<?php
							if (count($auffuehrung->staffeln) > 0) {
								foreach ($auffuehrung->staffeln as $staffel) {
									?>
									<span class="badge badge-light"><?= $staffel->title; ?></span>
									<?php
								}
							}
							?>
							<br/><?= $auffuehrung->wochentag; ?>, <?= $auffuehrung->datum; ?> - <?= $auffuehrung->uhrzeit; ?> Uhr&nbsp;
						</h6>
						<p class="auffuehrung-besetzung"><?= $auffuehrung->besetzung; ?></p>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
		?>

	</div>
	<?php
}
?>