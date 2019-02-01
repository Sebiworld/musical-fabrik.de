<?php
namespace ProcessWire;

?>
<section class="container-fluid onepage-sektion sektion-partner-foerderer <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->sektionID ? 'id="'.$this->sektionID.'"' : ''; ?>>

	<?php
	if (!empty($this->titel)) {
		?>
		<h2 class="sektion-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
			<?= $this->titel; ?>
		</h2>
		<?php
	}
	?>

	<?php
	if ($this->partner) {
		?>
		<div class="container-fluid hervorgehoben tertiary partner">
			<h3 class="sub-titel">Unsere Partner:</h3>
			<div class="row liste">
				<?php
				foreach ($this->partner as $listenIndex => $partner) {
					?>
					<div class="col-12 col-sm-6 col-md-4 col-lg-3 kachel">
						<div class="seitenverhaeltnis sv-1-1">
							<div class="sv-inhalt">
								<div class="inhalt-wrapper">
									<?php
									if ($partner->titelbild) {
										?>
										<img class="img-fluid" src="<?= $partner->titelbild->width(800)->url; ?>" alt="<?= $partner->title; ?>" />
										<?php
									} else {
										?>
										<span class="titel"><?= $partner->title; ?></span>
										<?php
									}
									?>
								</div>
							</div>
						</div>
					</div>
					<?php
					if (($listenIndex + 1) % 2 === 0) {
						echo '<div class="clearfix hidden-sm-down.hidden-sm-up"></div>';
					}
					if (($listenIndex + 1) % 3 === 0) {
						echo '<div class="clearfix hidden-md-down.hidden-md-up"></div>';
					}
					if (($listenIndex + 1) % 4 === 0) {
						echo '<div class="clearfix hidden-lg-down"></div>';
					}
				}
				?>
			</div>
		</div>
		<?php
	}
	?>

	<?php
	if ($this->foerderer) {
		?>
		<div class="container-fluid foerderer">
			<h3 class="sub-titel">Unsere Förderer:</h3>
			<div class="row liste">
				<?php
				foreach ($this->foerderer as $listenIndex => $foerderer) {
					?>
					<div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl-2 kachel">
						<div class="seitenverhaeltnis sv-1-1">
							<div class="sv-inhalt">
								<div class="inhalt-wrapper">
									<?php
									if ($foerderer->titelbild) {
										?>
										<img class="img-fluid" src="<?= $foerderer->titelbild->width(500)->url; ?>" alt="<?= $foerderer->title; ?>" />
										<?php
									} else {
										?>
										<span class="titel"><?= $foerderer->title; ?></span>
										<?php
									}
									?>
								</div>
							</div>
						</div>
					</div>
					<?php
					if (($listenIndex + 1) % 2 === 0) {
						echo '<div class="clearfix hidden-xs-up"></div>';
					}
					if (($listenIndex + 1) % 3 === 0) {
						echo '<div class="clearfix hidden-sm-down.hidden-sm-up"></div>';
					}
					if (($listenIndex + 1) % 4 === 0) {
						echo '<div class="clearfix hidden-md-down.hidden-md-up"></div>';
					}
					if (($listenIndex + 1) % 6 === 0) {
						echo '<div class="clearfix hidden-lg-down"></div>';
					}
					// if(($listenIndex + 1) % 12 === 0) echo '<div class="clearfix hidden-xl-down"></div>';
				}
				?>
			</div>
		</div>
		<?php
	}
	?>

	<div class="container-fluid hervorgehoben tertiary text-block">
		<?= $this->inhalte; ?>
	</div>
</section>