<?php
/*
Plugin Name: Recent Posts Widget With Thumbnails
Plugin URI:  http://wordpress.org/plugins/recent-posts-widget-with-thumbnails/
Description: Small and fast plugin to display in the sidebar a list of linked titles and thumbnails of the most recent postings
Version:     5.2.2
Author:      Martin Stehle
Author URI:  http://stehle-internet.de
Text Domain: recent-posts-widget-with-thumbnails
Domain Path: /languages
License:     GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

/**
 * Recent_Posts_Widget_With_Thumbnails widget class
 *
 * @since 1.0
 */
class Recent_Posts_Widget_With_Thumbnails extends WP_Widget {

	var $plugin_slug;  // identifier of this plugin for WP
	var $plugin_version; // number of current plugin version
	var $default_number_posts;  // number of posts to show in the widget
	var $default_thumb_dimensions;  // dimensions of the thumbnail
	var $default_thumb_width;  // custom width of the thumbnail
	var $default_thumb_height; // custom height of the thumbnail
	var $default_thumb_url; // URL of the default thumbnail
	var $default_post_title_length; // number of chars of excerpt
	var $default_excerpt_length; // number of chars of excerpt
	var $default_excerpt_more; // characters to indicate further text
	var $default_category_ids; // selected categories
	var $css_file_path; // path of the public css file
	var $current_thumb_dimensions; // set size of the thumbnail
	var $in_categories_text; // translated text for 'In {categories}' 
	var $comma_text; // translated text for ', ' 
	var $author_text; // translated text for 'Author:' 

	function __construct() {
		$language_codes = explode( '_', get_locale() );
		switch ( $language_codes[ 0 ] ) {
			case 'ar':
				$widget_name = 'آخر المقالات مع الصوّر المصغّرة';
				$widget_desc = 'قائمة موقعك المشاركات الأخيرة، مع عنوان نقر والصور المصغرة';
				break;
			case 'fa':
				$widget_name = 'نوشته های اخیر با تصویر بندانگشتی';
				$widget_desc = 'فهرست سایت شما ارسال جدید، با عنوان قابل کلیک و عکسها';
				break;
			case 'de':
				$widget_name = 'Neueste Beitr&auml;ge mit Vorschaubildern';
				$widget_desc = 'Liste deiner aktuellsten Beitr&auml;ge, mit klickbaren &Uuml;berschriften und Vorschaubildern.';
				break;
			case 'pl':
				$widget_name = 'Ostatnie posty z miniaturami';
				$widget_desc = 'Lista ostatnich wpisów twojej strony z klikalnymi tytułami i miniaturami.';
				break;
			case 'ru':
				$widget_name = 'Последние записи с эскизами';
				$widget_desc = 'Список последних сообщений вашего сайта, с заголовком интерактивными и иконками.';
				break;
			case 'tr':
				$widget_name = 'Küçük Resimlerle Son Yazılar';
				$widget_desc = 'Başlık ve küçük resimleri tıklanabilir olan sitenizin en son yazıların listesi.';
				break;
			case 'ja':
				$widget_name = 'Recent Posts With Thumbnails';
				$widget_desc = 'サイトの最新の投稿を、クリック可能なタイトルとサムネイル付きで一覧表示します。';
				break;
			default:
				$widget_name = 'Recent Posts With Thumbnails';
				$widget_desc = 'List of your site&#8217;s most recent posts, with clickable title and thumbnails.';
		}
		$this->plugin_slug				= 'recent-posts-widget-with-thumbnails';
		$this->plugin_version			= '5.2.2';
		$this->default_number_posts		= 5;
		$this->default_thumb_dimensions	= 'custom';
		$this->default_thumb_width		= absint( round( get_option( 'thumbnail_size_w', 110 ) / 2 ) );
		$this->default_thumb_height 	= absint( round( get_option( 'thumbnail_size_h', 110 ) / 2 ) );
		$this->default_post_title_length = 1000;
		$this->default_excerpt_length	= absint( apply_filters( 'rpwwt_excerpt_length', 55 ) );
		$this->default_excerpt_more		= apply_filters( 'rpwwt_excerpt_more', ' ' . '[&hellip;]' );
		$this->default_category_ids		= array( 0 );
		$this->default_thumb_url		= plugins_url( 'default_thumb.gif', __FILE__ );
		$this->css_file_path			= dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'public.css';
		$this->valid_excerpt_sources	= array( 'post_content', 'excerpt_field' );
		
		$widget_ops = array( 'classname' => $this->plugin_slug, 'description' => $widget_desc );
		parent::__construct( $this->plugin_slug, $widget_name, $widget_ops );

		add_action( 'admin_init',				array( $this, 'load_plugin_textdomain' ) );
		add_action( 'save_post',				array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post',				array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme',				array( $this, 'flush_widget_cache' ) );
		add_action( 'wp_enqueue_scripts',		array( $this, 'enqueue_public_style' ) );
		add_action( 'admin_enqueue_scripts',	array( $this, 'enqueue_admin_style' ) );

		// not in use, just for the po-editor to display the translation on the plugins overview list
		$widget_name = __( 'Recent Posts With Thumbnails', 'recent-posts-widget-with-thumbnails' );
		$widget_desc = __( 'List of your site&#8217;s most recent posts, with clickable title and thumbnails.', 'recent-posts-widget-with-thumbnails' );

	}

	function widget( $args, $instance ) {
		if ( ! isset( $args[ 'widget_id' ] ) ) {
			$args[ 'widget_id' ] = $this->id;
		}

		// get and sanitize values
		$title					= ( ! empty( $instance[ 'title' ] ) )				? esc_html( $instance[ 'title' ] )						: '';
		$title					= apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$category_ids 			= ( ! empty( $instance[ 'category_ids' ] ) )		? array_map( 'absint', $instance[ 'category_ids' ] )	: $this->default_category_ids;
		$default_url 			= ( ! empty( $instance[ 'default_url' ] ) )			? esc_url_raw( $instance[ 'default_url' ] )				: $this->default_thumb_url;
		$thumb_dimensions		= ( ! empty( $instance[ 'thumb_dimensions' ] ) )	? $instance[ 'thumb_dimensions' ]						: $this->default_thumb_dimensions;
		$excerpt_length 		= ( ! empty( $instance[ 'excerpt_length' ] ) )		? absint( $instance[ 'excerpt_length' ] )				: $this->default_excerpt_length;
		$number_posts			= ( ! empty( $instance[ 'number_posts' ] ) )		? absint( $instance[ 'number_posts' ] )					: $this->default_number_posts;
		$post_title_length 		= ( ! empty( $instance[ 'post_title_length' ] ) )	? absint( $instance[ 'post_title_length' ] )			: $this->default_post_title_length;
		$hide_current_post		= ( isset( $instance[ 'hide_current_post' ] ) ) 	? (bool) $instance[ 'hide_current_post' ]				: false;
		$hide_title				= ( isset( $instance[ 'hide_title' ] ) ) 			? (bool) $instance[ 'hide_title' ]						: false;
		$keep_aspect_ratio		= ( isset( $instance[ 'keep_aspect_ratio' ] ) )		? (bool) $instance[ 'keep_aspect_ratio' ]				: false;
		$keep_sticky			= ( isset( $instance[ 'keep_sticky' ] ) )			? (bool) $instance[ 'keep_sticky' ]						: false;
		$only_1st_img			= ( isset( $instance[ 'only_1st_img' ] ) )			? (bool) $instance[ 'only_1st_img' ]					: false;
		$random_order			= ( isset( $instance[ 'random_order' ] ) )			? (bool) $instance[ 'random_order' ]					: false;
		$show_author			= ( isset( $instance[ 'show_author' ] ) )			? (bool) $instance[ 'show_author' ]						: false;
		$show_categories		= ( isset( $instance[ 'show_categories' ] ) )		? (bool) $instance[ 'show_categories' ]					: false;
		$show_comments_number	= ( isset( $instance[ 'show_comments_number' ] ) )	? (bool) $instance[ 'show_comments_number' ]			: false;
		$show_date				= ( isset( $instance[ 'show_date' ] ) )				? (bool) $instance[ 'show_date' ]						: false;
		$show_excerpt			= ( isset( $instance[ 'show_excerpt' ] ) )			? (bool) $instance[ 'show_excerpt' ]					: false;
		$ignore_excerpt			= ( isset( $instance[ 'ignore_excerpt' ] ) )		? (bool) $instance[ 'ignore_excerpt' ]					: false;
		$set_more_as_link		= ( isset( $instance[ 'set_more_as_link' ] ) )		? (bool) $instance[ 'set_more_as_link' ]				: false;
		$show_thumb				= ( isset( $instance[ 'show_thumb' ] ) )			? (bool) $instance[ 'show_thumb' ]						: false;
		$try_1st_img			= ( isset( $instance[ 'try_1st_img' ] ) )			? (bool) $instance[ 'try_1st_img' ]						: false;
		$use_default			= ( isset( $instance[ 'use_default' ] ) )			? (bool) $instance[ 'use_default' ]						: false;
		$open_new_window		= ( isset( $instance[ 'open_new_window' ] ) )		? (bool) $instance[ 'open_new_window' ]					: false;
		$print_post_categories	= ( isset( $instance[ 'print_post_categories' ] ) )	? (bool) $instance[ 'print_post_categories' ]			: false;

		// let empty string
		if ( ! empty( $instance[ 'excerpt_more' ] ) ) {
			$excerpt_more = $instance[ 'excerpt_more' ];
		} elseif ( '' == $instance[ 'excerpt_more' ] ) {
			$excerpt_more = '';
		} else {
			$excerpt_more = $this->default_excerpt_more;
		}
		
		// if 'all categories' was selected ignore other selections of categories
		if ( in_array( 0, $category_ids ) ) {
			$category_ids = $this->default_category_ids;
		}

		/*
		// sanitizes vars
		if ( ! $number_posts )  	$number_posts = $this->default_number_posts;
		if ( ! $thumb_dimensions )	$thumb_dimensions = $this->default_thumb_dimensions;
		if ( ! $default_url )	    $default_url = $this->default_thumb_url;
		*/
		
		// get image size
		// take custom size if desired
		if ( $thumb_dimensions == $this->default_thumb_dimensions ) {
			$thumb_width  = ( ! empty( $instance[ 'thumb_width' ]  ) ) ? absint( $instance[ 'thumb_width' ]  ) : $this->default_thumb_width;
			$thumb_height = ( ! empty( $instance[ 'thumb_height' ] ) ) ? absint( $instance[ 'thumb_height' ] ) : $this->default_thumb_height;
		} else {
			list( $thumb_width, $thumb_height ) = $this->get_image_sizes( $thumb_dimensions );
		}

		// set image dimension array
		$this->current_thumb_dimensions = array( $thumb_width, $thumb_height );

		// set default image code
		$default_attr = array(
			'src'	=> $default_url,
			'class'	=> "attachment-" . join( 'x', $this->current_thumb_dimensions ),
			'alt'	=> '',
		);
		$default_img = '<img ';
		$default_img .= rtrim( image_hwstring( $thumb_width, $thumb_height ) );
		foreach ( $default_attr as $name => $value ) {
			$default_img .= ' ' . $name . '="' . $value . '"';
		}
		$default_img .= ' />';
		
		// set link target
		if ( $open_new_window ) {
			$link_target = ' target="_blank"';
		} else {
			$link_target = '';
		}
		
		// filter the arguments for the Recent Posts widget:
		
		// standard params
		$query_args = array(
			'posts_per_page'      => $number_posts,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
		);
		
		// ignore sticky posts if desired, else show them on top
		$query_args[ 'ignore_sticky_posts' ] = ( $keep_sticky ) ? false : true;
		
		// set order of posts in widget
		$query_args[ 'orderby' ] = ( $random_order ) ? 'rand' : 'date';
		$query_args[ 'order' ] = 'DESC';
		
		// add categories param only if 'all categories' was not selected
		if ( ! in_array( 0, $category_ids ) ) {
			$query_args[ 'category__in' ] = $category_ids;
		}
		
		// exclude current displayed post
		if ( $hide_current_post ) {
			global $post;
			if ( isset( $post->ID ) and is_singular() ) {
				$query_args[ 'post__not_in' ] = array( $post->ID );
			}
		}

		// run the query: get the latest posts
		$r = new WP_Query( apply_filters( 'rpwwt_widget_posts_args', $query_args ) );

		if ( $r->have_posts()) :
		
			// translate repeately used texts once (for more performance)
			$this->in_categories_text = _x( 'In', 'In {categories}', 'recent-posts-widget-with-thumbnails' );
			$text = ', ';
			$this->comma_text = __( $text );
			$text = 'By %s';
			$this->author_text = _x( $text, 'theme author' );

			// print list
			include 'includes/widget.php';

			// Reset the global $the_post as this query will have stomped on it
			wp_reset_postdata();

		endif;

	}

	function update( $new_widget_settings, $old_widget_settings ) {
		$instance = $old_widget_settings;
		// sanitize user input before update
		$instance[ 'title' ] 				= ( isset( $new_widget_settings[ 'title' ] ) )					? strip_tags( $new_widget_settings[ 'title' ] )						: '';
		$instance[ 'default_url' ] 			= ( isset( $new_widget_settings[ 'default_url' ] ) )			? esc_url_raw( $new_widget_settings[ 'default_url' ] )				: $this->default_thumb_url;
		$instance[ 'thumb_dimensions' ] 	= ( isset( $new_widget_settings[ 'thumb_dimensions' ] ) )		? strip_tags( $new_widget_settings[ 'thumb_dimensions' ] )			: $this->default_thumb_dimensions;
		$instance[ 'category_ids' ]   		= ( isset( $new_widget_settings[ 'category_ids' ] ) )			? array_map( 'absint', $new_widget_settings[ 'category_ids' ] )		: $this->default_category_ids;
		$instance[ 'excerpt_length' ] 		= ( isset( $new_widget_settings[ 'excerpt_length' ] ) )			? absint( $new_widget_settings[ 'excerpt_length' ] )				: $this->default_excerpt_length;
		$instance[ 'number_posts' ]			= ( isset( $new_widget_settings[ 'number_posts' ] ) )			? absint( $new_widget_settings[ 'number_posts' ] )					: $this->default_number_posts;
		$instance[ 'post_title_length' ] 	= ( isset( $new_widget_settings[ 'post_title_length' ] ) )		? absint( $new_widget_settings[ 'post_title_length' ] )				: $this->default_post_title_length;
		$instance[ 'thumb_height' ] 		= ( isset( $new_widget_settings[ 'thumb_height' ] ) )			? absint( $new_widget_settings[ 'thumb_height' ] )					: $this->default_thumb_height;
		$instance[ 'thumb_width' ] 			= ( isset( $new_widget_settings[ 'thumb_width' ] ) )			? absint( $new_widget_settings[ 'thumb_width' ] )					: $this->default_thumb_width;
		$instance[ 'hide_current_post' ]	= ( isset( $new_widget_settings[ 'hide_current_post' ] ) )		? (bool) $new_widget_settings[ 'hide_current_post' ]				: false;
		$instance[ 'hide_title' ] 			= ( isset( $new_widget_settings[ 'hide_title' ] ) )				? (bool) $new_widget_settings[ 'hide_title' ]						: false;
		$instance[ 'keep_aspect_ratio'] 	= ( isset( $new_widget_settings[ 'keep_aspect_ratio' ] ) )		? (bool) $new_widget_settings[ 'keep_aspect_ratio' ]				: false;
		$instance[ 'keep_sticky' ] 			= ( isset( $new_widget_settings[ 'keep_sticky' ] ) )			? (bool) $new_widget_settings[ 'keep_sticky' ]						: false;
		$instance[ 'only_1st_img' ] 		= ( isset( $new_widget_settings[ 'only_1st_img' ] ) )			? (bool) $new_widget_settings[ 'only_1st_img' ]						: false;
		$instance[ 'random_order' ]			= ( isset( $new_widget_settings[ 'random_order' ] ) )			? (bool) $new_widget_settings[ 'random_order' ]						: false;
		$instance[ 'show_author' ]			= ( isset( $new_widget_settings[ 'show_author' ] ) )			? (bool) $new_widget_settings[ 'show_author' ]						: false;
		$instance[ 'show_categories' ]		= ( isset( $new_widget_settings[ 'show_categories' ] ) )		? (bool) $new_widget_settings[ 'show_categories' ]					: false;
		$instance[ 'show_comments_number' ]	= ( isset( $new_widget_settings[ 'show_comments_number' ] ) )	? (bool) $new_widget_settings[ 'show_comments_number' ]				: false;
		$instance[ 'show_date' ] 			= ( isset( $new_widget_settings[ 'show_date' ] ) )				? (bool) $new_widget_settings[ 'show_date' ]						: false;
		$instance[ 'show_excerpt' ] 		= ( isset( $new_widget_settings[ 'show_excerpt' ] ) )			? (bool) $new_widget_settings[ 'show_excerpt' ]						: false;
		$instance[ 'ignore_excerpt' ] 		= ( isset( $new_widget_settings[ 'ignore_excerpt' ] ) )			? (bool) $new_widget_settings[ 'ignore_excerpt' ]					: false;
		$instance[ 'set_more_as_link' ] 	= ( isset( $new_widget_settings[ 'set_more_as_link' ] ) )		? (bool) $new_widget_settings[ 'set_more_as_link' ]					: false;
		$instance[ 'show_thumb' ] 			= ( isset( $new_widget_settings[ 'show_thumb' ] ) )				? (bool) $new_widget_settings[ 'show_thumb' ]						: false;
		$instance[ 'try_1st_img' ] 			= ( isset( $new_widget_settings[ 'try_1st_img' ] ) )			? (bool) $new_widget_settings[ 'try_1st_img' ]						: false;
		$instance[ 'use_default' ] 			= ( isset( $new_widget_settings[ 'use_default' ] ) )			? (bool) $new_widget_settings[ 'use_default' ]						: false;
		$instance[ 'open_new_window' ]		= ( isset( $new_widget_settings[ 'open_new_window' ] ) )		? (bool) $new_widget_settings[ 'open_new_window' ]					: false;
		$instance[ 'print_post_categories' ]= ( isset( $new_widget_settings[ 'print_post_categories' ] ) )	? (bool) $new_widget_settings[ 'print_post_categories' ]			: false;

		// let empty string
		if ( isset( $new_widget_settings[ 'excerpt_more' ] ) ) {
			$instance[ 'excerpt_more' ] = $new_widget_settings[ 'excerpt_more' ];
		} elseif ( '' == $new_widget_settings[ 'excerpt_more' ] ) {
			$instance[ 'excerpt_more' ] = '';
		} else {
			$instance[ 'excerpt_more' ] = $this->default_excerpt_more;
		}

		// if 'all categories' was selected ignore other selections of categories
		if ( in_array( 0, $instance[ 'category_ids' ] ) ) {
			$instance[ 'category_ids' ] = $this->default_category_ids;
		}
		
		// empty widget cache
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions[ $this->plugin_slug ] ) ) {
			delete_option( $this->plugin_slug );
		}

		// delete current css file to let make new one via $this->enqueue_public_style()
		if ( file_exists( $this->css_file_path ) ) {
			// remove the file
			unlink( $this->css_file_path );
		}

		// return sanitized current widget settings
		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete( $this->plugin_slug, 'widget' );
	}

	function form( $instance ) {
		// get and sanitize values
		$title					= ( isset( $instance[ 'title' ] ) ) 				? esc_html( $instance[ 'title' ] )						: '';
		$thumb_dimensions		= ( isset( $instance[ 'thumb_dimensions' ] ) )		? $instance[ 'thumb_dimensions' ]						: $this->default_thumb_dimensions;
		$default_url			= ( isset( $instance[ 'default_url' ] ) )			? esc_url_raw( $instance[ 'default_url' ] )				: $this->default_thumb_url;
		$excerpt_length			= ( isset( $instance[ 'excerpt_length' ] ) )		? absint( $instance[ 'excerpt_length' ] )				: $this->default_excerpt_length;
		$post_title_length		= ( isset( $instance[ 'post_title_length' ] ) )		? absint( $instance[ 'post_title_length' ] )			: $this->default_post_title_length;
		$number_posts			= ( isset( $instance[ 'number_posts' ] ) )			? absint( $instance[ 'number_posts' ] )					: $this->default_number_posts;
		$thumb_height			= ( isset( $instance[ 'thumb_height' ] ) )			? absint( $instance[ 'thumb_height' ] )					: $this->default_thumb_height;
		$thumb_width			= ( isset( $instance[ 'thumb_width' ] ) )			? absint( $instance[ 'thumb_width' ] )					: $this->default_thumb_width;
		$category_ids			= ( isset( $instance[ 'category_ids' ] ) )			? array_map( 'absint', $instance[ 'category_ids' ] )	: $this->default_category_ids;
		$hide_current_post		= ( isset( $instance[ 'hide_current_post' ] ) )		? (bool) $instance[ 'hide_current_post' ]				: false;
		$hide_title				= ( isset( $instance[ 'hide_title' ] ) )			? (bool) $instance[ 'hide_title' ]						: false;
		$keep_aspect_ratio		= ( isset( $instance[ 'keep_aspect_ratio' ] ) )		? (bool) $instance[ 'keep_aspect_ratio' ]				: false;
		$keep_sticky			= ( isset( $instance[ 'keep_sticky' ] ) )			? (bool) $instance[ 'keep_sticky' ]						: false;
		$only_1st_img			= ( isset( $instance[ 'only_1st_img' ] ) )			? (bool) $instance[ 'only_1st_img' ]					: false;
		$random_order			= ( isset( $instance[ 'random_order' ] ) )			? (bool) $instance[ 'random_order' ]					: false;
		$show_author			= ( isset( $instance[ 'show_author' ] ) )			? (bool) $instance[ 'show_author' ]						: false;
		$show_categories		= ( isset( $instance[ 'show_categories' ] ) )		? (bool) $instance[ 'show_categories' ]					: false;
		$show_comments_number	= ( isset( $instance[ 'show_comments_number' ] ) )	? (bool) $instance[ 'show_comments_number' ]			: false;
		$show_date				= ( isset( $instance[ 'show_date' ] ) )				? (bool) $instance[ 'show_date' ]						: false;
		$show_excerpt			= ( isset( $instance[ 'show_excerpt' ] ) )			? (bool) $instance[ 'show_excerpt' ]					: false;
		$ignore_excerpt			= ( isset( $instance[ 'ignore_excerpt' ] ) )		? (bool) $instance[ 'ignore_excerpt' ]					: false;
		$set_more_as_link		= ( isset( $instance[ 'set_more_as_link' ] ) )		? (bool) $instance[ 'set_more_as_link' ]				: false;
		$show_thumb				= ( isset( $instance[ 'show_thumb' ] ) )			? (bool) $instance[ 'show_thumb' ]						: true;
		$try_1st_img			= ( isset( $instance[ 'try_1st_img' ] ) )			? (bool) $instance[ 'try_1st_img' ]						: false;
		$use_default			= ( isset( $instance[ 'use_default' ] ) )			? (bool) $instance[ 'use_default' ]						: false;
		$open_new_window		= ( isset( $instance[ 'open_new_window' ] ) )		? (bool) $instance[ 'open_new_window' ]					: false;
		$print_post_categories	= ( isset( $instance[ 'print_post_categories' ] ) )	? (bool) $instance[ 'print_post_categories' ]			: false;

		// let empty string
		if ( isset( $instance[ 'excerpt_more' ] ) ) { 
			if ( '' == $instance[ 'excerpt_more' ] ) {
				$excerpt_more = '';
			} else {
				$excerpt_more = $instance[ 'excerpt_more' ];
			}
		} else {
			 $excerpt_more = $this->default_excerpt_more;
		}
		
		// if 'all categories' was selected ignore other selections of categories
		if ( in_array( 0, $category_ids ) ) {
			$category_ids = $this->default_category_ids;
		}

		// sanitize vars
		if ( ! $category_ids )		$category_ids		= $this->default_category_ids;
		if ( ! $default_url )		$default_url		= $this->default_thumb_url;
		if ( ! $post_title_length )	$post_title_length	= $this->default_post_title_length;
		if ( ! $excerpt_length )	$excerpt_length		= $this->default_excerpt_length;
		if ( ! $number_posts )		$number_posts		= $this->default_number_posts;
		if ( ! $thumb_dimensions )	$thumb_dimensions	= $this->default_thumb_dimensions;
		if ( ! $thumb_height )		$thumb_height		= $this->default_thumb_height;
		if ( ! $thumb_width )		$thumb_width		= $this->default_thumb_width;
		
		// compute ids only once to improve performance
		$id_category_ids			= $this->get_field_id( 'category_ids' );
		$id_default_url				= $this->get_field_id( 'default_url' );
		$id_excerpt_length			= $this->get_field_id( 'excerpt_length' );
		$id_excerpt_more			= $this->get_field_id( 'excerpt_more' );
		$id_set_more_as_link		= $this->get_field_id( 'set_more_as_link' );
		$id_hide_current_post		= $this->get_field_id( 'hide_current_post' );
		$id_hide_title				= $this->get_field_id( 'hide_title' );
		$id_keep_aspect_ratio 		= $this->get_field_id( 'keep_aspect_ratio' );
		$id_keep_sticky				= $this->get_field_id( 'keep_sticky' );
		$id_number_posts			= $this->get_field_id( 'number_posts' );
		$id_only_1st_img			= $this->get_field_id( 'only_1st_img' );
		$id_post_title_length		= $this->get_field_id( 'post_title_length' );
		$id_random_order			= $this->get_field_id( 'random_order' );
		$id_show_author				= $this->get_field_id( 'show_author' );
		$id_show_categories			= $this->get_field_id( 'show_categories' );
		$id_show_comments_number	= $this->get_field_id( 'show_comments_number' );
		$id_show_date				= $this->get_field_id( 'show_date' );
		$id_show_excerpt			= $this->get_field_id( 'show_excerpt' );
		$id_ignore_excerpt			= $this->get_field_id( 'ignore_excerpt' );
		$id_show_thumb				= $this->get_field_id( 'show_thumb' );
		$id_thumb_dimensions		= $this->get_field_id( 'thumb_dimensions' );
		$id_thumb_height			= $this->get_field_id( 'thumb_height' );
		$id_thumb_width				= $this->get_field_id( 'thumb_width' );
		$id_title					= $this->get_field_id( 'title' );
		$id_try_1st_img				= $this->get_field_id( 'try_1st_img' );
		$id_use_default				= $this->get_field_id( 'use_default' );
		$id_open_new_window			= $this->get_field_id( 'open_new_window' );
		$id_print_post_categories	= $this->get_field_id( 'print_post_categories' );
		
		// get texts and values for image sizes dropdown
		global $_wp_additional_image_sizes;
		$wp_standard_image_size_labels = array();
		$label = 'Full Size';	$wp_standard_image_size_labels[ 'full' ]		= __( $label );
		$label = 'Large';		$wp_standard_image_size_labels[ 'large' ]		= __( $label );
		$label = 'Medium';		$wp_standard_image_size_labels[ 'medium' ]		= __( $label );
		$label = 'Thumbnail';	$wp_standard_image_size_labels[ 'thumbnail' ]	= __( $label );
		
		$wp_standard_image_size_names = array_keys( $wp_standard_image_size_labels );
		$size_options = array();
		foreach ( get_intermediate_image_sizes() as $size_name ) {
			// Don't take numeric sizes that appear
			if( is_integer( $size_name ) ) {
				continue;
			}
			$option_values = array();

			// Set technical name
			$option_values[ 'size_name' ] = $size_name;
			
			// Set name
			$option_values[ 'name' ] = in_array( $size_name, $wp_standard_image_size_names ) ? $wp_standard_image_size_labels[$size_name] : $size_name;
			
			// Set width
			$option_values[ 'width' ] = isset( $_wp_additional_image_sizes[$size_name]['width'] ) ? $_wp_additional_image_sizes[$size_name]['width'] : get_option( "{$size_name}_size_w" );
			
			// Set height
			$option_values[ 'height' ] = isset( $_wp_additional_image_sizes[$size_name]['height'] ) ? $_wp_additional_image_sizes[$size_name]['height'] : get_option( "{$size_name}_size_h" );
			
			// add option to options list
			$size_options[] = $option_values;
			
		}
		
		// create text to Media Settings page
		$text = 'Settings';	$label_settings	= __( $text );
		$text = 'Media';	$label_media	= _x( $text, 'post type general name' );
		$label = sprintf( '%s &rsaquo; %s', $label_settings, $label_media );
		$media_trail = ( current_user_can( 'manage_options' ) ) ? sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( admin_url( 'options-media.php' ) ), esc_html( $label ) ) : sprintf( '<em>%s</em>', esc_html( $label ) );

		// get texts and values for categories dropdown
		#$none_text = 'All Categories';
		$all_text = 'All Categories';
		$label_all_cats = __( $all_text );

		// get categories
		$categories = get_categories( array( 'hide_empty' => 0, 'hierarchical' => 1 ) );
		$number_of_cats = count( $categories );
		
		// get size (number of rows to display) of selection box: not more than 10
		$number_of_rows = ( 10 > $number_of_cats ) ? $number_of_cats + 1 : 10;

		// start selection box
		$selection_element = sprintf(
			'<select name="%s[]" id="%s" class="rpwwt-cat-select" multiple size="%d">',
			$this->get_field_name( 'category_ids' ),
			$id_category_ids,
			$number_of_rows
		);
		$selection_element .= "\n";

		// make selection box entries
		$cat_list = array();
		if ( 0 < $number_of_cats ) {

			// make a hierarchical list of categories
			while ( $categories ) {
				// go on with the first element in the categories list:
				// if there is no parent
				if ( '0' == $categories[ 0 ]->parent ) {
					// get and remove it from the categories list
					$current_entry = array_shift( $categories );
					// append the current entry to the new list
					$cat_list[] = array(
						'id'	=> absint( $current_entry->term_id ),
						'name'	=> esc_html( $current_entry->name ),
						'depth'	=> 0
					);
					// go on looping
					continue;
				}
				// if there is a parent:
				// try to find parent in new list and get its array index
				$parent_index = $this->get_cat_parent_index( $cat_list, $categories[ 0 ]->parent );
				// if parent is not yet in the new list: try to find the parent later in the loop
				if ( false === $parent_index ) {
					// get and remove current entry from the categories list
					$current_entry = array_shift( $categories );
					// append it at the end of the categories list
					$categories[] = $current_entry;
					// go on looping
					continue;
				}
				// if there is a parent and parent is in new list:
				// set depth of current item: +1 of parent's depth
				$depth = $cat_list[ $parent_index ][ 'depth' ] + 1;
				// set new index as next to parent index
				$new_index = $parent_index + 1;
				// find the correct index where to insert the current item
				foreach( $cat_list as $entry ) {
					// if there are items with same or higher depth than current item
					if ( $depth <= $entry[ 'depth' ] ) {
						// increase new index
						$new_index = $new_index + 1;
						// go on looping in foreach()
						continue;
					}
					// if the correct index is found:
					// get current entry and remove it from the categories list
					$current_entry = array_shift( $categories );
					// insert current item into the new list at correct index
					$end_array = array_splice( $cat_list, $new_index ); // $cat_list is changed, too
					$cat_list[] = array(
						'id'	=> absint( $current_entry->term_id ),
						'name'	=> esc_html( $current_entry->name ),
						'depth'	=> $depth
					);
					$cat_list = array_merge( $cat_list, $end_array );
					// quit foreach(), go on while-looping
					break;
				} // foreach( cat_list )
			} // while( categories )

			// make HTML of selection box
			$selected = ( in_array( 0, $category_ids ) ) ? ' selected="selected"' : '';
			$selection_element .= "\t";
			$selection_element .= '<option value="0"' . $selected . '>' . $label_all_cats . '</option>';
			$selection_element .= "\n";

			foreach ( $cat_list as $category ) {
				$cat_name = apply_filters( 'rpwwt_list_cats', $category[ 'name' ], $category );
				$pad = ( 0 < $category[ 'depth' ] ) ? str_repeat('&ndash;&nbsp;', $category[ 'depth' ] ) : '';
				$selection_element .= "\t";
				$selection_element .= '<option value="' . $category[ 'id' ] . '"';
				$selection_element .= ( in_array( $category[ 'id' ], $category_ids ) ) ? ' selected="selected"' : '';
				$selection_element .= '>' . $pad . $cat_name . '</option>';
				$selection_element .= "\n";
			}
			
		}

		// close selection box
		$selection_element .= "</select>\n";
		
		// print form in widgets page
		include 'includes/form.php';

	}
	
	/**
	 * Return the array index of a given ID
	 *
	 * @since 4.1
	 */
	private function get_cat_parent_index( $arr, $id ) {
		$len = count( $arr );
		if ( 0 == $len ) {
			return false;
		}
		$id = absint( $id );
		for ( $i = 0; $i < $len; $i++ ) {
			if ( $id == $arr[ $i ][ 'id' ] ) {
				return $i;
			}
		}
		return false; 
	}
	
	/**
	 * Load the widget's CSS in the HEAD section of the frontend
	 *
	 * @since 2.3
	 */
	public function enqueue_public_style () {
		
		$is_available = true;
		
		// make sure the css file exists; if not available: generate it
		if ( ! file_exists( $this->css_file_path ) ) {
			// make the file
			$is_available = $this->make_css_file();
		}
		
		// enqueue the style if there is a file
		if ( $is_available ) {
			wp_enqueue_style(
				$this->plugin_slug . '-public-style',
				plugin_dir_url( __FILE__ ) . 'public.css',
				array(),
				$this->plugin_version,
				'all' 
			);
		}
	}

	/**
	 * Load the widget's CSS in the HEAD section of the backend
	 *
	 * @since 4.0
	 */
	public function enqueue_admin_style () {
		// load CSS file on Widgets page only
		$screen = get_current_screen();
		if ( 'widgets' == $screen->id ) {
			// enqueue the style
			wp_enqueue_style(
				$this->plugin_slug . '-admin-style',
				plugin_dir_url( __FILE__ ) . 'admin.css',
				array(),
				$this->plugin_version,
				'all' 
			);
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain( 'recent-posts-widget-with-thumbnails', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}

	/**
	 * Returns the id of the first image in the content, else 0
	 *
	 * @access   private
	 * @since     2.0
	 *
	 * @return    integer    the post id of the first content image
	 */
	private function get_first_content_image_id () {
		// set variables
		global $wpdb;
		$post = get_post();
		if ( $post and isset( $post->post_content ) ) {
			// look for images in HTML code
			preg_match_all( '/<img[^>]+>/i', $post->post_content, $all_img_tags );
			if ( $all_img_tags ) {
				foreach ( $all_img_tags[ 0 ] as $img_tag ) {
					// find class attribute and catch its value
					preg_match( '/<img.*?class\s*=\s*[\'"]([^\'"]+)[\'"][^>]*>/i', $img_tag, $img_class );
					if ( $img_class ) {
						// Look for the WP image id
						preg_match( '/wp-image-([\d]+)/i', $img_class[ 1 ], $found_id );
						// if first image id found: check whether is image
						if ( $found_id ) {
							$img_id = absint( $found_id[ 1 ] );
							// if is image: return its id
							if ( wp_attachment_is_image( $img_id ) ) {
								return $img_id;
							}
						} // if(found_id)
					} // if(img_class)
					
					// else: try to catch image id by its url as stored in the database
					// find src attribute and catch its value
					preg_match( '/<img.*?src\s*=\s*[\'"]([^\'"]+)[\'"][^>]*>/i', $img_tag, $img_src );
					if ( $img_src ) {
						// delete optional query string in img src
						$url = preg_replace( '/([^?]+).*/', '\1', $img_src[ 1 ] );
						// delete image dimensions data in img file name, just take base name and extension
						$guid = preg_replace( '/(.+)-\d+x\d+\.(\w+)/', '\1.\2', $url );
						// look up its id in the db
						$found_id = $wpdb->get_var( $wpdb->prepare( "SELECT `ID` FROM $wpdb->posts WHERE `guid` = '%s'", $guid ) );
						// if id is available: return it
						if ( $found_id ) {
							return absint( $found_id );
						} // if(found_id)
					} // if(img_src)
				} // foreach(img_tag)
			} // if(all_img_tags)
		} // if (post content)
		
		// if nothing found: return 0
		return 0;
	}

	/**
	 * Echoes the thumbnail of first post's image and returns success
	 *
	 * @access   private
	 * @since     2.0
	 *
	 * @return    bool    success on finding an image
	 */
	private function the_first_post_image () {
		// look for first image
		$thumb_id = $this->get_first_content_image_id();
		// if there is first image then show first image
		if ( $thumb_id ) :
			echo wp_get_attachment_image( $thumb_id, $this->current_thumb_dimensions );
			return true;
		else :
			return false;
		endif; // thumb_id
	}

	/**
	 * Returns the assigned categories of a post in a string
	 *
	 * @access   private
	 * @since     4.6
	 *
	 */
	private function get_the_categories ( $id ) {
		$terms = get_the_terms( $id, 'category' );

		if ( is_wp_error( $terms ) ) {
			return __( 'Error on listing categories', 'recent-posts-widget-with-thumbnails' );
		}

		if ( empty( $terms ) ) {
			$text = 'No categories';
			return __( $text );
		}

		$categories = array();

		foreach ( $terms as $term ) {
			$categories[] = $term->name;
		}

		$string = $this->in_categories_text . ' ';
		$string .= join( $this->comma_text, $categories );
		
		return $string;
	}

	/**
	 * Returns the assigned author of a post in a string
	 *
	 * @access   private
	 * @since     4.8
	 *
	 */
	private function get_the_author () {
		$author = get_the_author();

		if ( empty( $author ) ) {
			return '';
		} else {
			return sprintf( $this->author_text, $author );
		}

	}

	/**
	 * Generate the css file with stored settings
	 *
	 * @since 2.3
	 */
	private function make_css_file () {

		// get stored settings
		$all_instances = $this->get_settings();
		$set_default = true;

		// generate CSS
		$css_code  = ".rpwwt-widget ul { list-style: outside none none; margin-left: 0; margin-right: 0; padding-left: 0; padding-right: 0; }\n"; 
		$css_code .= ".rpwwt-widget ul li { overflow: hidden; margin: 0 0 1.5em; }\n"; 
		$css_code .= ".rpwwt-widget ul li:last-child { margin: 0; }\n"; 
		if ( is_rtl() ) {
			$css_code .= ".rpwwt-widget ul li img { display: inline; float: right; margin: .3em 0 .75em .75em; }\n";
		} else {
			$css_code .= ".rpwwt-widget ul li img { display: inline; float: left; margin: .3em .75em .75em 0; }\n";
		}

		foreach ( $all_instances as $number => $settings ) {
			// set width and height
			$thumb_width = $this->default_thumb_width;
			$thumb_height = $this->default_thumb_height;
			$thumb_dimensions = isset( $settings[ 'thumb_dimensions' ] ) ? $settings[ 'thumb_dimensions' ] : $this->default_thumb_dimensions;
			if ( $thumb_dimensions == $this->default_thumb_dimensions ) {
				if ( isset( $settings[ 'thumb_width' ] ) ) {
					$thumb_width  = absint( $settings[ 'thumb_width' ]  );
				}
				if ( isset( $settings[ 'thumb_height' ] ) ) {
					$thumb_height = absint( $settings[ 'thumb_height' ] );
				}
			} else {
				list( $thumb_width, $thumb_height ) = $this->get_image_sizes( $thumb_dimensions );
			} // $settings[ 'thumb_dimensions' ]
			// get aspect ratio option
			$keep_aspect_ratio = false;
			if ( isset( $settings[ 'keep_aspect_ratio' ] ) ) {
				$keep_aspect_ratio = (bool) $settings[ 'keep_aspect_ratio' ];
			}
			// set CSS code
			if ( $keep_aspect_ratio ) {
				$css_code .= sprintf( '#rpwwt-%s-%d img { max-width: %dpx; width: 100%%; height: auto; }', $this->plugin_slug, $number, $thumb_width );
				$css_code .= "\n"; 
			} else {
				$css_code .= sprintf( '#rpwwt-%s-%d img { width: %dpx; height: %dpx; }', $this->plugin_slug, $number, $thumb_width, $thumb_height );
				$css_code .= "\n"; 
			}
			// override default code
			$set_default = false;
		} // foreach ( $all_instances as $number => $settings )
		// set at least this statement if no settings are stored
		if ( $set_default ) {
			$css_code .= sprintf( '.rpwwt-widget ul li img { width: %dpx; height: %dpx; }', $this->default_thumb_width, $this->default_thumb_height );
			$css_code .= "\n"; 
		}

		// write file safely; print inline CSS on error
		$success = true;
		try {
			if ( false === @file_put_contents( $this->css_file_path, $css_code ) ) {
				$success = false;
				throw new Exception();
			} else {
				// file writing was successfull, so change file permissions
				chmod( $this->css_file_path, 0644 );
			}
		} catch (Exception $e) {
			print "\n<!-- Recent Posts Widget With Thumbnails: Could not open the CSS file! Print inline CSS instead: -->\n";
			printf( "<style type='text/css'>%s</style>\n", $css_code );
		}
		return $success;
	}

	/**
	 * Returns the shortened excerpt, must use in a loop.
	 *
	 * @since 3.0
	 */
	private function get_the_trimmed_excerpt ( $excerpt_length = 55, $excerpt_more = ' [&hellip;]', $ignore_excerpt = false, $set_more_as_link = false, $link_target = '' ) {
		
		$post = get_post();
								
		if ( empty( $post ) ) {
			return '';
		}

		$excerpt = '';
		
		if ( post_password_required( $post ) ) {
			$excerpt = 'There is no excerpt because this is a protected post.';
			return esc_html__( $excerpt );
		}

		// get excerpt from text field if desired
		if ( ! $ignore_excerpt ) {
			$excerpt = $post->post_excerpt;
		}
		
		// text processings if no manual excerpt is available
		if ( empty( $excerpt ) ) {

			// get excerpt from post content
			$excerpt = strip_shortcodes( get_the_content( '' ) );
			$excerpt = apply_filters( 'the_content', $excerpt );
			$excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
			$excerpt = wp_trim_words( $excerpt, $excerpt_length, $excerpt_more );
			
			// if excerpt is longer than desired
			if ( mb_strlen( $excerpt ) > $excerpt_length ) {
				// get excerpt in desired length
				$sub_excerpt = mb_substr( $excerpt, 0, $excerpt_length );
				// get array of shortened excerpt words
				$excerpt_words = explode( ' ', $sub_excerpt );
				// get the length of the last word in the shortened excerpt
				$excerpt_cut = - ( mb_strlen( $excerpt_words[ count( $excerpt_words ) - 1 ] ) );
				// if there is no empty string
				if ( $excerpt_cut < 0 ) {
					// get the shorter excerpt until the last word
					$excerpt = mb_substr( $sub_excerpt, 0, $excerpt_cut );
				} else {
					// get the shortened excerpt
					$excerpt = $sub_excerpt;
				}
				
			}
			
			// append 'more' text, set 'more' signs as link if desired
			if ( $set_more_as_link ) {
				$excerpt .= sprintf( '<a href="%s"%s>%s</a>', get_the_permalink( $post ), $link_target, $excerpt_more );
			} else {
				$excerpt .= $excerpt_more;
			}
			
		}
		
		// return text
		return $excerpt;
	}

	/**
	 * Returns the shortened post title, must use in a loop.
	 *
	 * @since 4.5
	 */
	private function get_the_trimmed_post_title ( $len = 1000, $more = '&hellip;' ) {
		
		// get current post's post_title
		$post_title = get_the_title();

		// if post_title is longer than desired
		if ( mb_strlen( $post_title ) > $len ) {
			// get post_title in desired length
			$post_title = mb_substr( $post_title, 0, $len );
			// append ellipses
			$post_title .= $more;
		}
		// return text
		return $post_title;
	}

	/**
	 * Returns width and height of a image size name, else default sizes
	 *
	 * @since 4.0
	 */
	private function get_image_sizes ( $size = 'thumbnail' ) {

		$width  = 0;
		$height = 0;
		// check if selected size is in registered images sizes
		if ( in_array( $size, get_intermediate_image_sizes() ) ) {
			// if in WordPress standard image sizes
			if ( in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) ) {
				$width  = get_option( $size . '_size_w' );
				$height = get_option( $size . '_size_h' );
			} else {
				// custom image sizes, formerly added via add_image_size()
				global $_wp_additional_image_sizes;
				$width  = $_wp_additional_image_sizes[ $size ][ 'width' ];
				$height = $_wp_additional_image_sizes[ $size ][ 'height' ];
			}
		}
		// check if vars have true values, else use default size
		if ( ! $width )  $width  = $this->default_thumb_width;
		if ( ! $height ) $height = $this->default_thumb_height;
		
		// return sizes
		return array( $width, $height );
	}
	
}

/**
 * Register widget on init
 *
 * @since 1.0
 */
function register_recent_posts_widget_with_thumbnails () {
	register_widget( 'Recent_Posts_Widget_With_Thumbnails' );
}
add_action( 'widgets_init', 'register_recent_posts_widget_with_thumbnails', 1 );