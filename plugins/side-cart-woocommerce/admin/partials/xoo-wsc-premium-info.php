<div class="xoo-wsc-prem">
	<div class="xoo-hero-btns">
		<a class="buy-prem button button-primary button-hero" href="http://demo.xootix.com/side-cart-for-woocommerce/">LIVE DEMO</a>
		<a class="live-demo button button-primary button-hero" href="http://xootix.com/plugins/side-cart-for-woocommerce/">BUY PREMIUM - 14$</a>
	</div>

	<!-- Free V/s Premium -->
	<div class="xoo-fvsp">
		<span class="xoo-fvsp-head">Free V/s Premium</span>

		<?php

		$table_content = array(
			array('Add to cart without refresh on product page','yes','yes'),
			array('Hide basket on specific pages','yes','yes'),
			array('Update quantity from the side cart','no','yes','alert'),
			array('Suggested Products','no','yes','alert'),
			array('Header menu SHORTCODE (Use anywhere)','no','yes','alert'),
			array('Different basket icons / Custom Image','no','yes','alert'),
			array('Hide basket when empty','no','yes','alert'),
			array('Fly to cart animation','no','yes'),
			array('Works with custom add to cart button','no','yes'),
			array('Additional styling options','no','yes'),
		);

		?>

		<table class="xoo-fvsp-table">
			<thead>
				<tr>
					<th></th>
					<th>Free</th>
					<th>Premium<br><span>(No time limit)</span></th>
				</tr>
			</thead>

			<tbody>
				<?php 
					$html = '';
					foreach ($table_content as $table_row) {
						$html .= '<tr>';
						$alert = isset($table_row[3]) ? 'class=xfp-alert' : '';
						$html .= '<td '.$alert.'>'.$table_row[0].'</td>';
						$html .= '<td class="xfp-'.$table_row[1].'"><span class="dashicons dashicons-'.$table_row[1].'"></span></td>';
						$html .= '<td class="xfp-'.$table_row[2].'"><span class="dashicons dashicons-'.$table_row[2].'"></span></td>';
						$html .= '</tr>';
					}

					echo $html;
				?>
			</tbody>

		</table>

	</div>


	<div class="prem-images">
		<h3>Premium Options</h3>
		<span>Menu Shortcode - [xoo_wsc_cart]</span>
		<img src="<?php echo plugin_dir_url( __FILE__ ).'images/1.png'?>">
		<img src="<?php echo plugin_dir_url( __FILE__ ).'images/2.png'?>">
		<img src="<?php echo plugin_dir_url( __FILE__ ).'images/3.png'?>">
		<img src="<?php echo plugin_dir_url( __FILE__ ).'images/4.png'?>">
		<img src="<?php echo plugin_dir_url( __FILE__ ).'images/5.png'?>">
		<img src="<?php echo plugin_dir_url( __FILE__ ).'images/6.png'?>">
		<img src="<?php echo plugin_dir_url( __FILE__ ).'images/7.png'?>">
	</div>	
</div>