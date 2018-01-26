<?php
	$site_url = site_url();
?>
		 	<div class="wz-content" >
		 		<div class="wz-questions">

	    			<!-- question II-->
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
	    			</div>

	    			<p class="wz-info"><?php _e('Based on these answers, in the wizard you will get recommended settings for what your needs are.', $this->localizationName); ?></p>
	    		</div>
		 	</div>