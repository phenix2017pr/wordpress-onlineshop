<?php
/*
Plugin Name: Logo Slider
Plugin URI: http://www.wordpress.org/extend/plugins/logo-slider
Description:  Add a logo slideshow carousel to your site quicky and easily.
Version: 1.4.7
Author: EnigmaWeb
Author URI: https://profiles.wordpress.org/enigmaweb/
Text Domain: logo-slider
Domain Path: /languages
*/

/*
///////////////////////////////////////////////
This section defines the variables that
will be used throughout the plugin
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
error_reporting(0);

// define our defaults (filterable)
$wp_logo_defaults = apply_filters('wp_logo_defaults', array(
    'custom_css' => 'You can write your custom CSS here.',
    'arrow' => 1,
    'bgcolour' => '#FFFFFF',
    'slider_width' => 450,
    'slider_height' => 198,
    'num_img' => 2,
    'auto_slide' => 1,
    'auto_slide_time' => '',
));

// pull the settings from the db
$wp_logo_slider_settings = get_option('wp_logo_slider_settings');
$wp_logo_slider_images = get_option('wp_logo_slider_images');

// fallback
$wp_logo_slider_settings = wp_parse_args($wp_logo_slider_settings, $wp_logo_defaults);


/*
///////////////////////////////////////////////
This section hooks the proper functions
to the proper actions in WordPress
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/

// this function registers our settings in the db
add_action('admin_init', 'wp_logo_register_settings');
function wp_logo_register_settings() {
    register_setting('wp_logo_slider_images', 'wp_logo_slider_images', 'wp_logo_images_validate');
    register_setting('wp_logo_slider_settings', 'wp_logo_slider_settings', 'wp_logo_settings_validate');
}
// this function adds the settings page to the Appearance tab
add_action('admin_menu', 'wp_logo_slider_menu');
function wp_logo_slider_menu() {
    $page_title		=	'Logo Slider';
    $menu_title		=	'Logo Slider';
    $capability		=	'manage_options';
    $menu_slug		=	'wp_logo_slider';
    $function		=	'wp_logo_slider';
    $icon			=	plugin_dir_url( __FILE__ ).'icon.png';
    add_menu_page($page_title,$menu_title,$capability,$menu_slug,$function,$icon);
}

// add "Settings" link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__) , 'wp_logo_plugin_action_links');
function wp_logo_plugin_action_links($links) {
    $wp_logo_settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'upload.php?page=wp_logo_slider' ), __('Settings') );
    array_unshift($links, $wp_logo_settings_link);
    return $links;
}

/*
///////////////////////////////////////////////
this function is the code that gets loaded when the
settings page gets loaded by the browser.  It calls 
functions that handle image uploads and image settings
changes, as well as producing the visible page output.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
function wp_logo_slider() {
    echo '<div class="wrap">';
            //	handle image upload, if necessary
            if($_REQUEST['action'] == 'wp_handle_upload')
                    wp_logo_handle_upload();

            //	delete an image, if necessary
            if(isset($_REQUEST['delete']))
            wp_logo_delete_upload($_REQUEST['delete']);

            //	the image management form
            wp_logo_images_admin();

            //	the settings management form
            wp_logo_settings_admin();
    echo '</div>';
}

/*
///////////////////////////////////////////////
    this section handles uploading images, adding
    the image data to the database, deleting images,
    and deleting image data from the database.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	this function handles the file upload,
//	resize/crop, and adds the image data to the db
function wp_logo_handle_upload() {
    global $wp_logo_slider_settings, $wp_logo_slider_images;

    // upload the image
    $upload = wp_handle_upload($_FILES['logo_images'], 0);

    // extract the $upload array
    extract($upload);

    // the URL of the directory the file was loaded in
    $upload_dir_url = str_replace(basename($file), '', $url);

    // get the image dimensions
    list($width, $height) = getimagesize($file);

    // if the uploaded file is NOT an image
    if(strpos($type, 'image') === FALSE) {
        unlink($file); // delete the file
        echo '<div class="error" id="message"><p>'.__('Sorry, but the file you uploaded does not seem to be a valid image. Please try again.','logo-slider').'</p></div>';
        return;
    }

    // if the image is larger than the width/height requirements, then scale it down.
    if($width > $wp_logo_slider_settings['slider_width'] || $height > $wp_logo_slider_settings['slider_height']) {
        // resize the image
        $width = $wp_logo_slider_settings['slider_width'];
        $height = $wp_logo_slider_settings['slider_height'];
        $image = wp_get_image_editor($file);
        if(is_wp_error($image)){
            return $image;
        }
        $resized = $image->resize($width, $height, TRUE);
        $destFile = $image->generate_filename(NULL, NULL);
        $saved = $image->save($destFile);

        if(is_wp_error($saved)){
            return $saved;
        }
        $newImgPath = $destFile;
    }

    // make the thumbnail
    $thumb_height = round((100 * $wp_logo_slider_settings['slider_height']) / $wp_logo_slider_settings['slider_width']);
    if(isset($upload['file'])) {
        $thumbnail = image_resize($file, 100, $thumb_height, true, 'thumb');
        $thumbnail_url = $upload_dir_url . basename($thumbnail);
    }

    $row = 1; 
    foreach((array)$wp_logo_slider_images as $image => $data) : 
        $row++;
    endforeach;
    
    // use the timestamp as the array key and id
    $time = date('YmdHis');

    //	add the image data to the array
    $wp_logo_slider_images[$time] = array(
        'id' => $time,
        'file' => $file,
        'file_url' => $url,
        'thumbnail' => $thumbnail,
        'thumbnail_url' => $thumbnail_url,
        'slide_title' => '',
        'slide_desc' => '',
        'image_links_to' => ''
    );

    //	add the image information to the database
    $wp_logo_slider_images['update'] = 'Added';
    update_option('wp_logo_slider_images', $wp_logo_slider_images);
}

// this function deletes the image,
// and removes the image data from the db
function wp_logo_delete_upload($id) {
    global $wp_logo_slider_images;

    // if the ID passed to this function is invalid,
    // halt the process, and don't try to delete.
    if(!isset($wp_logo_slider_images[$id])) return;

    // delete the image and thumbnail
    unlink($wp_logo_slider_images[$id]['file']);
    unlink($wp_logo_slider_images[$id]['thumbnail']);

    // indicate that the image was deleted
    $wp_logo_slider_images['update'] = 'Deleted';

    // remove the image data from the db
    unset($wp_logo_slider_images[$id]);
    update_option('wp_logo_slider_images', $wp_logo_slider_images);
}

/*
///////////////////////////////////////////////
    these two functions check to see if an update
    to the data just occurred. if it did, then they
    will display a notice, and reset the update option.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
// this function checks to see if we just updated the settings
// if so, it displays the "updated" message.
function wp_logo_slider_settings_update_check() {
    global $wp_logo_slider_settings;
    if(isset($wp_logo_slider_settings['update'])) {
        echo '<div class="updated fade" id="message">
                <p>'.__('Wordpress Logo Slider Settings <strong>'.$wp_logo_slider_settings['update'],'logo-slider').'</strong>
                </p>
              </div>';
	
        unset($wp_logo_slider_settings['update']);
	update_option('wp_logo_slider_settings', $wp_logo_slider_settings);
    }
}

// this function checks to see if we just added a new image
// if so, it displays the "updated" message.
function wp_logo_slider_images_update_check() {
    global $wp_logo_slider_images;
    if($wp_logo_slider_images['update'] == 'Added' || $wp_logo_slider_images['update'] == 'Deleted' || $wp_logo_slider_images['update'] == 'Updated') {
	echo '<div class="updated fade" id="message"><p>'.__('Image(s) '.$wp_logo_slider_images['update'].' Successfully','logo-slider').'</p></div>';
	unset($wp_logo_slider_images['update']);
	update_option('wp_logo_slider_images', $wp_logo_slider_images);
    }
}

/*
///////////////////////////////////////////////
these two functions display the front-end code
on the admin page. it's mostly form markup.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
// display the images administration code
function wp_logo_images_admin() { ?>
    <?php global $wp_logo_slider_images; ?>
    <?php wp_logo_slider_images_update_check(); ?>
    <h2><?php _e('Wordpress LogoSlider Images','logo-slider'); ?></h2>
	
    <table class="form-table">
	<tr valign="top"><th scope="row"><?php _e('Upload New Image','logo-slider') ?></th>
            <td>
                <form enctype="multipart/form-data" method="post" action="?page=wp_logo_slider">
                    <input type="hidden" name="post_id" id="post_id" value="0" />
                    <input type="hidden" name="action" id="action" value="wp_handle_upload" />
                    
                    <label for="logo_images"><?php _e('Select a File: ','logo-slider') ?></label>
                    <input type="file" name="logo_images" id="logo_images" />
                    <input type="submit" class="button-primary" name="html-upload" value="Upload" />
		</form>
            </td>
	</tr>
    </table>
    <br />
	
    <p style="border:2px solid #999; border-radius: 10px; font-size: 12px; padding: 6px 10px; width: 24%;">
        <strong><?php _e('Note:', 'hmpf') ?> </strong><?php _e('Drag &amp; Drop is auto save.','hmpf') ?>
    </p>
        
    <?php
        if(!empty($wp_logo_slider_images)) :
    ?>
        <table class="widefat fixed" cellspacing="0" id="image_sort" style="width:100%; table-layout:inherit;">
            <thead>
                <tr>
                    <th scope="col" class="column-slug"><?php _e('Image','logo-slider') ?></th>
                    <th scope="col"><?php _e('Image Links To','logo-slider') ?></th>
                    <th scope="col" class="column-slug"><?php _e('Actions','logo-slider') ?></th>
		</tr>
            </thead>
            <tfoot>
                <tr>
                    <th scope="col" class="column-slug"><?php _e('Image','logo-slider') ?></th>
                    <th scope="col"><?php _e('Image Links To','logo-slider') ?></th>
                    <th scope="col" class="column-slug"><?php _e('Actions','logo-slider') ?></th>
		</tr>
            </tfoot>
            <tbody>
		<form method="post" action="options.php">
                    <?php settings_fields('wp_logo_slider_images'); ?>
                    <?php foreach((array)$wp_logo_slider_images as $image => $data) : ?>
                    <tr id="list_item_<?php echo $image ?>" class="list_item">
			<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][id]" value="<?php echo $data['id']; ?>" />
			<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][file]" value="<?php echo $data['file']; ?>" />
			<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][file_url]" value="<?php echo $data['file_url']; ?>" />
			<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][thumbnail]" value="<?php //echo $data['thumbnail']; ?>" />
			<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][thumbnail_url]" value="<?php echo $data['thumbnail_url']; ?>" />
                        <th scope="row" class="column-slug"><img src="<?php echo $data['thumbnail_url']; ?>" /></th>
                        <td>
                            <?php //echo $image; ?>
                            <input type="text" name="wp_logo_slider_images[<?php echo $image; ?>][image_links_to]" value="<?php echo $data['image_links_to']; ?>" size="30" />
                        </td>
                        <td class="column-slug">
                            <input type="submit" class="button-primary" value="Update" />
                            <a href="?page=wp_logo_slider&amp;delete=<?php echo $image; ?>" class="button">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <input type="hidden" name="wp_logo_slider_images[update]" value="Updated" />
		</form>
            </tbody>
	</table>
<?php
        endif;
}

/*
///////////////////////////////////////////////
    SORTABLE FUNCTION.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/

function image_sort(){  
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-sortable');
?>
    <script type="text/javascript">
        jQuery(document).ready( function(e) {
            jQuery('#image_sort').sortable({
                items: '.list_item',
                opacity: 0.5,
                cursor: 'pointer',
                axis: 'y',
                update: function() {
                    var ordr = jQuery(this).sortable('serialize') + '&action=list_update_order';
                    jQuery.post(ajaxurl, ordr, function(response){
                            //alert(response);
                    });
                }
            });
        });
    </script>
<?php
}
add_action('admin_head','image_sort');
	
function order_list(){
    global $wp_logo_slider_images;

    $list = $wp_logo_slider_images;
    $new_order = $_POST['list_item'];
    $new_list = array();

    foreach($new_order as $v){
        if(isset($list[$v])){
            $new_list[$v] = $list[$v];
        }
    }
    update_option('wp_logo_slider_images',$new_list);
}
add_action('wp_ajax_list_update_order','order_list');

/*============================================================================================*/

// display the settings administration code
function wp_logo_settings_admin() {
    wp_logo_slider_settings_update_check();
?>
    <h2><?php _e('Wordpress Logo Slider Settings','logo-slider'); ?></h2>
    <form method="post" action="options.php">
    <?php
        settings_fields('wp_logo_slider_settings');
        global $wp_logo_slider_settings; $options = $wp_logo_slider_settings;
    ?>
	<table class="form-table">
            <tr>
                <th scope="row"><?php _e('Size','logo-slider') ?></th>
		<td>
                    <?php _e('Width: ','logo-slider') ?>
                    <input type="text" name="wp_logo_slider_settings[slider_width]" value="<?php echo $options['slider_width'] ?>" size="4" /> 
                    <?php _e('Height: ','logo-slider') ?>
                    <input type="text" name="wp_logo_slider_settings[slider_height]" value="<?php echo $options['slider_height'] ?>" size="4" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Images Per Slide','logo-slider') ?></th>
		<td>
                    <select name="wp_logo_slider_settings[num_img]">
                        <option value="1" <?php echo ($options['num_img'] == '1' ? 'selected="selected"' : '') ?>>
                            <?php _e('1','logo-slider') ?>
                        </option>
                        <option value="2" <?php echo ($options['num_img'] == '2' ? 'selected="selected"' : '') ?>>
                            <?php _e('2','logo-slider') ?>
                        </option>
                        <option value="3" <?php echo ($options['num_img'] == '3' ? 'selected="selected"' : '') ?>>
                            <?php _e('3','logo-slider') ?>
                        </option>
                        <option value="4" <?php echo ($options['num_img'] == '4' ? 'selected="selected"' : '') ?>>
                            <?php _e('4','logo-slider') ?>
                        </option>
                        <option value="5" <?php echo ($options['num_img'] == '5' ? 'selected="selected"' : '') ?>>
                            <?php _e('5','logo-slider') ?>
                        </option>
                        <option value="6" <?php echo ($options['num_img'] == '6' ? 'selected="selected"' : '') ?>>
                            <?php _e('6','logo-slider') ?>
                        </option>
                        <option value="7" <?php echo ($options['num_img'] == '7' ? 'selected="selected"' : '') ?>>
                            <?php _e('7','logo-slider') ?>
                        </option>
                        <option value="8" <?php echo ($options['num_img'] == '8' ? 'selected="selected"' : '') ?>>
                            <?php _e('8','logo-slider') ?>
                        </option>
                    </select>
                    <small><?php _e('Number of logos per slide','logo-slider') ?></small>
                </td>
            </tr>        
            <tr>
                <th scope="row"><?php _e('Background Colour','logo-slider') ?></th>
		<td>
                    <input type="text" name="wp_logo_slider_settings[bgcolour]" value="<?php echo $options['bgcolour'] ?>" />
                    <small><?php _e('Format: ','logo-slider') ?>#FFFFFF</small>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Open logo links in New Window','logo-slider') ?></th>
		<td>
                    <input type="checkbox" name="wp_logo_slider_settings[new_window]" <?php echo ($options['new_window'] == 'on' ? 'checked="checked"' : '' ) ?> />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Select Slider','logo-slider') ?></th>
		<td>
                    <select name="wp_logo_slider_settings[select_slider]">
                        <option value="slide" <?php echo ($options['select_slider'] == 'slide' ? 'selected="selected"' : '' ) ?>>
                            <?php _e('Slide','logo-slider') ?>
                        </option>
                        <option value="fade" <?php echo ($options['select_slider'] == 'fade' ? 'selected="selected"' : '' ) ?>>
                            <?php _e('Fade','logo-slider') ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Auto Slide','logo-slider') ?></th>
		<td id="arrow-style"> 
                    <?php _e('ON','logo-slider') ?>
                    <input type="radio" name="wp_logo_slider_settings[auto_slide]" value="1" <?php if($options['auto_slide']==1){echo 'checked="checked"';}?> />&nbsp; &nbsp;
                    <?php _e('OFF','logo-slider') ?>
                    <input type="radio" name="wp_logo_slider_settings[auto_slide]" value="2" <?php if($options['auto_slide']==2){echo 'checked="checked"';}?>/>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Auto Slide Time','logo-slider') ?></th>
		<td>
                    <input type="text" name="wp_logo_slider_settings[auto_slide_time]" value="<?php echo $options['auto_slide_time'] ?>" size="4" /> 
                    <small><?php _e('Set auto slide duration in seconds','logo-slider') ?></small>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Arrow Style','logo-slider') ?></th>
		<td id="arrow-style"> 
                    <p>
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/off.png" width="28" height="40" alt="" />
                        <br />
                        <input type="radio" name="wp_logo_slider_settings[arrow]" value="0" <?php if($options['arrow']==0){echo 'checked="checked"';}?> />
                    </p>
                    <p>
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow1.png" width="28" height="40" alt="" />
                        <br />
                        <input type="radio" name="wp_logo_slider_settings[arrow]" value="1" <?php if($options['arrow']==1){echo 'checked="checked"';}?> />
                    </p>
                    <p>
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow2.png" width="31" height="40" alt="" />
                        <br />
                        <input type="radio" name="wp_logo_slider_settings[arrow]" value="2" <?php if($options['arrow']==2){echo 'checked="checked"';}?>/>
                    </p>
                    <p>
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow3.png" width="34" height="40" alt="" />
                        <br />
                        <input type="radio" name="wp_logo_slider_settings[arrow]" value="3" <?php if($options['arrow']==3){echo 'checked="checked"';}?>/>
                    </p>
                    <p>
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow4.png" width="34" height="40" alt="" />
                        <br />
                        <input type="radio" name="wp_logo_slider_settings[arrow]" value="4" <?php if($options['arrow']==4){echo 'checked="checked"';}?>/>
                    </p>
                    <p>
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow5.png" width="24" height="40" alt="" />
                        <br />
                        <input type="radio" name="wp_logo_slider_settings[arrow]" value="5" <?php if($options['arrow']==5){echo 'checked="checked"';}?>/>
                    </p>
                    <p>
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow6.png" width="36" height="40" alt="" />
                        <br />
                        <input type="radio" name="wp_logo_slider_settings[arrow]" value="6" <?php if($options['arrow']==6){echo 'checked="checked"';}?>/>
                    </p>
                    <p>
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow7.png" width="38" height="40" alt="" />
                        <br />
                        <input type="radio" name="wp_logo_slider_settings[arrow]" value="7" <?php if($options['arrow']==7){echo 'checked="checked"';}?>/>
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Custom CSS','logo-slider') ?></th>
		<td>
                    <textarea name="wp_logo_slider_settings[custom_css]" rows="6" cols="70"><?php echo $options['custom_css']; ?></textarea>
                </td>
            </tr>
                <input type="hidden" name="wp_logo_slider_settings[update]" value="<?php _e('UPDATED','logo-slider'); ?>" />
	</table>
        
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Settings','logo-slider') ?>" />
        </p>
    </form>
    
    <!-- The Reset Option -->
    <form method="post" action="options.php">
    <?php
        settings_fields('wp_logo_slider_settings');
        global $wp_logo_defaults; // use the defaults
    
        foreach((array)$wp_logo_defaults as $key => $value) : ?>
            <input type="hidden" name="wp_logo_slider_settings[<?php echo $key; ?>]" value="<?php echo $value; ?>" />
    <?php
        endforeach;
    ?>
        <input type="hidden" name="wp_logo_slider_settings[update]" value="<?php _e('RESET','logo-slider'); ?>" />
        <input type="submit" class="button" value="<?php _e('Reset Settings','logo-slider') ?>" />
    </form>
    <!-- End Reset Option -->

<?php
}

/*
///////////////////////////////////////////////
    these two functions sanitize the data before it
    gets stored in the database via options.php
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
// this function sanitizes our settings data for storage
function wp_logo_settings_validate($input) {
    $input['slider_width'] = intval($input['slider_width']);
    $input['slider_height'] = intval($input['slider_height']);
    $input['num_img'] = intval($input['num_img']);
    $input['arrow'] = intval($input['arrow']);
    $input['custom_css'] = wp_filter_nohtml_kses($input['custom_css']);
    $input['bgcolour'] = wp_filter_nohtml_kses($input['bgcolour']);
    $input['auto_slide'] = intval($input['auto_slide']);
    $input['auto_slide_time'] = intval($input['auto_slide_time']);

    return $input;
}

// this function sanitizes our image data for storage
function wp_logo_images_validate($input) {
    foreach((array)$input as $key => $value) {
        if($key != 'update') {
            $input[$key]['file_url'] = clean_url($value['file_url']);
            $input[$key]['thumbnail_url'] = clean_url($value['thumbnail_url']);

            if($value['image_links_to'])
                $input[$key]['image_links_to'] = clean_url($value['image_links_to']);
        }
    }
    return $input;
}

/*
///////////////////////////////////////////////
    this final section generates all the code that
    is displayed on the front-end of the WP Theme
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
function logo_slider($args = array(), $content = null) {
    global $wp_logo_slider_settings, $wp_logo_slider_images;
    
    // possible future use
    $args = wp_parse_args($args, $wp_logo_slider_settings);
    $newline = "\n"; // line break
    
    echo '<div id="logo-slider-wraper">';
            $check = $wp_logo_slider_settings['new_window'];
            $new_window = '';

            if(isset($check)) {
                $new_window = 'target="_blank"';
            } else {
                $new_window = 'target="_parent"';
            }

            $img_num1 = 1;
            $img_num2 = 2;
            $img_num3 = 3;
            $img_num4 = 4;

            $num_img = $wp_logo_slider_settings['num_img'];

            // Get web and other device engins for Responsive
            $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
            $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
            $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
            $Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");
            $webOS   = stripos($_SERVER['HTTP_USER_AGENT'],"webOS");
            $mobile = stripos($_SERVER['HTTP_USER_AGENT'],"mobile");
            $BlackBerry = stripos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
            $RimTablet= stripos($_SERVER['HTTP_USER_AGENT'],"RIM Tablet");

            $msie = strpos($_SERVER["HTTP_USER_AGENT"], 'MSIE');
            $firefox = strpos($_SERVER["HTTP_USER_AGENT"], 'Firefox');
            $safari = strpos($_SERVER["HTTP_USER_AGENT"], 'Safari');
            $chrome = strpos($_SERVER["HTTP_USER_AGENT"], 'Chrome');
            $Opera = strpos($_SERVER["HTTP_USER_AGENT"], 'OPR');
            $IE11 = strpos($_SERVER["HTTP_USER_AGENT"], 'rv:11.0');

            if( $iPod || $iPhone ){
                $data_chunks = array_chunk($wp_logo_slider_images, $img_num1);
            }else if($iPad){
                $data_chunks = array_chunk($wp_logo_slider_images, $img_num4);
            }else if($Android){
                $data_chunks = array_chunk($wp_logo_slider_images, $img_num1);
            }else if($webOS){
                $data_chunks = array_chunk($wp_logo_slider_images, $img_num1);
            }else if($mobile){
                $data_chunks = array_chunk($wp_logo_slider_images, $img_num1);
            }else if($BlackBerry){
                $data_chunks = array_chunk($wp_logo_slider_images, $img_num1);
            }else if($RimTablet){
                $data_chunks = array_chunk($wp_logo_slider_images, $img_num4);
            }else if(($msie) || ($firefox) || ($safari) || ($chrome) || ($IE11)){
                $data_chunks = array_chunk($wp_logo_slider_images, $num_img);
            }

            $slid = $wp_logo_slider_settings['select_slider'];
            $lgs_slide_effect = '';

            if($slid == 'slide') {
                $lgs_slide_effect = 'scrollHorz';
            } elseif($slid == 'fade') {
                $lgs_slide_effect = 'fade';
            } else {
                $lgs_slide_effect = 'scrollHorz';
            }

            $lgs_auto_slide = $wp_logo_slider_settings['auto_slide'];
            $lgs_slide_time = $wp_logo_slider_settings['auto_slide_time'];

            // Logo Image Slider
        ?>
            <ul id="logo-slider" class="cycle-slideshow"
                data-cycle-fx="<?php echo $lgs_slide_effect ?>"
                data-cycle-timeout="<?php echo (($lgs_auto_slide == 1 ) ? $lgs_slide_time * 1000 : 0) ?>"
                data-cycle-next="#prev"
                data-cycle-prev="#next"
                data-cycle-speed="600"
                data-cycle-slides="> li"
            >
        <?php
            foreach ($data_chunks as $data_chunk) {
                echo '<li class="slide">';
                        foreach($data_chunk as $data) {
                            if($data['image_links_to'])
                                echo '<a href="'.$data['image_links_to'].'" '.$new_window.'>';
                                    echo '<img src="'.$data['file_url'].'" class="logo-img" alt="" />';
                                if($data['image_links_to'])
                                echo '</a>';
                        }
                echo '</li>';
            }
        ?>
            </ul>
<?php
            if($wp_logo_slider_settings['arrow'] != 0) {
                echo '<div class="slider-controls"><a href="#" id="prev">&lt;</a> <a href="#" id="next">&gt;</a></div>';
            }

    echo '</div>';
}

// create the shortcode [wp_LogoSlider]
add_shortcode('logo-slider', 'wp_slider_shortcode');
function wp_slider_shortcode($atts) {
    // Temp solution, output buffer the echo function.
    ob_start();
    logo_slider();
    $output = ob_get_clean();

    return $output;
}

add_action('wp_print_scripts', 'wp_LogoSlider_scripts');
function wp_LogoSlider_scripts() {
    if(!is_admin())
    wp_enqueue_script('cycle', WP_CONTENT_URL.'/plugins/logo-slider/lgs_jquery.cycle2.js', array('jquery'), '', true);
}

add_action( 'wp_head', 'wp_logo_slider_style' );
function wp_logo_slider_style() { 
    global $wp_logo_slider_settings;
    global $options;
?>
    <style type="text/css" media="screen">
    <?php 
        echo $wp_logo_slider_settings['custom_css'];
    ?>
	#logo-slider-wraper {
            position:relative;	
	}
	.slider-controls {
            position:absolute;
            width:<?php echo $wp_logo_slider_settings['slider_width']; ?>px;	
            top: <?php echo $wp_logo_slider_settings['slider_height'] / 2 - 19 ?>px !important;
	}
	#logo-slider {
            position: relative;
            width: <?php echo $wp_logo_slider_settings['slider_width']; ?>px;
            height: <?php echo $wp_logo_slider_settings['slider_height']?>px;
            margin: 0; padding: 0;
            overflow: hidden;
            list-style:none;
            background:<?php echo $wp_logo_slider_settings['bgcolour']; ?>;
            text-align:center;
	}
	.slide {
            list-style:none;
            margin:0 !important;
            width:<?php echo $wp_logo_slider_settings['slider_width']; ?>px !important;
	}
	.slider-controls a {
            height:40px;
            width:40px;
            display:inline-block;
            text-indent:-9000px;
	}
	#prev{
            background:url(<?php echo WP_CONTENT_URL.'/plugins/logo-slider/arrows/arrow'. $wp_logo_slider_settings['arrow'].'.png'; ?>) no-repeat center;
            float:right;
            margin-right:-50px;
	}	
	#next{
            background:url(<?php echo WP_CONTENT_URL.'/plugins/logo-slider/arrows/arrow'. $wp_logo_slider_settings['arrow'].'-prev.png'; ?>) no-repeat center;
            float:left;
            margin-left:-50px
	}	
    /*
    ===============================================================
        --------------------_ Responsive _--------------------
    ===============================================================
    */
	@media screen and (max-width:320px) {
            #logo-slider-wraper{
                position:relative !important;
                width:52% !important;
                left:42px;
            }
            .slider-controls {
                position: absolute;
                top: <?php echo $wp_logo_slider_settings['slider_height'] / 2 - 19 ?>px;
                left:30px;
                width: 100% !important;
            }
            #logo-slider {
                background:<?php echo $wp_logo_slider_settings['bgcolour']; ?>;
                height: <?php echo $wp_logo_slider_settings['slider_height']?>px;
                list-style: none outside none;
                margin: 0;
                overflow: hidden;
                padding: 0;
                position: relative;
                width: 110% !important;
            }
            .slide {
                list-style: none outside none;
                margin: 0 !important;
                width: 100% !important;
            }
            #next{
                background:url(<?php echo WP_CONTENT_URL.'/plugins/logo-slider/arrows/arrow'. $wp_logo_slider_settings['arrow'].'-prev.png'; ?>) no-repeat center;
                float:left;
                margin-left:-66px !important;
            }
            .logo-img {
                margin-left:32px;
            }
	}
	@media screen and (min-width:321px) and (max-width:480px){
            #logo-slider-wraper{
                position:relative;
                width:35% !important;
                left:55px !important;
            }
            .slider-controls {
                position: absolute;
                top: <?php echo $wp_logo_slider_settings['slider_height'] / 2 - 19 ?>px;
                width: 100% !important;
            }
            #logo-slider {
                background:<?php echo $wp_logo_slider_settings['bgcolour']; ?>;
                height: <?php echo $wp_logo_slider_settings['slider_height']?>px;
                list-style: none outside none;
                margin: 0;
                overflow: hidden;
                padding: 0;
                position: relative;
                width: 102% !important;
            }
            .slide {
                list-style: none outside none;
                margin: 0 !important;
                width: 100% !important;
            }
	}
	@media screen and (min-width:321px) and (max-width:360px){
            #logo-slider-wraper{
                position:relative;
                width:50% !important;
            }
            .slider-controls {
                position: absolute;
                top: <?php echo $wp_logo_slider_settings['slider_height'] / 2 - 19 ?>px;
                width: 100% !important;
            }
            #logo-slider {
                background:<?php echo $wp_logo_slider_settings['bgcolour']; ?>;
                height: <?php echo $wp_logo_slider_settings['slider_height']?>px;
                list-style: none outside none;
                margin: 0;
                overflow: hidden;
                padding: 0;
                position: relative;
                width: 100% !important;
            }
            .slide {
                list-style: none outside none;
                margin: 0 !important;
                width: 100% !important;
            }
	}
	@media screen and (min-width:481px) and (max-width:640px){
            #logo-slider-wraper{
                position:relative;
                width:28% !important;
                left:34px !important;
            }
            .slider-controls {
                position: absolute;
                top: <?php echo $wp_logo_slider_settings['slider_height'] / 2 - 19 ?>px;
                width: 100% !important;
            }
            #logo-slider {
                background:<?php echo $wp_logo_slider_settings['bgcolour']; ?>;
                height: <?php echo $wp_logo_slider_settings['slider_height']?>px;
                list-style: none outside none;
                margin: 0;
                overflow: hidden;
                padding: 0;
                position: relative;
                width: 100% !important;
            }
            .slide {
                list-style: none outside none;
                margin: 0 !important;
                width: 100% !important;
            }
	}
	@media only screen and (min-width:641px) and (max-width:768px){
            #logo-slider-wraper{
                position:relative;
                width:78% !important;
                left:34px !important
            }
            .slider-controls {
                position: absolute;
                top: <?php echo $wp_logo_slider_settings['slider_height'] / 2 - 19 ?>px;
                width: 100% !important;
            }
            #logo-slider {
                background:<?php echo $wp_logo_slider_settings['bgcolour']; ?>;
                height: <?php echo $wp_logo_slider_settings['slider_height']?>px;
                list-style: none outside none;
                margin: 0;
                overflow: hidden;
                padding: 0;
                position: relative;
                width: 100% !important;
                left:-12px;
            }
            .slide {
                list-style: none outside none;
                margin: 0 !important;
                width: 100% !important;
            }
	}
	@media only screen and (min-width:770px){
            #logo-slider-wraper{
                position:relative;
                width:<?php echo $wp_logo_slider_settings['slider_width']; ?>px !important;
                left:34px !important;
            }
            .slider-controls {
                position: absolute;
                top: <?php echo $wp_logo_slider_settings['slider_height'] / 2 - 19 ?>px;
                width: 100% !important;
            }
            #logo-slider {
                background:<?php echo $wp_logo_slider_settings['bgcolour']; ?>;
                height: <?php echo $wp_logo_slider_settings['slider_height']?>px;
                list-style: none outside none;
                margin: 0;
                overflow: hidden;
                padding: 0;
                position: relative;
                width: 100% !important;
            }
            .slide {
                list-style: none outside none;
                margin: 0 !important;
                width: <?php echo $wp_logo_slider_settings['slider_width']; ?>px !important;
            }
	}
    </style>	
<?php
}

add_action( 'admin_enqueue_scripts', 'wp_logo_slider_admin_styles' );
function wp_logo_slider_admin_styles(){ ?>
    <style type="text/css" media="screen">
        #arrow-style p {
            float:left;
            height:60px;
            width:40px;
            text-align:center;
            margin-right:16px;
        }
    </style>
<?php
}
?>