<?php
/*
Template Name: Fun Casino Blank
*/
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>" />
<title><?php _e('Fun Casino', 'fun-casino'); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet" />
<?php wp_head(); ?>
</head>
<body>
<div class="fc-container">
<?php
while ( have_posts() ) : the_post();
    the_content();
endwhile;
?>
</div>
<?php wp_footer(); ?>
</body>
</html>
