<?php
if (!defined('ABSPATH')) exit;
add_action('after_setup_theme', function () {
    add_theme_support('title-tag'); add_theme_support('post-thumbnails');
    add_theme_support('woocommerce'); add_theme_support('responsive-embeds');
    add_theme_support('html5', ['gallery', 'caption', 'style', 'script', 'search-form']);
    register_nav_menus(['footer' => 'Footer']);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('rl-style', get_stylesheet_uri(), [], (string)filemtime(get_stylesheet_directory() . '/style.css'));
});
function rl_date(int $timestamp): string { return wp_date('d. F Y · H:i', $timestamp); }
function rl_image(int $id, string $size = 'large'): void {
    if (has_post_thumbnail($id)) echo get_the_post_thumbnail($id, $size, ['loading' => 'lazy']);
    else echo '<img src="' . esc_url(get_theme_file_uri('assets/stage.svg')) . '" alt="Illustration einer kleinen Wohnzimmerbühne" loading="lazy">';
}
function rl_card(int $id, int $timestamp = 0): void { ?>
    <article class="card"><a class="image" tabindex="-1" aria-hidden="true" href="<?php echo esc_url(get_permalink($id)); ?>"><?php rl_image($id, 'medium_large'); ?></a>
    <div class="card-body"><p class="eyebrow"><?php echo $timestamp ? esc_html(rl_date($timestamp)) : esc_html(get_the_date('', $id)); ?></p>
    <h3><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html(get_the_title($id)); ?></a></h3>
    <p><?php echo esc_html(get_the_excerpt($id)); ?></p></div></article>
<?php }
