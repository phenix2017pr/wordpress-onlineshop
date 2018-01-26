<?php
	global $check, $check_all;
?>
			<!-- StartSetup -->
			<div class="wz-setup">
				<table  width="60%">
					<tr>
						<td colspan="3">
							<p class="wz-server-status-title"><strong><?php _e('Server status : these are all mandatory', $this->localizationName); ?></strong></p>
						</td>
					</tr>
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Cart page', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox " id="cart_page" name="cart_page" />
							    <label class="wz-checked" for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('woo-page-cart', array(
								'readonly'		=> true,
								'value'			=> 'valid',
								'default'		=> $check['check_woo_page_cart']['status'],
							)); ?>
	                       <div class="wz-ptable">
	                       		<?php /*<p class="wz-success"><?php _e('#5 - /cart/', $this->localizationName); ?></p>*/ ?>
	                       		<?php echo $check['check_woo_page_cart']['html']; ?>
	                       </div>
                       </td>
					</tr>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('Checkout  Page', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox " id="checkout_page" name="checkout_page" />
							    <label for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('woo-page-checkout', array(
								'readonly'		=> true,
								'value'			=> 'valid',
								'default'		=> $check['check_woo_page_checkout']['status'],
							)); ?>
	  						<div class="wz-ptable">
	  							  <?php /*<p class="wz-error"><?php _e('Missing - Please install it here', $this->localizationName); ?></p>*/ ?>
	  							  <?php echo $check['check_woo_page_checkout']['html']; ?>
	                        </div>
                       </td>
					</tr>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('WP Memory Limit', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="wp_memory_limit" name="wp_memory_limit" />
							    <label class="wz-checked" for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('wp-memory-limit', array(
								'readonly'		=> true,
								'value'			=> 'valid',
								'default'		=> $check['check_memory_limit']['status'],
							)); ?>
  							<div class="wz-ptable">
  								<?php /*<p class="wz-success"><?php _e('1 GB', $this->localizationName); ?></p>*/ ?>
  								<?php echo $check['check_memory_limit']['html']; ?>
  							</div>
                       </td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('SOAP Client', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="soap_client" name="soap_client" />
							    <label class="wz-checked" for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('soap_client', array(
								'readonly'		=> true,
								'value'			=> 'valid',
								'default'		=> $check['check_soap']['status'],
							)); ?>
  							<div class="wz-ptable">
  								<?php /*<p class="wz-success"><?php _e('Your server has the SOAP Client class enabled.', $this->localizationName); ?></p>*/ ?>
  								<?php echo $check['check_soap']['html']; ?>
  							</div>
                       </td>
					</tr>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('SimpleXML library', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="simplexml" name="simplexml" />
							    <label class="wz-checked" for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('simplexml', array(
								'readonly'		=> true,
								'value'			=> 'valid',
								'default'		=> $check['check_simplexml']['status'],
							)); ?>
  							<div class="wz-ptable">
  								<?php /*<p class="wz-success"><?php _e('Your server has the SOAP Client class enabled.', $this->localizationName); ?></p>*/ ?>
  								<?php echo $check['check_simplexml']['html']; ?>
  							</div>
                       </td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('WP Remote GET', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="wp_remote_get" name="wp_remote_get" />
							    <label class="wz-checked" for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('wp-remote-get', array(
								'readonly'		=> true,
								'value'			=> 'valid',
								'default'		=> $check['check_remote_get']['status'],
							)); ?>
  							<div class="wz-ptable">
  								<?php /*<p class="wz-success"><?php _e('wp_remote_get() was successful - Webservices Amazon is working.', $this->localizationName); ?></p>*/ ?>
  								<?php echo $check['check_remote_get']['html']; ?>
  							</div>
                       </td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('WP Cron', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="show_coupon" name="show_coupon" />   
							    <label class="wz-checked" for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('wp-cron', array(
								'readonly'		=> true,
								'value'			=> 'valid',
								'default'		=> $check['check_cron_status']['status'],
							)); ?>
  							<div class="wz-ptable">
  								<?php /*<p class="wz-success"><?php _e('Successfully spawn a call to the WP-Cron system on your website. The WP-Cron jobs on your website seems to work fine.', $this->localizationName); ?></p>*/ ?>
  								<?php echo $check['check_cron_status']['html']; ?>
  							</div>
                       </td>
					</tr>
				</table>
			</div>