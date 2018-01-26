<?php
	$current_step = $this->step;
	$nextprev = $this->nextprev;
?>
			<!DOCTYPE html>
			<!--[if IE 9]>
			<html class="ie9" <?php language_attributes(); ?> >
			<![endif]-->
			<!--[if !(IE 9) ]><!-->
			<html <?php language_attributes(); ?> >
			<!--<![endif]-->

			<head>
				<meta name="viewport" content="width=device-width, initial-scale=1.0" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><?php esc_html_e( 'WooZoneLite &rsaquo; Setup Wizard', $this->localizationName ); ?></title>
				<?php
					//wp_print_head_scripts();

					//do_action( 'admin_print_styles' );
					//do_action( 'admin_head' );
				?>
			</head>

			<body class="wp-core-ui">

<!-- ajax main container-->
<div id="WooZoneLiteWizard">

<!-- container-->
<div class="container" id="WooZoneLiteWizard-ajax-response">

<?php require( $this->module_folder_path . 'steps/_header_html.php' ); ?>