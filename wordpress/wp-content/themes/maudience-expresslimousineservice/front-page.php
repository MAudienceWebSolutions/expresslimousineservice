<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */

get_header(); ?>

	<div id="primary" class="site-content">
		<div id="content" role="main">
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<section id="top-section" class="home-section">

						<div id="slider-section" class="column-left section-column">
							<header class="entry-header">
								<h1 class="entry-title"><?php the_title(); ?></h1>
							</header>
							<div class="entry-content"><?php the_content(); ?></div><!-- .entry-content -->
							<div class="slider-wrap">
								<?php
								if ( function_exists( 'soliloquy' ) ) { soliloquy( 'front-page-slider', 'slug' ); }
								?>

							</div>
						</div>

						<div id="instant_rate_finder-section" class="column-right section-column">
						</div>
					</section>

					<section id="middle-section" class="home-section">
						<div class="services-information-output">
							<?php maudience_return_custom_posts('services', 3); ?>
						</div>
					</section>

					<section id="bottom-section" class="home-section">
						<div id="testimonials-section" class="column-left section-column">
							<div class="testimonials-information-output">
								<?php maudience_return_custom_posts('testimonials', 3); ?>
							</div>
						</div>

						<div id="service_area-section" class="column-right section-column">

						</div>
					</section>

				</article><!-- #post -->
			<?php endwhile; // end of the loop. ?>
		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_footer(); ?>