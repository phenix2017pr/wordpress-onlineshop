<?php 
if( !class_exists('WooZoneLiteWcProductModify_Variable') ){
	class WooZoneLiteWcProductModify_Variable Extends WC_Product_Variable {
	    public function get_image( $size = 'shop_thumbnail', $attr = array(), $placeholder = true ) {
	        if ( has_post_thumbnail( $this->get_id() ) ) {
				$image = get_the_post_thumbnail( $this->get_id(), $size, $attr );
			} elseif ( ( $parent_id = wp_get_post_parent_id( $this->get_id() ) ) && has_post_thumbnail( $parent_id ) ) {
				$image = get_the_post_thumbnail( $parent_id, $size, $attr );
			} elseif ( $placeholder ) {
				$image = wc_placeholder_img( $size );
			} else {
				$image = '';
			}

			return $image;
	    }
	}
}