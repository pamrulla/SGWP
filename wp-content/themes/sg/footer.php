<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package Sydney
 */
?>
			</div>
		</div>
	</div><!-- #content -->

	<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
		<?php get_sidebar('footer'); ?>
	<?php endif; ?>

    <a class="go-top"><i class="fa fa-angle-up"></i></a>

	<footer id="colophon" class="site-footer" role="contentinfo">
        <div class="container">
            <div class="col-md-4 site-info">
                <a href="<?php echo esc_url( __( 'http://sweetymagicalworks.com/', 'Sweety Technologies LLP' ) ); ?>"><?php printf( __( 'Proudly powered by %s', 'Sweety Technologies LLP' ), 'Sweety Technologies LLP' ); ?></a>
            </div><!-- .site-info -->
        <div class="col-md-8 text-right">
            <nav id="mainnav" class="mainnav" role="navigation">
                <?php wp_nav_menu( array( 'theme_location' => 'secondary', 'fallback_cb' => 'sydney_menu_fallback' ) ); ?>
            </nav>
        </div>
        </div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
