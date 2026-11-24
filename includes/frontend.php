<?php
if (!defined('ABSPATH')) exit;
function rl_date(int $timestamp): string { return wp_date('d. F Y · H:i', $timestamp); }
function rl_image(int $id, string $size = 'large'): void {
    if (has_post_thumbnail($id)) echo get_the_post_thumbnail($id, $size, ['loading' => 'lazy']);
    else echo '<img src="' . esc_url(plugins_url('assets/stage.svg', RL_PLUGIN_FILE)) . '" alt="Illustration einer kleinen Wohnzimmerbühne" loading="lazy">';
}
function rl_card(int $id, int $timestamp = 0): void { ?>
    <article class="card"><a class="image" tabindex="-1" aria-hidden="true" href="<?php echo esc_url(get_permalink($id)); ?>"><?php rl_image($id, 'medium_large'); ?></a>
    <div class="card-body"><p class="eyebrow"><?php echo $timestamp ? esc_html(rl_date($timestamp)) : esc_html(get_the_date('', $id)); ?></p>
    <h3><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html(get_the_title($id)); ?></a></h3>
    <p><?php echo esc_html(get_the_excerpt($id)); ?></p></div></article>
<?php }

function rl_header(): void { require RL_PLUGIN_DIR . 'templates/header.php'; }
function rl_footer(): void { require RL_PLUGIN_DIR . 'templates/footer.php'; }
function rl_is_homepage(): bool {
    $post = get_queried_object();
    return is_singular('page') && $post instanceof WP_Post && has_shortcode($post->post_content, 'rommelfangen_homepage');
}
function rl_is_concert_view(): bool {
    return rl_is_homepage() || is_singular('rl_concert') || is_post_type_archive('rl_concert');
}
add_shortcode('rommelfangen_homepage', function () {
    ob_start();
    require RL_PLUGIN_DIR . 'templates/home-content.php';
    return ob_get_clean();
});
add_filter('template_include', function ($template) {
    if (rl_is_homepage()) return RL_PLUGIN_DIR . 'templates/page.php';
    if (is_singular('rl_concert')) return RL_PLUGIN_DIR . 'templates/single-concert.php';
    if (is_post_type_archive('rl_concert')) return RL_PLUGIN_DIR . 'templates/archive-concerts.php';
    return $template;
}, 99);
add_action('wp_enqueue_scripts', function () {
    if (rl_is_concert_view()) wp_enqueue_style('rl-concerts', plugins_url('assets/frontend.css', RL_PLUGIN_FILE), [], RL_VERSION);
}, 100);
add_filter('body_class', function ($classes) {
    if (rl_is_concert_view()) $classes[] = 'rl-concert-view';
    return $classes;
});
