<?php
namespace ProcessWire;

if ($this->socialLinks && !empty($this->socialLinks)) {
	?>

	<div class="box teilen-box">
		<?php
		if ($this->titel) {
			?>
			<h4 class="titel"><?= $this->titel; ?></h4>
			<?php
		}
		?>

		<?php
		foreach ($this->socialLinks as $socialLink) {
			if ($socialLink->onclick) {
				?>
				<a class="social-button" title="<?= $socialLink->title; ?>" onclick="window.open('<?= $socialLink->link; ?>'); return false;" rel="noopener">
					<i class="icon <?= $socialLink->icon; ?>"></i>
				</a>
				<?php
			} else {
				?>
				<a class="social-button" href="<?= $socialLink->link; ?>" title="<?= $socialLink->title; ?>" target="_blank" rel="noopener">
					<i class="icon <?= $socialLink->icon; ?>"></i>
				</a>
				<?php
			}
		}
		?>

	</div>
	<?php
}
?>
