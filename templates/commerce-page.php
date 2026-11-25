<?php if (!defined('ABSPATH')) exit; rl_header(); ?>
<main id="main" class="wrap section rl-commerce"><?php while (have_posts()): the_post(); ?><h1><?php the_title(); ?></h1><?php the_content(); endwhile; ?></main><?php rl_footer(); ?>
