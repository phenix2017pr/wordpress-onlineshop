<?php

/**
 * The file that defines the rewrite rules for MailMunch lanidng pages
 *
 * @link       http://www.mailmunch.co
 * @since      3.0.0
 *
 */

class Mailchimp_Mailmunch_Rewrite {
  
  public function __construct() {
    
  }
  
  public function init() {
    $this->generateRewriteRules();
  }
  
  public function on_save_post($postId) {
    if (get_post_type($postId) == MAILCHIMP_MAILMUNCH_POST_TYPE) {
      $this->regenerateRewriteRules();
    }
  }

  public function on_wp_insert_post($postId) {
    if (get_post_type($postId) == MAILCHIMP_MAILMUNCH_POST_TYPE) {
      $this->regenerateRewriteRules();
    }    
  }
  
  public function on_post_type_link($permalink) {
    $customPostTypes = get_post_types(array('_builtin' => false), 'objects');
    foreach ($customPostTypes as $type => $postType) {
      if ($type == MAILCHIMP_MAILMUNCH_POST_TYPE) {
        $slug = trim($postType->rewrite['slug'], '/');
        $permalink = str_replace(get_bloginfo('url') . '/' . $slug . '/', get_bloginfo('url') . '/', $permalink);
      }
    }
    return $permalink;
    
  }
  
  public function on_wp_unique_post_slug($slug, $post_ID, $post_status, $post_type, $post_parent) {
    global $wpdb, $wp_rewrite;

    // Don't touch hierarchical post types
    $hierarchical_post_types = get_post_types(array('hierarchical' => true));
    if (in_array( $post_type, $hierarchical_post_types)) return $slug;

    if ('attachment' == $post_type) {
      // These will be unique anyway
      return $slug;
    }

    $feeds = $wp_rewrite->feeds;
    if (!is_array($feeds)) $feeds = array();

    // Lets make sure the slug is really unique:
    $check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1";
    $post_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $slug, $post_ID));

    if ($post_name_check || in_array($slug, $feeds)) {
      $suffix = 2;

      do {
        $alt_post_name = substr($slug, 0, 200 - (strlen($suffix) + 1)) . "-$suffix";
        $post_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $alt_post_name, $post_ID));
        $suffix++;
      } while ($post_name_check);

      $slug = $alt_post_name;
    }

    return $slug;
  }
  
  public function enable_front_page_landing_pages($query) {
    $postType = '';
    if (array_key_exists('post_type', $query->query_vars)) {
      $postType = $query->query_vars['post_type'];
    }
    
    if ('' == $postType && 0 != $query->query_vars['page_id']) {
      $query->query_vars['post_type'] = array('page', MAILCHIMP_MAILMUNCH_POST_TYPE);
    }
  }
  
  /**
  * Regenerate rewrite rules for each custom page
  *
  * @return void
  */
  public function regenerateRewriteRules() {
    $this->generateRewriteRules();
    flush_rewrite_rules(false);
  }

  /**
  * Generate rewrite rules for each custom page
  *
  * @return void
  */
  public function generateRewriteRules() {
    global $wpdb;
    $customPostTypes = get_post_types(array('_builtin' => false), 'objects');

    foreach ($customPostTypes as $type => $postType) {
      if ($type == MAILCHIMP_MAILMUNCH_POST_TYPE) {
        $posts = $wpdb->get_results($wpdb->prepare("SELECT id, post_name FROM ". $wpdb->posts. " WHERE post_name != '' AND post_type = %s", $type), OBJECT);
        foreach ($posts as $post) {
          add_rewrite_rule($post->post_name . '$', 'index.php?' . $postType->query_var . '=' . $post->post_name, 'top');
        }
      }
    }
  }
}
