<?php 
/**
 * Picture - Divi Child theme custom functions
 */
 
/**
 * import Divi theme styles & Font Awesome.
 */
function candy_enqueue_assets() {

    wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
	wp_enqueue_style( 'candy-font-awesome', get_stylesheet_directory_uri() . '/css/font-awesome.min.css', array(), '4.4.0', 'all' );
	wp_enqueue_style( 'candy-animate', get_stylesheet_directory_uri() . '/css/animate.css', array(), '2016', 'all' );
	
}
add_action( 'wp_enqueue_scripts', 'candy_enqueue_assets' );

/**
* Featured Image function for posts and pages
* 
* @param  string $class The CSS class name to apply to the image default is .featured-img
* @param  string $size  The image size to use. Default is full size
* @return string        img -> width | height | src | class | alt
* 
*/
function candy_featured_image( $size = 'full', $class = 'featured-img' ) {
 
    global $post;
 
    if ( has_post_thumbnail( $post->ID ) ) {
 
    /* get the title attribute of the post or page 
     * and apply it to the alt tag of the image if the alt tag is empty
     */
    $attachment_id = get_post_thumbnail_id( $post->ID );
	
	$large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
  
    // echo the featured image
   echo $large_image_url[0];
 
    } // end if has_post_thumbnail
}

/**
 * Add Custom Footer Fields to Divi Customizer.
 */
function candy_customize_register($wp_customize) {

	$wp_customize->add_setting( 'et_divi[hide_footer_section]', array(
		'default'       => 1,
		'type'			=> 'option',
		'capability'	=> 'edit_theme_options',
		'transport'		=> 'postMessage',
		'sanitize_callback' => 'wp_validate_boolean',
	) );

	$wp_customize->add_control( 'et_divi[hide_footer_section]', array(
		'label'		=> esc_html__( 'Hide Credits & Social Icons', 'Divi' ),
		'section'	=> 'et_divi_footer_elements',
		'type'      => 'checkbox',
		'priority'  => 0,
	) );
	
	$wp_customize->add_setting( 'et_divi[center_footer_content]', array(
		'default'       => 1,
		'type'			=> 'option',
		'capability'	=> 'edit_theme_options',
		'transport'		=> 'postMessage',
		'sanitize_callback' => 'wp_validate_boolean',
	) );

	$wp_customize->add_control( 'et_divi[center_footer_content]', array(
		'label'		=> esc_html__( 'Center Credits & Social Icons', 'Divi' ),
		'section'	=> 'et_divi_footer_elements',
		'type'      => 'checkbox',
		'priority'  => 5,
	) );
	
}
add_action( 'customize_register', 'candy_customize_register', 11 );

/**
 * Import Demo Content.
 */
function candy_import_files() {
    return array(
        array(
            'import_file_name'           => 'Demo Import',
            'import_file_url'            => 'http://www.candythemes.com/free-themes/candy-picture/content.xml',
            'import_widget_file_url'     => 'http://www.candythemes.com/free-themes/candy-picture/widgets.wie',
            'import_customizer_file_url' => 'http://www.candythemes.com/free-themes/candy-picture/customizer.dat',
			'import_notice'              => __( '<img src="http://www.candythemes.com/free-themes/candy-picture/screenshot.jpg">', 'divi' ),
        ),
    );
}
add_filter( 'pt-ocdi/import_files', 'candy_import_files' );

/**
 * Set Home page.
 */
$front_page = get_page_by_title( 'Home' );
if(isset( $front_page ) && $front_page->ID) {
update_option('show_on_front', 'page');
update_option('page_on_front', $front_page->ID);
}

/**
 * Set Main and Footer menu.
 */
function candy_after_import_setup() {
	// Menus to assign after import.
	$main_menu   = get_term_by( 'name', 'Picture-Main', 'nav_menu' );
	$footer_menu = get_term_by( 'name', 'Picture-Footer', 'nav_menu' );
	set_theme_mod( 'nav_menu_locations', array(
		'primary-menu'   => $main_menu->term_id,
		'footer-menu' => $footer_menu->term_id,
	));
}
add_action( 'pt-ocdi/after_import', 'candy_after_import_setup' );


/**
 * Disable generation of smaller images (thumbnails) during the content import.
 */
add_filter( 'pt-ocdi/regenerate_thumbnails_in_content_import', '__return_false' );

/**
 * Remove ProteusThemes.com credits after installation.
 */
add_filter( 'pt-ocdi/disable_pt_branding', '__return_true' );

/**
 * Deleting WP default post "Hello World".
 */
wp_delete_post(1);
add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 20 );

/**
 * Plugin activation.
 */
require_once get_stylesheet_directory() . '/inc/plugin-activation.php';