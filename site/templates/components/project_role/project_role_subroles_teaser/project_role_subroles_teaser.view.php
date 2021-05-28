<?php
namespace ProcessWire;

if (!empty($this->subroles)) {
	?>
	<div class="container-fluid project_role_subroles_teaser">
		<div class="row">
			<?php
			foreach ($this->subroles as $projectRole) {
				?>
				<div class="col col-12 card group-portrait">
					<div class="card-img-top">
						<?php
						if ($projectRole->main_image) {
							echo $this->imageService->getPictureHtml(array(
								'image' => $projectRole->main_image,
								'classes' => array('project-role-group-portrait', 'img-fluid'),
								'loadAsync' => true,
								'default' => array(
									'width' => 400
								)
							));
						} else {
							?>
							<img class="project-role-group-portrait img-fluid" src="<?= wire('config')->urls->templates; ?>assets/static_img/silhouette_gruppe.png" />
							<?php
						}
						?>
					</div>
					<div class="card-block">
						<h2 class="card-title title">
							<?= (!empty($projectRole->headline) ? $projectRole->headline : $projectRole->title); ?>

						</h2>
						<?= $projectRole->text ? '<div class="description card-text">'.$projectRole->text.'</div>' : ''; ?>

						<a class="btn btn-sm btn-light role-more" href="<?= $projectRole->url; ?>" title="<?= sprintf(__('Jump to "%1$s" details'), $projectRole->title); ?>">
							<?= __('More...'); ?>
						</a>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}