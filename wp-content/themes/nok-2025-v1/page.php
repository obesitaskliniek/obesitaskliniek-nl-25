<?php
/* Template Name: FAQ */

use NOK2025\V1\Helpers;

get_header();
?>

<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <?php the_content(); // displays whatever you wrote in the wordpress editor ?>
<?php endwhile; endif; //ends the loop
?>

<?php
get_footer();
?>