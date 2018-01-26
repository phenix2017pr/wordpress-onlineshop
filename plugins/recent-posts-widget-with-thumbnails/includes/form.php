<h4><?php esc_html_e( 'Display Options', 'recent-posts-widget-with-thumbnails' ); ?>:</h4>

<p><label for="<?php echo $id_title; ?>"><?php $text = 'Title:'; esc_html_e( $text ); ?></label>
<input class="widefat" id="<?php echo $id_title; ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

<p><label for="<?php echo $id_number_posts; ?>"><?php $text = 'Number of posts to show:'; esc_html_e( $text ); ?></label>
<input id="<?php echo $id_number_posts; ?>" name="<?php echo $this->get_field_name( 'number_posts' ); ?>" type="text" value="<?php echo $number_posts; ?>" size="3" /></p>

<p><input class="checkbox" type="checkbox" <?php checked( $open_new_window ); ?> id="<?php echo $id_open_new_window; ?>" name="<?php echo $this->get_field_name( 'open_new_window' ); ?>" />
<label for="<?php echo $id_open_new_window; ?>"><?php esc_html_e( 'Open post links in new windows?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $random_order ); ?> id="<?php echo $id_random_order; ?>" name="<?php echo $this->get_field_name( 'random_order' ); ?>" />
<label for="<?php echo $id_random_order; ?>"><?php esc_html_e( 'Show posts in random order?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $hide_current_post ); ?> id="<?php echo $id_hide_current_post; ?>" name="<?php echo $this->get_field_name( 'hide_current_post' ); ?>" />
<label for="<?php echo $id_hide_current_post; ?>"><?php esc_html_e( 'Do not list the current post?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $keep_sticky ); ?> id="<?php echo $id_keep_sticky; ?>" name="<?php echo $this->get_field_name( 'keep_sticky' ); ?>" />
<label for="<?php echo $id_keep_sticky; ?>"><?php esc_html_e( 'Keep sticky posts on top of the list?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $hide_title ); ?> id="<?php echo $id_hide_title; ?>" name="<?php echo $this->get_field_name( 'hide_title' ); ?>" />
<label for="<?php echo $id_hide_title; ?>"><?php esc_html_e( 'Do not show post title?', 'recent-posts-widget-with-thumbnails' ); ?><br />
<em><?php esc_html_e( 'Make sure you set a default thumbnail for posts without a thumbnail, otherwise there will be no link.', 'recent-posts-widget-with-thumbnails' ); ?></em></label></p>

<p><label for="<?php echo $id_post_title_length; ?>"><?php esc_html_e( 'Maximum length of post title', 'recent-posts-widget-with-thumbnails' ); ?>:</label>
<input id="<?php echo $id_post_title_length; ?>" name="<?php echo $this->get_field_name( 'post_title_length' ); ?>" type="text" value="<?php echo $post_title_length; ?>" size="3" /></p>

<p><input class="checkbox" type="checkbox" <?php checked( $show_author ); ?> id="<?php echo $id_show_author; ?>" name="<?php echo $this->get_field_name( 'show_author' ); ?>" />
<label for="<?php echo $id_show_author; ?>"><?php esc_html_e( 'Show post author?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $show_categories ); ?> id="<?php echo $id_show_categories; ?>" name="<?php echo $this->get_field_name( 'show_categories' ); ?>" />
<label for="<?php echo $id_show_categories; ?>"><?php esc_html_e( 'Show post categories?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $id_show_date; ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
<label for="<?php echo $id_show_date; ?>"><?php esc_html_e( 'Show post date?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $show_excerpt ); ?> id="<?php echo $id_show_excerpt; ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" />
<label for="<?php echo $id_show_excerpt; ?>"><?php esc_html_e( 'Show excerpt?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><label for="<?php echo $id_excerpt_length; ?>"><?php esc_html_e( 'Maximum length of excerpt', 'recent-posts-widget-with-thumbnails' ); ?>:</label>
<input id="<?php echo $id_excerpt_length; ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" type="text" value="<?php echo $excerpt_length; ?>" size="3" /></p>

<p><label for="<?php echo $id_excerpt_more; ?>"><?php esc_html_e( 'Signs after excerpt', 'recent-posts-widget-with-thumbnails' ); ?>:</label>
<input id="<?php echo $id_excerpt_more; ?>" name="<?php echo $this->get_field_name( 'excerpt_more' ); ?>" type="text" value="<?php echo esc_attr( $excerpt_more ); ?>" size="3" /></p>

<p><input class="checkbox" type="checkbox" <?php checked( $set_more_as_link ); ?> id="<?php echo $id_set_more_as_link; ?>" name="<?php echo $this->get_field_name( 'set_more_as_link' ); ?>" />
<label for="<?php echo $id_set_more_as_link; ?>"><?php esc_html_e( 'Set signs after excerpt as a link to the post?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $ignore_excerpt ); ?> id="<?php echo $id_ignore_excerpt; ?>" name="<?php echo $this->get_field_name( 'ignore_excerpt' ); ?>" />
<label for="<?php echo $id_ignore_excerpt; ?>"><?php esc_html_e( 'Ignore post excerpt field as excerpt source?', 'recent-posts-widget-with-thumbnails' ); ?></label><br />
<em><?php esc_html_e( 'Normally the widget takes the excerpt from the text of the excerpt field unchanged and if there is no text it creates the excerpt from the post content automatically. If this option is activated the excerpt is created from the post content only.', 'recent-posts-widget-with-thumbnails' );?></em></p>

<p><input class="checkbox" type="checkbox" <?php checked( $show_comments_number ); ?> id="<?php echo $id_show_comments_number; ?>" name="<?php echo $this->get_field_name( 'show_comments_number' ); ?>" />
<label for="<?php echo $id_show_comments_number; ?>"><?php esc_html_e( 'Show number of comments?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $print_post_categories ); ?> id="<?php echo $id_print_post_categories; ?>" name="<?php echo $this->get_field_name( 'print_post_categories' ); ?>" />
<label for="<?php echo $id_print_post_categories; ?>"><?php esc_html_e( 'Print slugs of post categories in class attribute of LI elements?', 'recent-posts-widget-with-thumbnails' ); ?></label><br />
<em><?php esc_html_e( 'For developers: If checked you can set CSS rules for each list item on base of the assigned categories.', 'recent-posts-widget-with-thumbnails' ); ?></em></p>

<p><label for="<?php echo $id_category_ids;?>"><?php esc_html_e( 'Show posts of selected categories only?', 'recent-posts-widget-with-thumbnails' ); ?></label><br />
<?php echo $selection_element; ?><br />
<em><?php printf( esc_html__( 'Click on the categories with pressed CTRL key to select multiple categories. If &#8220;%s&#8221; was selected then other selections will be ignored.', 'recent-posts-widget-with-thumbnails' ), $label_all_cats ); ?></em></p>

<h4><?php esc_html_e( 'Thumbnail Options', 'recent-posts-widget-with-thumbnails' ); ?>:</h4>

<p><input class="checkbox" type="checkbox" <?php checked( $show_thumb ); ?> id="<?php echo $id_show_thumb; ?>" name="<?php echo $this->get_field_name( 'show_thumb' ); ?>" />
<label for="<?php echo $id_show_thumb; ?>"><?php esc_html_e( 'Display post featured image?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><label for="<?php echo $id_thumb_dimensions; ?>"><?php esc_html_e( 'Size of thumbnail', 'recent-posts-widget-with-thumbnails' ); ?>:</label>
	<select id="<?php echo $id_thumb_dimensions; ?>" name="<?php echo $this->get_field_name( 'thumb_dimensions' ); ?>">
		<option value="<?php echo $this->default_thumb_dimensions; ?>" <?php selected( $thumb_dimensions, $this->default_thumb_dimensions ); ?>><?php esc_html_e( 'Specified width and height', 'recent-posts-widget-with-thumbnails' ); ?></option>
<?php
// Display the sizes in the array
foreach ( $size_options as $option ) {
?>
		<option value="<?php echo esc_attr( $option[ 'size_name' ] ); ?>"<?php selected( $thumb_dimensions, $option[ 'size_name' ] ); ?>><?php echo esc_html( $option[ 'name' ] ); ?> (<?php echo absint( $option[ 'width' ] ); ?> &times; <?php echo absint( $option[ 'height' ] ); ?>)</option>
<?php
} // end foreach(option)
?>
	</select><br />
	<em><?php printf( esc_html__( 'If you use a specified size the following sizes will be taken, otherwise they will be ignored and the selected dimension as stored in %s will be used:', 'recent-posts-widget-with-thumbnails' ), $media_trail ); ?></em>
</p>

<p><label for="<?php echo $id_thumb_width; ?>"><?php esc_html_e( 'Width of thumbnail', 'recent-posts-widget-with-thumbnails' ); ?>:</label>
<input id="<?php echo $id_thumb_width; ?>" name="<?php echo $this->get_field_name( 'thumb_width' ); ?>" type="text" value="<?php echo $thumb_width; ?>" size="3" /></p>

<p><label for="<?php echo $id_thumb_height; ?>"><?php esc_html_e( 'Height of thumbnail', 'recent-posts-widget-with-thumbnails' ); ?>:</label>
<input id="<?php echo $id_thumb_height; ?>" name="<?php echo $this->get_field_name( 'thumb_height' ); ?>" type="text" value="<?php echo $thumb_height; ?>" size="3" /></p>

<p><input class="checkbox" type="checkbox" <?php checked( $keep_aspect_ratio ); ?> id="<?php echo $id_keep_aspect_ratio; ?>" name="<?php echo $this->get_field_name( 'keep_aspect_ratio' ); ?>" />
<label for="<?php echo $id_keep_aspect_ratio; ?>"><?php esc_html_e( 'Use aspect ratios of original images?', 'recent-posts-widget-with-thumbnails' ); ?><br />
<em><?php esc_html_e( 'If checked the given width is used to determine the height of the thumbnail automatically. This option also supports responsive web design.', 'recent-posts-widget-with-thumbnails' ); ?></em></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $try_1st_img ); ?> id="<?php echo $id_try_1st_img; ?>" name="<?php echo $this->get_field_name( 'try_1st_img' ); ?>" />
<label for="<?php echo $id_try_1st_img; ?>"><?php esc_html_e( "Try to use the post's first image if post has no featured image?", 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $only_1st_img ); ?> id="<?php echo $id_only_1st_img; ?>" name="<?php echo $this->get_field_name( 'only_1st_img' ); ?>" />
<label for="<?php echo $id_only_1st_img; ?>"><?php esc_html_e( 'Use first image only, ignore featured image?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><input class="checkbox" type="checkbox" <?php checked( $use_default ); ?> id="<?php echo $id_use_default; ?>" name="<?php echo $this->get_field_name( 'use_default' ); ?>" />
<label for="<?php echo $id_use_default; ?>"><?php esc_html_e( 'Use default thumbnail if no image could be determined?', 'recent-posts-widget-with-thumbnails' ); ?></label></p>

<p><label for="<?php echo $id_default_url; ?>"><?php esc_html_e( 'URL of default thumbnail (start with http://)', 'recent-posts-widget-with-thumbnails' ); ?>:</label>
<input class="widefat" id="<?php echo $id_default_url; ?>" name="<?php echo $this->get_field_name( 'default_url' ); ?>" type="text" value="<?php echo esc_url( $default_url ); ?>" /></p>

<p><?php esc_html_e( 'Do you like the plugin?', 'recent-posts-widget-with-thumbnails' ); ?> <a href="https://wordpress.org/support/plugin/recent-posts-widget-with-thumbnails/reviews/" target="_blank"><?php esc_html_e( 'Please rate it at wordpress.org!', 'recent-posts-widget-with-thumbnails' ); ?></a></p>
<p><?php esc_html_e( 'Do you need more options?', 'recent-posts-widget-with-thumbnails' ); ?> <a href="http://shop.stehle-internet.de/downloads/ultimate-post-list-pro/" target="_blank"><?php esc_html_e( 'Get Ultimate Post List Pro!', 'recent-posts-widget-with-thumbnails' ); ?></a></p>
