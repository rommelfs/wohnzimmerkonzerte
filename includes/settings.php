<?php
if (!defined('ABSPATH')) exit;
function rl_link_fields(): array {
    return ['instagram' => 'Instagram', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'gift' => 'Geschenkgutschein-Produkt', 'imprint' => 'Impressum', 'privacy' => 'Datenschutz'];
}
function rl_setting(string $key): string {
    $settings = get_option('rl_settings', []);
    return is_array($settings) && isset($settings[$key]) && is_string($settings[$key]) ? $settings[$key] : '';
}
function rl_sanitize_settings($input): array {
    $clean = [];
    foreach (array_keys(rl_link_fields()) as $key) {
        $clean[$key] = is_array($input) && isset($input[$key]) && is_string($input[$key])
            ? esc_url_raw($input[$key], ['http', 'https']) : '';
    }
    return $clean;
}
add_action('admin_menu', function () {
    add_options_page('Rommelfangen Concerts', 'Rommelfangen Concerts', 'manage_options', 'rl-concerts', 'rl_settings_page');
});
add_action('admin_init', function () {
    register_setting('rl_concerts', 'rl_settings', ['type' => 'array', 'sanitize_callback' => 'rl_sanitize_settings', 'default' => []]);
    add_settings_section('rl_links', 'Links dieser Website', '__return_false', 'rl-concerts');
    foreach (rl_link_fields() as $key => $label) {
        add_settings_field('rl_' . $key, $label, function () use ($key) {
            echo '<input class="regular-text" type="url" name="rl_settings[' . esc_attr($key) . ']" value="' . esc_attr(rl_setting($key)) . '" placeholder="https://">';
        }, 'rl-concerts', 'rl_links');
    }
});
function rl_settings_page(): void {
    if (!current_user_can('manage_options')) return;
    echo '<div class="wrap"><h1>Rommelfangen Living Room Concerts</h1><p>Eine Seite mit dem Shortcode <code>[rommelfangen_homepage]</code> veröffentlichen und unter Einstellungen → Lesen als Startseite auswählen. Diese Einstellungen gelten nur für die aktuelle Website.</p><form action="options.php" method="post">';
    settings_fields('rl_concerts');
    do_settings_sections('rl-concerts');
    submit_button();
    echo '</form></div>';
}
