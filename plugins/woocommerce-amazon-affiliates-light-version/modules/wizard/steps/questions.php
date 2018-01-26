<?php
	$site_url = site_url();
?>
		 	<div class="wz-content" >
		 		<div class="wz-questions">

		 			<!-- question I-->
			 		<h2>
			 			<?php echo sprintf( __('What kind of site is %s ?', $this->localizationName), '<a href="' . $site_url . '" target="_blank">' . $site_url . '</a>' ); ?>
			 		</h2>
			 		<div class="wz-options">
						 <?php /*<label class="control control--radio">I already have a website and I want to earn some easy money from affiliation
							<input type="radio" name="site_info"/>
							<div class="control__indicator"></div>
						</label>
						<label class="control control--radio">Online Store
							<input type="radio" name="site_info"/>
							<div class="control__indicator"></div>
						</label>
						<label class="control control--radio">Personal Website
							<input type="radio" name="site_info" />
							<div class="control__indicator"></div>
						</label>
						<label class="control control--radio">Other
							<input type="radio" name="site_info"/>
							<div class="control__indicator"></div>
						</label>*/ ?>
						<?php /*
						echo $this->build_form_input_radio('site_info', array(
							'value'		=> 'affiliate',
							'text' 		=> __('I already have a website and I want to earn some easy money from affiliation', $this->localizationName),
						));
						echo $this->build_form_input_radio('site_info', array(
							'value'		=> 'online',
							'text' 		=> __('Online Store', $this->localizationName),
						));
						echo $this->build_form_input_radio('site_info', array(
							'value'		=> 'personal',
							'text' 		=> __('Personal Website', $this->localizationName),
						));
						echo $this->build_form_input_radio('site_info', array(
							'value'		=> 'other',
							'text' 		=> __('Other', $this->localizationName),
						));
						*/ ?>
						<?php echo $this->build_form_input_radio_group('site_info', array(
							'values'		=> array(
								'blog' 			=> __('Blog', $this->localizationName),
								'store' 		=> __('Online Store', $this->localizationName),
								'personal' 		=> __('Personal Website', $this->localizationName),
								'other' 		=> __('Other', $this->localizationName),
							),
						)); ?>
	    			</div>

	    			<!-- question II-->
	    			<?php /*
			 		<h2>
			 			<?php echo sprintf( __('What is the state of the website: %s ?', $this->localizationName), '<a href="' . $site_url . '" target="_blank">' . $site_url . '</a>' ); ?>
			 		</h2>
			 		<div class="wz-options">
						<?php echo $this->build_form_input_radio_group('site_install', array(
							'values'		=> array(
								'fresh_install'	=> __('Fresh Install', $this->localizationName),
								'have_content'	=> __('Already have some content', $this->localizationName),
							),
						)); ?>
	    			</div>*/ ?>

	    			<!-- question III-->
	    			<?php /*
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
	    			</div>*/ ?>

	    			<p class="wz-info"><?php _e('Based on these answers, in the wizard you will get recommended settings for what your needs are.', $this->localizationName); ?></p>
	    		</div>
		 	</div>