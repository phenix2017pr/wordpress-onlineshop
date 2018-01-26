<?php
	//$img_base_url = $this->module_folder . 'images/flags/';

	$amz_country = $this->__WooZoneLite_amazon_countries( 'country' );
	$amz_main_aff_id = $this->__WooZoneLite_amazon_countries( 'main_aff_id' );
?>
			<!-- StartSetup -->
			<div class="wz-setup">
				
				<!-- Warning messages -->
				<div class="wz-message">
					<i class="icon icon-warning2"> </i>
					<div class="alert alert-danger" role="alert">
						<?php _e('The following fields are required in order to send requests to Amazon and retrieve data about products  and listings. If you do not already have access keys set up, please visit the AWS Account Management page to create and retrieve them.', $this->localizationName); ?>
					</div>
				</div>

				<table  width="60%">
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Access Key ID', $this->localizationName); ?></td>
						<td colspan="2" class="wz-ttext"> 
							<?php /*<input type="text" name="AccessKeyID" id="AccessKeyID" value="" class="wz-textinput">*/ ?>
							<?php echo $this->build_form_input_text('AccessKeyID', array(
								'placeholder'	=> __('Enter Your Access Key ID', $this->localizationName),
							)); ?>
                       </td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Secret Access Key', $this->localizationName); ?></td>
						<td colspan="2" class="wz-ttext"> 
							<?php /*<input type="text" name="SecretAccessKey" id="SecretAccessKey" value="" class="wz-textinput">*/ ?>
							<?php echo $this->build_form_input_text('SecretAccessKey', array(
								'placeholder'	=> __('Enter Your Secret Access Key', $this->localizationName),
							)); ?>
                       </td>
					</tr>
				</table>
				
				<table width="60%" id="wz-aff-table">

					<tr>
						<td colspan="2" class="wz-toption your_affiliate_ids"><?php _e('Your Affiliate IDs', $this->localizationName); ?></td>
					</tr>

					<?php
					$html = array();
					$cc = 0;
					foreach ($amz_main_aff_id as $globalid => $country_name) {
						$flag = 'com' == $globalid ? 'us' : $globalid;
						$flag = strtoupper($flag);

						$flag_img = $this->module_folder . 'images/flags/' . $flag . '-flag.gif';

						$input_id = 'AffiliateID[' . $globalid . ']';
						$input_val = isset($this->settings['AffiliateID']["$globalid"]) ? $this->settings['AffiliateID']["$globalid"] : '';

						$idx = ($cc ? floor($cc / 2) : 0);
						$html[$idx] = isset($html[$idx]) ? $html[$idx] : '';

						$input_ = $this->build_form_input_text($input_id, array(
							'default'		=> $input_val,
							'css_class'		=> 'wz-flag',
							'placeholder'	=> sprintf( __('Enter Your Affiliate ID For %s', $this->localizationName), $flag ),
						));
						$html[$idx] .= '
						<td width="50%" class="wz-doption"> 
							' . $input_ . '
							<img src="' . $flag_img . '" height="10">
							<p class="wz-country">' . $country_name . '</p>
						</td>
						';

						$cc++;
					} // end foreach
					$html = '<tr>' . implode('</tr><tr>', $html) . '</tr>';
					echo $html;
					?>

				</table>
				
				<table>	
					
					<tr>
						<td width="20%" class="wz-toption"><?php _e('Main Affiliate ID', $this->localizationName); ?></td>
						<td class="wz-tselect"> 
							<?php /*<select name="main_aff_id" id="main_aff_id" class="wz-dropdown">
								<option selected="selected" value="com">United States</option>
								<option value="uk">United Kingdom</option>
								<option value="de">Deutschland</option>
								<option value="fr">France</option>
								<option value="jp">Japan</option>
								<option value="ca">Canada</option>
								<option value="cn">China</option>
								<option value="in">India</option>
								<option value="it">Italia</option>
								<option value="es">España</option>
								<option value="mx">Mexico</option>
								<option value="br">Brazil</option>
							</select>*/ ?>
							<?php echo $this->build_form_select('main_aff_id', array(
								'values'		=> $amz_main_aff_id,
							)); ?>
                       	</td>
                       	<td></td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption">Request Type</td>
						<td class="wz-tselect"> 
							<?php /*<select name="request_type" id="request_type" class="wz-dropdown">		
								<option selected="selected" value="auto">Auto Detect</option>
								<option value="soap">SOAP</option>
								<option value="xml">XML (over cURL, streams, fsockopen)</option>
							</select>*/ ?>
							<?php echo $this->build_form_select('protocol', array(
								'values'		=> array(
									'auto'			=> __('Auto Detect', $this->localizationName),
									'soap'			=> __('SOAP', $this->localizationName),
									'xml'			=> __('XML (over cURL, streams, fsockopen)', $this->localizationName),
								),
							)); ?>
                       	</td>
                       	<td></td>
					</tr>
					
					<tr>
						<td width="20%" class="wz-toption">Import Location</td>
						<td class="wz-tselect"> 
							<?php /*<select name="import_location" id="import_location" class="wz-dropdown">						
								<option selected="selected" value="com">Worldwide</option>
								<option value="co.uk">United Kingdom</option>
								<option value="de">Germany</option>
								<option value="fr">France</option>
								<option value="co.jp">Japan</option>
								<option value="ca">Canada</option>
								<option value="cn">China</option>
								<option value="in">India</option>
								<option value="it">Italy</option>
								<option value="es">Spain</option>
								<option value="com.mx">Mexico</option>
								<option value="com.br">Brazil</option>
							</select>*/ ?>
							<?php echo $this->build_form_select('country', array(
								'values'		=> $amz_country,
							)); ?>
                       	</td>
                       	<td></td>
					</tr>

				</table>

				<div class="wz-checkamazon">
			 		<a href="#" class="WooZoneLiteWizardCheckAmzKeys"><?php _e('Check Amazon AWS Keys', $this->localizationName); ?></a>
		 		</div>
			</div>