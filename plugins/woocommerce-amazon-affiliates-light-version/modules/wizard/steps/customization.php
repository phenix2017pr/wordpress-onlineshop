			<!-- StartSetup -->
			<div class="wz-setup">
				<table  width="60%">

					<tr>
						<td width="20%" class="wz-toption" valign="top"><?php _e('Checkout Message', $this->localizationName); ?></td>
						<td valign="top" colspan="2" class="wz-ttextarea"> 
							<?php /*<textarea class="wz-textarea" name="checkout_message" id="checkout_message">You will be redirected to {amazon_website} to complete your checkout!</textarea>*/ ?>
							<?php echo $this->build_form_textarea('redirect_checkout_msg', array(
								'placeholder'	=> __('You will be redirected to {amazon_website} to complete your checkout!', $this->localizationName),
							)); ?>
                       </td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Redirect in', $this->localizationName); ?></td>
						<td colspan="2" class="wz-ttext"> 
							<?php /*<input type="text" value="3" name="redirect_in" id="redirect_in" class="wz-textinput">*/ ?>
							<?php echo $this->build_form_input_text('redirect_time', array(
								'css_class'		=> 'wz-textinput ',
								//'placeholder'	=> '',
							)); ?>
							<div class="wz-ptable redirect_in">
  								<p><?php _e('How many seconds to wait before redirect to Amazon!', $this->localizationName); ?></p>
  							</div>
                       </td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Buy Button Custom Text', $this->localizationName); ?></td>
						<td class="wz-ttext"> 
							<?php /*<input type="text" value="Checkout with Amazon" name="buy_button_text" id="buy_button_text" class="wz-textinput large">*/ ?>
							<?php echo $this->build_form_input_text('product_buy_text', array(
								//'placeholder'	=> '',
							)); ?>
                       </td>
                       <td>
                       		<div class="wz-ptable wz-buy-button-text">
  								<p><?php _e('(global) This text will be shown on the button linking to the external product. (global) = all external products; external products = those with "On-site Cart" option value set to "No"', $this->localizationName); ?></p>
  							</div>
                       </td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Buy Button Opens in:', $this->localizationName); ?></td>
						<td class="wz-tselect"> 
							<?php /*<select name="buy_button_opens_in" id="buy_button_opens_in" class="wz-dropdown">
								<option value="new_tab">New Tab</option>
								<option value="same_tab">Same Tab</option>
							</select>*/ ?>
							<?php echo $this->build_form_select('product_buy_button_open_in', array(
								'values'		=> array(
									'_self' 		=> __('Same tab', $this->localizationName),
									'_blank' 		=> __('New tab', $this->localizationName),
								),
							)); ?>
                       </td>
  						<td valign="middle"><p><?php _e('This option will allow you to setup how the product buy button will work. You can choose between opening in the same tab or in a new tab.', $this->localizationName); ?></p>
  						</td>
					</tr>
					
					<?php /*<tr>
						<td width="20%" class="wz-toption"><?php _e('"As Of" Text Font Size', $this->localizationName); ?></td>
						<td colspan="2" class="wz-ttext"> 
							<input type="text" value="1em" name="as_of_font_size" id="as_of_font_size" class="wz-textinput">
  							<div class="wz-ptable as_of_font_size">
  								<p><?php _e('Choose the font size (in em) for "as of" text that\'s next to the price in frontend', $this->localizationName); ?></p>
  							</div>
                       </td>
					</tr>*/ ?>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('"As Of" Text Font Size', $this->localizationName); ?></td>
						<td class="wz-tselect"> 
							<?php /*<select name="buy_button_opens_in" id="buy_button_opens_in" class="wz-dropdown">
								<option value="new_tab">New Tab</option>
								<option value="same_tab">Same Tab</option>
							</select>*/ ?>
							<?php echo $this->build_form_select('asof_font_size', array(
								'values'		=> $this->__WooZoneLite_asof_font_size(),
							)); ?>
                       </td>
  					   <td valign="middle"><p><?php _e('Choose the font size (in em) for "as of" text that\'s next to the price in frontend', $this->localizationName); ?></p>
  					   </td>
					</tr>

				</table>
			</div>