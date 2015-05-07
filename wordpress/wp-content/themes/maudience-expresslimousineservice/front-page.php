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
				<?php get_template_part( 'content', 'page' ); ?>
				<?php comments_template( '', true ); ?>
			<?php endwhile; // end of the loop. ?>

			<section id="" class="home-section">
				<div id="slider-section" class="column-left section-column">
				</div>

				<div id="instant_rate_finder-section" class="column-right section-column">
				</div>
			</section>

			<section id="our-services" class="home-section">
				<div class="services-information-output">
						<?php maudience_return_custom_posts('services', 3); ?>
					</div>
			</section>

			<section id="" class="home-section">
				<div id="testimonials-section" class="column-left section-column">
				</div>

				<div id="service_area-section" class="column-right section-column">
				</div>
			</section>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_footer(); ?>