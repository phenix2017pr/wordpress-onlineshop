			<!-- StartSetup -->
			<div class="wz-setup">
				<table  width="60%">
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Prices Setup', $this->localizationName); ?></td>
						<td class="wz-tselect"> 
							<?php /*<select name="prices_setup" class="wz-dropdown">
								<option value="only_amazon">Only Amazon</option>
								<option value="other_sellers">Other Sellers</option>
							</select>*/ ?>
							<?php echo $this->build_form_select('price_setup', array(
								'values'		=> array(
									'only_amazon' 		=> __('Only Amazon', $this->localizationName),
									'amazon_or_sellers' => __('Amazon OR other sellers', $this->localizationName),
								),
							)); ?>
                       </td>
  						<td valign="middle"><p><?php _e('Get product offer lowest price from Amazon or other Amazon sellers.', $this->localizationName); ?></p></td>
					</tr>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('Import Product from Merchant', $this->localizationName); ?></td>
						<td class="wz-tselect"> 
							<?php /*<select name="import_product_merchant" class="wz-dropdown">
								<option value="only_amazno">Only Amazon</option>
								<option value="amazon_and_other_sellers">Amazon & Other Sellers</option>
							</select>*/ ?>
							<?php echo $this->build_form_select('merchant_setup', array(
								'values'		=> array(
									'only_amazon' 		=> __('Only Amazon', $this->localizationName),
									'amazon_or_sellers' => __('Amazon and other sellers', $this->localizationName),
								),
							)); ?>
                        </td>
  						<td valign="middle"><p><?php _e('Get products: A. only from Amazon or B. from (Amazon and other sellers). ATTENTION: If you choose "Only Amazon" then only products which have Amazon among their sellers will be imported!', $this->localizationName); ?></p></td>
					</tr>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('Import as', $this->localizationName); ?></td>
						<td class="wz-tselect"> 
							<?php echo $this->build_form_select('default_import', array(
								'values'		=> array(
									'publish' 		=> __('Publish', $this->localizationName),
									'draft' 		=> __('Draft', $this->localizationName),
								),
							)); ?>
                       </td>
  						<td valign="middle"><p><?php _e('Default import products with status "Published" or "Draft"', $this->localizationName); ?></p></td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Import Products with Price 0', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="import_zero_priced_products" name="import_zero_priced_products" />
							    <label for="import_zero_priced_products"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('import_price_zero_products', array(
								//'value'			=> 'yes',
							)); ?>
	                       <div class="wz-ptable">
	                       		<p><?php _e('Choose if you want to import products with price 0 (free products)', $this->localizationName); ?></p>
	                       </div>
                       </td>
					</tr>

					<tr>
						<td width="20%" class="wz-toption"><?php _e('Import Attributes', $this->localizationName); ?></td>
						<td colspan="2" class="wz-tcolspan"> 
							<?php /*<div class="checkbox">
							    <input type="checkbox" id="import_attributes" name="import_attributes" />
							    <label class="wz-checked" for="import_attributes"></label>
  							</div>*/ ?>
							<?php echo $this->build_form_input_checkbox('item_attribute', array(
								//'value'			=> 'yes',
							)); ?>
  							<div class="wz-ptable">
  								<p><?php _e('This option will allow to import the product item attributes.', $this->localizationName); ?></p>
  							</div>
                       </td>
					</tr>

				</table>
			</div>