<?php
// soap
if (extension_loaded('soap')) {
?>
<div class="WooZoneLite-message WooZoneLite-success">
	SOAP extension installed on server
</div>
<?php
}else{
?>
<div class="WooZoneLite-message WooZoneLite-error">
	SOAP extension not installed on your server, please talk to your hosting company and they will install it for you.
</div>
<?php
}

// Woocommerce
if( class_exists( 'Woocommerce' ) ){
?>
<div class="WooZoneLite-message WooZoneLite-success">
	 WooCommerce plugin installed
</div>
<?php
}else{
?>
<div class="WooZoneLite-message WooZoneLite-error">
	WooCommerce plugin not installed, in order the product to work please <a href="https://www.woothemes.com/woocommerce/" traget="_blank">install WooCommerce wordpress plugin</a>.
</div>
<?php
}

// curl
if ( function_exists('curl_init') ) {
?>
<div class="WooZoneLite-message WooZoneLite-success">
	cURL extension installed on server
</div>
<?php
}else{
?>
<div class="WooZoneLite-message WooZoneLite-error">
	cURL extension not installed on your server, please talk to your hosting company and they will install it for you.
</div>
<?php
}
?>
<?php
