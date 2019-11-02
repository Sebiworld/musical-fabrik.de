<?php
namespace ProcessWire;

if ($this->tags) {
	?>
	<div class="tags_field tags">
		<?php
		foreach ($this->tags as $tag) {
			if ($tag->viewable()) {
				?>
				<a href="<?= $tag->url; ?>">
					<span class="badge badge-primary">
						<?= $tag->title; ?>
					</span>
				</a>
				<?php
			} else {
				?>
				<span class="badge badge-primary">
					<?= $tag->title; ?>
				</span>
				<?php
			}
		}
		?>
	</div>
	<?php
}
?>