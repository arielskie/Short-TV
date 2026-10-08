<?php
get_header();
?>
<div class="short-page-container">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) : the_post();
			the_content();
		endwhile;
	endif;
	?>
</div>
<?php
get_footer();
