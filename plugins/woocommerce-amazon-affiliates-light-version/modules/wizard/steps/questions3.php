<?php
	$site_url = site_url();
?>
		 	<div class="wz-content" >
		 		<div class="wz-questions">

	    			<!-- question III-->
			 		<h2>
			 			<?php echo sprintf( __('With what purpose you want to use %s ?', $this->localizationName), 'WZone' ); ?>
			 		</h2>
			 		<div class="wz-options">
						<?php echo $this->build_form_input_radio_group('site_purpose', array(
							'values'		=> array(
								'have_website' 		=> __('I already have a website and I want to earn some easy money from affiliation', $this->localizationName),
								'new_store' 		=> __('I\' creating a new website with the sole purpose of having a store featuring amazon products', $this->localizationName),
								'have_store' 		=> __('I already have a webstore and want to earn some money from affiliation also', $this->localizationName),
								'custom_checkout'	=> __('I only want to import products from Amazon, but to take care of the checkout process myself', $this->localizationName),
							),
						)); ?>
	    			</div>

	    			<p class="wz-info"><?php _e('Based on these answers, in the wizard you will get recommended settings for what your needs are.', $this->localizationName); ?></p>
	    		</div>
		 	</div>