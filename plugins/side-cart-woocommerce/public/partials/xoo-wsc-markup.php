<?php

/**
 * Side Cart HTML
 *
 * @since      1.0.0
*/

//User Settings
$options 	= get_option('xoo-wsc-gl-options');
$sy_options = get_option('xoo-wsc-sy-options');

$show_basket		= isset( $options['bk-show-basket']) ? $options['bk-show-basket'] : 1; //Show Basket
$show_basket_mobile	= isset( $options['bk-show-basket-mobile']) ? $options['bk-show-basket-mobile'] : 1; //Show Basket on mobile device
$hide_basket_pages 	= trim(isset( $options['bk-hide-basket-pages']) ? $options['bk-hide-basket-pages'] : ''); //Hide basket on pages
$show_count 		= isset( $options['bk-show-bkcount']) ? $options['bk-show-bkcount'] : 1; //Show Count


?>

<div class="xoo-wsc-modal">

	<?php if($show_basket == 1): ?>
		<?php 
			$hide_basket = false;

			//On mobile device
			if(wp_is_mobile() && $show_basket_mobile != 1){
				$hide_basket = true;
			}

			//Hide on pages
			if(isset($hide_basket_pages) && $hide_basket === false){
				foreach (explode(',',$hide_basket_pages) as $page) {
					if($page && is_page($page)){
						$hide_basket = true;
						break;
					}
				}
			}

			$show_basket_style = $hide_basket === true ? 'display:none;' : '';
		?>
		<div class="xoo-wsc-basket" style="<?php echo $show_basket_style; ?>">
			<?php if($show_count == 1): 
				$count_value = WC()->cart->get_cart_contents_count();
			?>
				<span class="xoo-wsc-items-count"><?php echo $count_value ?></span>
			<?php endif; ?>
			<span class="xoo-wsc-icon-basket1 xoo-wsc-bki"></span>
		</div>
	<?php endif; ?>

	<div class="xoo-wsc-opac"></div>
	<div class="xoo-wsc-container">
		<?php do_action('xoo_wsc_cart_content'); ?>
	</div>
</div>

<div class="xoo-wsc-notice-box" style="display: none;">
	<div>
	  <span class="xoo-wsc-notice"></span>
	</div>
</div>