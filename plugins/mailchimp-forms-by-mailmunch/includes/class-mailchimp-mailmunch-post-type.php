<?php
class Mailchimp_Mailmunch_Post_Type {
  /**
  * Register custom post type for Landing Pages
  *
  * @since    3.0.0
  */
  
  public function initiate_api() {
		if (empty($this->mailmunch_api)) {
			$this->mailmunch_api = new Mailchimp_Mailmunch_Api();
		}
		return $this->mailmunch_api;
	}
  
  public function create_post_type() {
    // Register Custom Post Type
    $labels = array(
        'name'               => __( 'Landing Pages by MailMunch'),
        'singular_name'      => __( 'MailMunch Page' ),
        'menu_name'          => __( 'Landing Pages' ),
        'name_admin_bar'     => __( 'Landing Page' ),
        'add_new'            => __( 'Add New Page' ),
        'add_new_item'       => __( 'Add New Page' ),
        'new_item'           => __( 'New Page' ),
        'edit_item'          => __( 'Edit Page' ),
        'view_item'          => __( 'View Page' ),
        'all_items'          => __( 'All Pages' ),
        'search_items'       => __( 'Search Pages' ),
        'parent_item_colon'  => __( 'Parent Pages:' ),
        'not_found'          => __( 'No pages found.' ),
        'not_found_in_trash' => __( 'No pages found in Trash.' )
    );

    $args = array(
        'labels'             => $labels,
        'menu_icon'          => 'dashicons-admin-page',
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('with_front' => false, 'slug' => MAILCHIMP_MAILMUNCH_POST_TYPE ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array( 'title', 'author' )
    );
    register_post_type( MAILCHIMP_MAILMUNCH_POST_TYPE, $args );
  }
  
  /**
  * Template
  */
  public function post_type_template( $template ) {
    global $post;
    if (empty($post)) return $template;
    if ($post->post_type == MAILCHIMP_MAILMUNCH_POST_TYPE) {
      return plugin_dir_path( dirname( __FILE__ ) ) . 'public/mailchimp-mailmunch-landing-page.php';
    }
    return $template;
  }
  
  public function post_type_desc( $views ) {
    $currentStep = 'landingpages';
    require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/mailchimp-mailmunch-tabs.php');
    return $views;
  }

  public function post_meta_box($postType, $post) {
      if ($postType == MAILCHIMP_MAILMUNCH_POST_TYPE) {
          add_meta_box(
              'mailchimp_mailmunch_meta_choose_page',
              'Choose or Create New Page',
              array($this, 'get_landing_pages_dropdown'),
              MAILCHIMP_MAILMUNCH_POST_TYPE,
              'normal',
              'high'
          );
      }
  }
  
  public function get_landing_pages_dropdown($post) {
    $this->initiate_api();
    $landing_pages = $this->mailmunch_api->getLandingPages();
    $html = '<input type="hidden" name="mailmunch_landing_page_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
    $key = '_mailmunch_landing_page_id';
    $selected_landing_page_id = get_post_meta( $post->ID, $key, true );
    $selected_landing_page_id = intval($selected_landing_page_id);
    if (!$selected_landing_page_id) $selected_landing_page_id = $landing_pages[0]->id;
    $html .= '<p>Create or choose the landing page that you want to activate here.</p>';
    $html .= '<input id="mailmunch_landing_page_id" name="mailmunch_landing_page_id" type="hidden" value="'. $selected_landing_page_id .'" />';
    foreach ($landing_pages as $key => $landing_page) {
      $html .= '<div class="mailmunch-landing-page'. ($selected_landing_page_id == $landing_page->id ? ' active' : '') .'" data-landing-page-id="'. $landing_page->id .'">';
      $html .= '<div class="page-image">';
      $html .= '<img src="'. $landing_page->preview_url .'" />';
      $html .= '<div class="page-hover">';
      $html .= '<a href="'. MAILCHIMP_MAILMUNCH_URL. "/sso?token=". get_option(MAILCHIMP_MAILMUNCH_PREFIX."_user_token"). "&next_url=". urlencode(MAILCHIMP_MAILMUNCH_LANDING_PAGE_URL. "/sites/". get_option(MAILCHIMP_MAILMUNCH_PREFIX."_site_id")."/landing_pages/". $landing_page->id ."/edit") .'" target="_blank" class="mailmunch-edit-page button button-primary button-large">Edit Page</a>';
      $html .= '</div>';
      $html .= '</div>';
      $html .= '<div class="page-name">'. ($landing_page->name ? $landing_page->name : "Untitled") .'</div>';
      $html .= '</div>';
    }
    $html .= '<a href="'.MAILCHIMP_MAILMUNCH_URL. "/sso?token=". get_option(MAILCHIMP_MAILMUNCH_PREFIX."_user_token"). "&next_url=". urlencode(MAILCHIMP_MAILMUNCH_LANDING_PAGE_URL. "/sites/". get_option(MAILCHIMP_MAILMUNCH_PREFIX."_site_id")."/landing_pages/new") .'" class="mailmunch-landing-page new-landing-page" target="_blank">';
    $html .= '<div class="page-image">';
    $html .= '<span class="dashicons dashicons-plus-alt"></span>';
    $html .= '<div class="new-page-title">Create New Page</div>';
    $html .= '</div>';
    $html .= '<div class="page-name">&nbsp;</div>';
    $html .= '</a>';
    
    echo $html;
  }
  
  public function landing_page_save_meta($post_id, $post) {
    /*
     * We need to verify this came from our screen and with proper authorization,
     * because the save_post action can be triggered at other times.
     */

    if ( ! isset( $_POST['mailmunch_landing_page_noncename'] ) ) { // Check if our nonce is set.
      return;
    }

    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if( !wp_verify_nonce( $_POST['mailmunch_landing_page_noncename'], plugin_basename(__FILE__) ) ) {
      return $post->ID;
    }

    // is the user allowed to edit the post or page?
    if( ! current_user_can( 'edit_post', $post->ID )){
    	return $post->ID;
    }

    $key = '_mailmunch_landing_page_id';
    $value = $_POST['mailmunch_landing_page_id'];
    if( get_post_meta( $post->ID, $key, true ) ) { // if the custom field already has a value
      update_post_meta($post->ID, $key, $value);
    } else { // if the custom field doesn't have a value
      add_post_meta( $post->ID, $key, $value );
    }
    if( !$value ) { // delete if blank
      delete_post_meta( $post->ID, $key );
    }
  }
  
  public function add_pages_to_dropdown($pages, $r) {
    if (array_key_exists('name', $r) && 'page_on_front' == $r['name']) {
      $args = array('post_type' => MAILCHIMP_MAILMUNCH_POST_TYPE);
      $stacks = get_posts($args);
      $pages = array_merge($pages, $stacks);
    }

    return $pages;
  }
}
