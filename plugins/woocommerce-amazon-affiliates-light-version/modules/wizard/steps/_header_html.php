<?php
	global $check, $check_all, $stepPms;

	$current_step = $this->step;
	$nextprev = $this->nextprev;
?>
<div class="subcontainer" data-step_pms='<?php echo json_encode( $stepPms ); ?>'>

	<?php if ( ! in_array($current_step, array('index', 'questions', 'questions2', 'questions3', 'requirements')) ) { ?>
	<div class="wz-notice">
		<a href="<?php echo $this->wizard_requirements_url; ?>" title="Check Mandatory Requirements">
			<i class="icon <?php echo 'valid' == $check_all ? 'icon-checkmark' : 'icon-notification2'; ?>"> </i>
		</a>
	</div>
	<?php } ?>

	<?php if ( in_array($current_step, array('requirements')) ) { ?>
	<div class="wz-notice wz-requirements-back">
		<?php //javascript: history.back(); ?>
		<a href="#" title="Go back to WooZoneLite Wizard">
			<i class="icon icon-undo2"> </i>
		</a>
	</div>
	<?php } ?>

	<div class="row">
		 <div class="col-lg-12">
		 	<div class="wz-header" >

		 		<?php
		 			//:: index step
		 			if ( in_array($current_step, array('index')) ) {
		 		?>

		 		<h1><?php _e('Welcome to WZone Setup Wizard', $this->localizationName); ?>
		 			<i class="wz-icon"></i>
		 		</h1>

		 		<p><?php _e('Please take 5 minutes to setup the most important plugin settings.', $this->localizationName); ?></p>

		 		<div class="wz-wizzy">
		 			<img src="<?php echo $this->module_folder; ?>images/wizzy.png">
		 		</div>

		 		<a href="<?php echo $this->wizard_questions_url; ?>" class="wz-btn wz-wizy">
		 			<?php _e('GET STARTED', $this->localizationName); ?>
		 			<i class="fa fa-angle-right" aria-hidden="true"></i>
		 		</a>

		 		<a class="wz-skip" href="<?php echo $this->plugin_dashboard_url; ?>"><?php _e('skip', $this->localizationName); ?></a>

		 		<?php
		 			}
		 			//:: finished step
		 			else if ( in_array($current_step, array('finished')) ) {
		 		?>

		 		<h1><?php _e('Congrats! You have successfully set up WooZoneLite Wizard!', $this->localizationName); ?>
		 			<i class="wz-icon"></i>
		 		</h1>

		 		<p><?php _e('Now you can go and import Amazon products into your website!', $this->localizationName); ?></p>

		 		<div class="wz-wizzy">
		 			<img src="<?php echo $this->module_folder; ?>images/wizzy.png">
		 		</div>

		 		<a href="<?php echo $this->plugin_dashboard_url; ?>" class="wz-btn wz-wizy">
		 			<?php _e('CLOSE', $this->localizationName); ?>
		 		</a>

		 		<?php
		 			}
		 			//:: all other steps
		 			else {
		 		?>

		 		<h1><?php _e('Welcome to WZone Setup Wizard', $this->localizationName); ?>
		 			<i class="wz-icon"></i>
		 		</h1>

		 		<p><?php _e('Please take 5 minutes to setup the most important plugin settings.', $this->localizationName); ?></p>

		 		<?php
		 			}
		 		?>

		 	</div><!-- end wz-header -->

		 	<form class="wz-main-form" id="wz-main-form" method="POST" action="<?php echo $nextprev['next']['url']; ?>">
				<?php if (function_exists('wp_nonce_field')) { wp_nonce_field('woozonelite-wizard-save', '_wz_nonce'); } ?>
				<input type="hidden" name="savechanges" value="yes">
				<input type="hidden" name="step_prev" value="<?php echo $current_step; ?>">

		 	<?php require( $this->module_folder_path . 'steps/_timeline.php' ); ?>