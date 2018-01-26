			<!-- StartSetup -->
			<div class="wz-setup">
				<table  width="60%">
					<tr>
						<td width="20%" class="wz-toption"><?php _e('On Site Cart', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox " id="on_site_cart" name="on_site_cart" />
							    <label class="wz-checked" for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('onsite_cart', array(
								//'value'			=> 'yes',
							)); ?>
	                       <div class="wz-ptable">
	                       		<p><?php _e('This option will allow your customers to add multiple Amazon Products into Cart and checkout trought Amazon\'s system with all at once.', $this->localizationName); ?></p>
	                       </div>
                       </td>
					</tr>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('90 Days Cookies', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox " id="90_days_cookies" name="90_days_cookies" />
							    <label class="wz-checked" for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('90day_cookie', array(
								//'value'			=> 'yes',
							)); ?>
	  						<div class="wz-ptable">
	  							  <p><?php _e('If a customer adds a product into amazon cart, it’s kept there for 90 days, and if the user continues shopping you will get the commissions also!', $this->localizationName); ?></p>
	                        </div>
                       </td>
					</tr>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('Reviews Tab', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="reviews_tab" name="reviews_tab" />
							    <label for="cb"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('show_review_tab', array(
								//'value'			=> 'yes',
							)); ?>
  							<div class="wz-ptable">
  								<p><?php _e('Show Amazon reviews', $this->localizationName); ?></p>
  							</div>
                       </td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Remote Amazon Images', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="remote_amazon_images" name="remote_amazon_images" /> 
							    <label class="wz-checked" for="cb"></label>  
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('remote_amazon_images', array(
								//'value'			=> 'yes',
							)); ?>
  							<div class="wz-ptable">
  								<p><?php _e('This option will display all products images from Amazon CDN', $this->localizationName); ?></p>
  							</div>
                       </td>
					</tr>
					
				</table>
			</div>