<?php get_header(); ?>
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
  background: rgba(0, 0,0, 0.5);
}

.et_pb_fullwidth_header_0.et_pb_fullwidth_header .et_pb_fullwidth_header_container { z-index: 100; }
.entry-title { text-transform:uppercase;}

</style>

<div id="main-content">

	<div class="et_pb_section et_pb_fullwidth_section  et_pb_section_0 et_section_regular">
								
		<section class="et_pb_fullwidth_header et_pb_module et_pb_bg_layout_light et_pb_text_align_center  et_pb_fullwidth_header_0">
			
			<div class="et_pb_fullwidth_header_container center">
				<div class="header-content-container center">
				<div class="header-content">
						
					<h1 class="entry-title"><?php single_tag_title(); ?></h1>
										
				</div>
				</div>
					
			</div>
			<div class="et_pb_fullwidth_header_overlay"></div>
			<div class="et_pb_fullwidth_header_scroll"></div>
			
		</section>
				
	</div> <!-- .et_pb_section -->
			
	<div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area">
			
		<?php
			if ( have_posts() ) :
				while ( have_posts() ) : the_post();
					$post_format = et_pb_post_format(); ?>

					<article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post' ); ?>>

				<?php
					$thumb = '';

					$width = (int) apply_filters( 'et_pb_index_blog_image_width', 1080 );

					$height = (int) apply_filters( 'et_pb_index_blog_image_height', 1080 );
					$classtext = 'et_pb_post_main_image';
					$titletext = get_the_title();
					$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
					$thumb = $thumbnail["thumb"];

					et_divi_post_format_content();

					if ( ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) {
						if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) :
							printf(
								'<div class="et_main_video_container">
									%1$s
								</div>',
								$first_video
							);
						elseif ( ! in_array( $post_format, array( 'gallery' ) ) && 'on' === et_get_option( 'divi_thumbnails_index', 'on' ) && '' !== $thumb ) : ?>
							<a href="<?php the_permalink(); ?>">
								<?php print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
							</a>
					<?php
						elseif ( 'gallery' === $post_format ) :
							et_pb_gallery_images();
						endif;
					} ?>

				<?php if ( ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) : ?>
					<?php if ( ! in_array( $post_format, array( 'link', 'audio' ) ) ) : ?>
						<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php endif; ?>

					<?php
						et_divi_post_meta();

						if ( 'on' !== et_get_option( 'divi_blog_style', 'false' ) || ( is_search() && ( 'on' === get_post_meta( get_the_ID(), '_et_pb_use_builder', true ) ) ) ) {
							truncate_post( 270 );
						} else {
							the_excerpt();
						}
						
						echo get_the_tag_list('<p class="tags">Tags: ',', ','</p>');
						
						
					?>
				<?php endif; ?>
				
				</article> <!-- .et_pb_post -->
			<?php
					endwhile;

					if ( function_exists( 'wp_pagenavi' ) )
						wp_pagenavi();
					else
						get_template_part( 'includes/navigation', 'index' );
				else :
					get_template_part( 'includes/no-results', 'index' );
				endif;
			?>
			</div> <!-- #left-area -->

			<?php get_sidebar(); ?>
		</div> <!-- #content-area -->
	</div> <!-- .container -->
</div> <!-- #main-content -->

<?php get_footer(); ?>