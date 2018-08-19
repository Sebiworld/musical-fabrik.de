<?php
namespace ProcessWire;

?>

<ol class="breadcrumb">
	<?php
	foreach ($this->breadcrumbs as $crumb) {
		?>
		<li class="breadcrumb-item <?= $crumb->active ? 'active' : ''; ?>">
			<?php
			if (!$crumb->active && $crumb->viewable()) {
				?>
				<a href="<?= $crumb->url; ?>">
					<?= $crumb->title_kurz; ?>
				</a>
				<?php
			} else {
				?>
				<?= $crumb->title_kurz; ?>
				<?php
			} ?>
		</li>
		<?php
	}
	?>

	<script type="application/ld+json">
		{
			"@context": "http://schema.org",
			"@type": "BreadcrumbList",
			"itemListElement": [
			<?php
			$position = 1;
			foreach ($this->breadcrumbs as $crumb) {
				if ($position > 1) {
					echo ",";
				}
				?>
				{
					"@type": "ListItem",
					"position": <?= $position; ?>,
					"item": {
						"@id": "<?= $crumb->httpUrl; ?>",
						"name": "<?= $crumb->title; ?>"
						<?php
						if ($crumb->template->hasField('titelbild') && $crumb->titelbild && !empty($crumb->titelbild)) {
							?>
							,"image": "<?= $crumb->titelbild->httpUrl; ?>"
							<?php
						}
						?>
					}
				}
				<?php
				$position++;
			}
			?>
			]
		}
	</script>
</ol>