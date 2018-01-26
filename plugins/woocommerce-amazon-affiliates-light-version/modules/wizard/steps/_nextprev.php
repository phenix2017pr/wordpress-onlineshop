<?php
	$current_step = $this->step;
	$nextprev = $this->nextprev;

	$show_nextprev = true;
	if ( empty($nextprev['prev']['url']) && empty($nextprev['next']['url']) ) {
		$show_nextprev = false;
	}
?>
			<?php if ( $show_nextprev ) { ?>
		 	<div class="wz-next-prev">
		 		
		 		<?php if ( ! empty($nextprev['prev']['url']) ) { ?>
		 		<a href="<?php echo $nextprev['prev']['url']; ?>" class="wz-btn wz-prev"><i class="fa fa-angle-left" aria-hidden="true"></i> <?php echo $nextprev['prev']['text']; ?></a>
		 		<?php } ?>

				<?php if ( ! empty($nextprev['next']['url']) ) { ?>
		 		<a href="<?php echo $nextprev['next']['url']; ?>" class="wz-btn wz-next"><?php echo $nextprev['next']['text']; ?> <i class="fa fa-angle-right" aria-hidden="true"></i></a>
		 		<?php } ?>

	 		</div><!-- end wz-next-prev -->
	 		<?php } ?>