<?php
namespace ProcessWire;

?>

<ol class="breadcrumb breadcrumbs_component">
	<?php
	foreach ($this->breadcrumbs as $crumb) {
		?>
		<li class="breadcrumb-item <?= $crumb->active ? 'active' : ''; ?>">
			<?php
			if (!$crumb->active && $crumb->viewable()) {
				?>
				<a href="<?= $crumb->url; ?>">
					<?= $crumb->title_short; ?>
				</a>
				<?php
			} else {
				?>
				<?= $crumb->title_short; ?>
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
						if ($crumb->viewable()) {
							?>
							,"url": "<?= $crumb->httpUrl; ?>"
							<?php
						}

						if ($crumb->template->hasField('main_image') && $crumb->main_image && !empty($crumb->main_image)) {
							?>
							,"image": "<?= $crumb->main_image->httpUrl; ?>"
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