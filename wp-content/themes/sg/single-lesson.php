<?php
/**
 * The template for displaying all single posts.
 *
 * @package Sydney
 */

get_header(); ?>
<?php 
	$fullwidth = 'fullwidth'; ?>
	
	<div id="primary" class="content-area col-md-9 <?php echo $fullwidth; ?>">
		<main id="main" class="post-wrap" role="main">

			<?php while ( have_posts() ) : the_post(); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<header class="entry-header">
						<?php the_title( '<h1 class="title-post">', '</h1>' ); ?>
					</header><!-- .entry-header -->

					<div class="entry-content">
						<?php the_content(); ?>
					</div><!-- .entry-content -->

					<footer class="entry-footer">
						<?php sydney_entry_footer(); ?>
					</footer><!-- .entry-footer -->
				</article><!-- #post-## -->
			<?php endwhile; // end of the loop. ?>

		</main><!-- #main -->
	</div><!-- #primary -->

    <script src="https://player.vimeo.com/api/player.js"></script>
    <script>
        var iframe = document.querySelector('iframe');
        var player = new Vimeo.Player(iframe);

        player.on('loaded', function () {

        });

        player.on('ended', function() {
            console.log('Ended');
            document.getElementById("mark_complete_form").click();
            /*$( "#mark_complete_form" ).submit(function( event ) {
                alert( "Handler for .submit() called." );
                event.preventDefault();
            });*/
        });

    </script>

<?php get_footer(); ?>