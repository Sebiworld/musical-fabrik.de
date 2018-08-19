<?php
namespace ProcessWire;

if (!empty($this->unterrollen)) {
	?>
	<div class="container-fluid">
		<div class="row">
			<?php
			$counter = 1;
			foreach ($this->unterrollen as $rolle) {
				?>
				<a class="titel-link col-12 gruppenportrait" href="<?= $rolle->url; ?>">
					<div class="card">
						<div class="card-img-top">
							<?php
							if ($rolle->titelbild) {
								echo $this->bildProvider->getBildTag(array(
									'bild' => $rolle->titelbild,
									'ausgabeAls' => 'image',
									'classes' => 'rolle-gruppenbild img-fluid',
									'normal' => array(
										'height' => 800
									),
									'sm' => array(
										'height' => 400
									)
								));
							} else {
								?>
								<img class="rolle-gruppenbild img-fluid" src="<?= wire('config')->urls->templates; ?>assets/static_img/silhouette_gruppe.png" />
								<?php
							}
							?>
						</div>
						<div class="card-block">
							<h2 class="card-title titel">
								<?= (!empty($rolle->ueberschrift) ? $rolle->ueberschrift : $rolle->title); ?>

							</h2>
							<?= $rolle->einleitung ? '<p class="einleitung card-text">'.$rolle->einleitung.'</p>' : ''; ?>
						</div>
					</div>
				</a>

				<?php
				if ($counter % 2 === 0) {
					echo '<div class="clearfix hidden-md-up"></div>';
				}
				if ($counter % 3 === 0) {
					echo '<div class="clearfix hidden-lg-down.hidden-lg-up"></div>';
				}
				if ($counter % 4 === 0) {
					echo '<div class="clearfix hidden-xl-down"></div>';
				}
				$counter++;
			}
			?>
		</div>
	</div>
	<?php
}