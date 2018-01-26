<?php
/*
Template Name: Sitemap
*/

get_header();

$is_page_builder_used = et_pb_is_pagebuilder_used( get_the_ID() );

?>
<style>	
.et_pb_fullwidth_header_0 { padding: 10% 0; }
.et_pb_fullwidth_header_0.et_pb_fullwidth_header { background-image: url( <?php 
    candy_featured_image(); 
?> ); }
.et_pb_fullwidth_header_0.et_pb_fullwidth_header { position: relative; } 
.et_pb_fullwidth_header_0.et_pb_fullwidth_header:before {
  content: " ";
  z-index: 10;
  display: block;
  position: absolute;
  height: 100%;
  top: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.5);
}

.et_pb_fullwidth_header_0.et_pb_fullwidth_header .et_pb_fullwidth_header_container { z-index: 100; }
.entry-title { text-transform:uppercase;}
</style>

<div id="main-content">

	<div class="et_pb_section et_pb_fullwidth_section  et_pb_section_0 et_section_regular sitemap">

		<section class="et_pb_fullwidth_header et_pb_module et_pb_bg_layout_dark et_pb_text_align_center  et_pb_fullwidth_header_0">
				
			<div class="et_pb_fullwidth_header_container center">
				<div class="header-content-container center">
				<div class="header-content">
						
					<h1 class="entry-title"><?php esc_html_e( 'Sitemap', 'Divi' ); ?></h1>
						
				</div>
				</div>
			</div>
			<div class="et_pb_fullwidth_header_overlay"></div>
			<div class="et_pb_fullwidth_header_scroll"></div>
			
		</section>
		
	</div>

<?php if ( ! $is_page_builder_used ) : ?>

	<div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area">

<?php endif; ?>

			<?php while ( have_posts() ) : the_post(); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<?php if ( ! $is_page_builder_used ) : ?>
	
				<?php
	
					$thumb = '';

					$width = (int) apply_filters( 'et_pb_index_blog_image_width', 1080 );

					$height = (int) apply_filters( 'et_pb_index_blog_image_height', 675 );
					$classtext = 'et_featured_image';
					$titletext = get_the_title();
					$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
					$thumb = $thumbnail["thumb"];

					if ( 'on' === et_get_option( 'divi_page_thumbnails', 'false' ) && '' !== $thumb )
						print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height );
				?>

				<?php endif; ?>

					<div class="entry-content sitemap">
					
					<h3><?php esc_html_e( 'Pages', 'Divi' ); ?> : </h3>  
					
						<ul class="sitemap-pages"><?php wp_list_pages("title_li=" ); ?></ul>  
 
					<h3><?php esc_html_e( 'Categories', 'Divi' ); ?> : </h3>  
						
						<ul class="sitemap-pages"><?php wp_list_categories('sort_column=name&optioncount=1&hierarchical=0&feed=RSS&title_li'); ?></ul>  
	
					<h3><?php esc_html_e( 'Tags', 'Divi' ); ?> : </h3>  
						
						<ul class="sitemap-pages"><?php
								$tags = get_tags( array('orderby' => 'count', 'order' => 'DESC') );
								foreach ( (array) $tags as $tag ) {
								echo '<li><a href="' . get_tag_link ($tag->term_id) . '" rel="tag">' . $tag->name . ' (' . $tag->count . ') </a></li>';
								}
							?>
						</ul>
	
					<h3><?php esc_html_e( 'All Blog Posts', 'Divi' ); ?> : </h3>  
					
						<ul class="sitemap-pages"><?php $archive_query = new WP_Query('showposts=1000&cat=-8');  
							while ($archive_query->have_posts()) : $archive_query->the_post(); ?>  
							<li>  
								<a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a>  
								(<?php comments_number('0', '1', '%'); ?>)  
							</li>  
							<?php endwhile; ?>  
						</ul>  
   
					<h3><?php esc_html_e( 'Archives', 'Divi' ); ?> : </h3>  
					
						<ul class="sitemap-pages">  
							<?php wp_get_archives('type=monthly&show_post_count=true'); ?>  
						</ul>  
						
					<h3><?php esc_html_e( 'Feeds', 'Divi' ); ?> : </h3>  
						
						<ul class="sitemap-pages">  
							<li><a title="Full content" href="feed:<?php bloginfo('rss2_url'); ?>"><?php esc_html_e( 'Main RSS', 'Divi' ); ?></a></li>  
							<li><a title="Comment Feed" href="feed:<?php bloginfo('comments_rss2_url'); ?>"><?php esc_html_e( 'Comment Feed', 'Divi' ); ?></a></li>  
						</ul> 				
					
					<?php 
					//Iterate over get_post_types arrays. 
					//get_post_types(): returns the registered post types as found in $wp_post_types. 
					//Set '_builtin' to false and 'public' to true to return only public Custom Post Types.
					foreach( get_post_types( array('public' => true, '_builtin' => false) ) as $post_type ) {
 
					//Retrieves an object which describes any registered post type: 
					//in this case only user-created Custom Post TypeS
					$cpt = get_post_type_object( $post_type );
 
					echo '<h3>'.$cpt->labels->name.' : </h3>';
						
						echo '<ul class="sitemap-pages">'; 
 
						$args = array(
							'post_type' => $post_type,
							'post__not_in' => array(), /**Exclude custom posts by ID separated by comma inside the array.**/
							'posts_per_page' => -1  //show all custom posts
							);
						$query_cpt = new WP_Query($args);
 
						//Loop throught the Custom Post TypeS and display them as a list. Exit when done.
						while( $query_cpt->have_posts() ) {
							$query_cpt->the_post();
							echo '<li><a title="'.get_the_title().'" href="'.get_permalink().'">'.get_the_title().'</a></li>';
							}
						echo '</ul>';
						} 
						?> 

					<?php
					
						if ( ! $is_page_builder_used )
							wp_link_pages( array( 'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'Divi' ), 'after' => '</div>' ) );
					?>
					</div> <!-- .entry-content -->

				<?php
					if ( ! $is_page_builder_used && comments_open() && 'on' === et_get_option( 'divi_show_pagescomments', 'false' ) ) comments_template( '', true );
				?>

				</article> <!-- .et_pb_post -->

			<?php endwhile; ?>

<?php if ( ! $is_page_builder_used ) : ?>

			</div> <!-- #left-area -->

			<?php get_sidebar(); ?>
			
		</div> <!-- #content-area -->
		
	</div> <!-- .container -->

<?php endif; ?>

</div> <!-- #main-content -->

<?php get_footer(); ?>