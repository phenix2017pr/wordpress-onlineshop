<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


$subtotal_txt 		= isset($options['sc-subtotal-text']) ? $options['sc-subtotal-text']: __("Subtotal:",'side-cart-woocommerce'); //Subtotal Text
$shipping_txt 		= isset($options['sc-shipping-text']) ? $options['sc-shipping-text']: __("To find out your shipping cost , Please proceed to checkout.",'side-cart-woocommerce'); // Shipping Text
$cart_txt 			= isset($options['sc-cart-text']) ? $options['sc-cart-text'] : __("View Cart",'side-cart-woocommerce'); //Cart Text
$chk_txt 			= isset($options['sc-checkout-text']) ? $options['sc-checkout-text']: __("Checkout",'side-cart-woocommerce'); //Checkout Text
$cont_txt 			= isset($options['sc-continue-text']) ? $options['sc-continue-text'] :__( "Continue Shopping",'side-cart-woocommerce'); //Continue Text

?>

<?php if(!empty($cart_txt) || !empty($chk_txt) || !empty($cont_txt)): // If any footer button exists , add footer div ?>

	<div class="xoo-wsc-footer">

		<div class="xoo-wsc-footer-a">
			<div class="xoo-wsc-subtotal">
				<span><?php esc_attr_e($subtotal_txt,'side-cart-woocommerce') ?></span> <?php echo wc_price(WC()->cart->subtotal); ?>
			</div>

			<?php if(!empty($shipping_txt)): ?>
				<span class="xoo-wsc-shiptxt"><?php esc_attr_e($shipping_txt,'side-cart-woocommerce'); ?></span>
			<?php endif; ?>
		</div>

		<div class="xoo-wsc-footer-b">
			<?php $hide_btns = WC()->cart->is_empty() ? 'style="display: none;"' : '';?>

			<?php if(!empty($cart_txt)): ?>
			<a href="<?php echo wc_get_cart_url(); ?>" class="button xoo-wsc-cart btn" <?php echo $hide_btns; ?>><?php echo esc_attr__($cart_txt,'side-cart-woocommerce'); ?></a>
			<?php endif; ?>

			<?php if(!empty($chk_txt)): ?>
			<a  href="<?php echo wc_get_checkout_url(); ?>" class="button xoo-wsc-chkt btn" <?php echo $hide_btns; ?>><?php echo esc_attr__($chk_txt,'side-cart-woocommerce'); ?></a>
			<?php endif; ?>

			<?php if(!empty($cont_txt)): ?>
			<a  href="#" class="button xoo-wsc-cont btn"><?php echo esc_attr__($cont_txt,'side-cart-woocommerce'); ?></a>
			<?php endif; ?>
		</div>

	</div>

	<?php endif; ?>

</div>