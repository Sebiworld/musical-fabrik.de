<?php
namespace ProcessWire;

if (($this->performances && count($this->performances) > 0) || ($this->performancesOld && count($this->performancesOld) > 0)) {
	?>

	<div class="box events_box">
		<?php
		if ($this->title) {
			?>
			<h4 class="title"><?= $this->title; ?></h4>
			<?php
		}
		?>

		<?php
		// TODO: Grouping in Seasons
		if ($this->performances && count($this->performances) > 0) {
			?>
			<div class="events-list future-events">
				<?php
				foreach ($this->performances as $performance) {
					?>
					<div class="event">
						<h6 class="event-title">
							<?php
							if (count($performance->seasons) > 0) {
								foreach ($performance->seasons as $season) {
									?>
									<span class="badge badge-light"><?= $season->title; ?></span>
									<?php
								}
								?>
								<br/>
								<?php
							}
							?>

							<?= sprintf(__('%1$s, %2$s - %3$s o\'clock'), $performance->weekday, $performance->date, $performance->time); ?>&nbsp;
						</h6>
						<p class="event-cast"><?= $performance->cast; ?></p>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		} else {
			?>
			<?= __('No future performances'); ?>
			<?php
		}
		?>

		<?php
		if ($this->ticketPage && $this->performances && count($this->performances) > 0) {
			?>
			<a class="btn btn-dark get-tickets-btn" href="<?= $this->ticketPage->url; ?>"><?= __('Get your tickets now!'); ?></a>
			<?php
		}
		?>

		<?php
		if ($this->performancesOld && count($this->performancesOld) > 0) {
			?>
			<br/>
			<button class="btn btn-light btn-sm" data-action="show-past-performances"><?= __('Show past performances'); ?></button>

			<div class="past-performances d-none">
				<h5 class="subtitle"><?= __('already passed:'); ?></h5>

				<div class="events-list past-events">
					<?php
					foreach ($this->performancesOld as $performance) {
						?>
						<div class="event">
							<h6 class="event-title">
								<?php
								if (count($performance->seasons) > 0) {
									foreach ($performance->seasons as $season) {
										?>
										<span class="badge badge-light"><?= $season->title; ?></span>
										<?php
									}
									?>
									<br/>
									<?php
								}
								?>
								<?= sprintf(__('%1$s, %2$s - %3$s o\'clock'), $performance->weekday, $performance->date, $performance->time); ?>&nbsp;
							</h6>
							<p class="event-cast"><?= $performance->cast; ?></p>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}
		?>

	</div>
	<?php
}
?>