<?php
namespace ProcessWire;

if (!empty($this->subroles)) {
	?>
	<div class="container-fluid project_role_subroles_teaser">
		<div class="row">
			<?php
			$counter = 1;
			foreach ($this->subroles as $projectRole) {
				?>
				<a class="title-link col-12 group-portrait" href="<?= $projectRole->url; ?>">
					<div class="card">
						<div class="card-img-top">
							<?php
							if ($projectRole->main_image) {
								echo $this->imageService->getImgHtml(array(
									'image' => $projectRole->main_image,
									'classes' => array('project-role-group-portrait', 'img-fluid'),
									'outputType' => 'image',
									'loadAsync' => true,
									'default' => array(
										'width' => 400
									),
									'srcset' => array(
										'320w' => array(
											'width' => 320
										),
										'640w' => array(
											'width' => 640
										),
										'720w' => array(
											'width' => 720
										),
										'800w' => array(
											'width' => 800
										),
										'960w' => array(
											'width' => 960
										)
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
							<?= $projectRole->intro ? '<p class="intro card-text">'.$projectRole->intro.'</p>' : ''; ?>
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