<?php if (!defined('ABSPATH')) exit; $branding=rl_site(); ?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?><a class="skip" href="#main"><?php echo esc_html(rl_ui('skip')); ?></a>
<header class="site-header wrap"><?php rl_brand(); ?>
<nav aria-label="Hauptnavigation"><?php foreach ($branding['navigation'] as $link) if (!empty($link['enabled']) && $link['label'] && $link['url'] && rl_nav_link_visible($link['url'])) echo '<a href="'.esc_url(rl_nav_url($link['url'])).'">'.esc_html($link['label']).'</a>'; ?>
<?php if ($anchor=rl_newsletter_anchor()): ?><a href="<?php echo esc_url(rl_concert_home_url().'#'.$anchor); ?>"><?php echo esc_html(rl_ui('nav_newsletter')); ?></a><?php endif; ?>
<a href="<?php echo esc_url(rl_shop_url()); ?>"><?php echo esc_html(rl_ui('nav_shop')); ?></a><?php if (function_exists('wc_get_cart_url')): ?><a class="nav-cart" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php echo esc_html(rl_ui('nav_cart')); ?></a><?php endif; ?></nav></header>
