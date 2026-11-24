<?php
if (!defined('ABSPATH')) exit;
rl_header();
while (have_posts()) { the_post(); the_content(); }
rl_footer();
