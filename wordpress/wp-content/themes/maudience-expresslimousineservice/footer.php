<?php
/**
 * The template for displaying the footer
 *
 * Contains footer content and the closing of the #main and #page div elements.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
?>
	</div><!-- #main .wrapper -->
	<footer id="colophon" role="contentinfo">

		<?php if ( is_active_sidebar( 'footer-widget-left' ) ) : ?>
			<div id="footer-widget-left" class="footer-widget-left widget-area" role="complementary">
				<?php dynamic_sidebar( 'footer-widget-left' ); ?>
			</div><!-- #footer-widget-left -->
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'footer-widget-center' ) ) : ?>
			<div id="footer-widget-center" class="footer-widget-center widget-area" role="complementary">
				<?php dynamic_sidebar( 'footer-widget-center' ); ?>
			</div><!-- #footer-widget-center -->
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'footer-widget-right' ) ) : ?>
			<div id="footer-widget-right" class="footer-widget-right widget-area" role="complementary">
				<?php dynamic_sidebar( 'footer-widget-right' ); ?>
			</div><!-- #footer-widget-right -->
		<?php endif; ?>

	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>