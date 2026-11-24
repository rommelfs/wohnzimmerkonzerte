<?php if (!defined('ABSPATH')) exit; ?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip" href="#main">Zum Inhalt</a>
<header class="site-header wrap"><a class="brand" href="<?php echo esc_url(home_url('/')); ?>">Rommelfangen<span>Living Room Concerts</span></a>
<nav aria-label="Hauptnavigation"><a href="<?php echo esc_url(home_url('/#konzerte')); ?>">Konzerte</a><a href="<?php echo esc_url(home_url('/#stories')); ?>">Stories</a><a href="<?php echo esc_url(home_url('/#gutscheine')); ?>">Gutscheine</a><?php if (function_exists('wc_get_cart_url')): ?><a class="nav-cart" href="<?php echo esc_url(wc_get_cart_url()); ?>">Warenkorb</a><?php endif; ?></nav></header>
