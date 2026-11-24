<?php
if (!defined('ABSPATH')) exit;

/** Site-local, lazy upgrade: also runs after git pull and for new network sites. */
function rl_maybe_upgrade(): void {
    if (get_option('rl_rewrite_version') === RL_REWRITE_VERSION) return;
    if (get_option('rl_settings', null) === null) {
        $links = [];
        $legacy = get_option('theme_mods_wohnzimmer', []);
        foreach (array_keys(rl_link_fields()) as $key) {
            $value = is_array($legacy) ? ($legacy['rl_' . $key] ?? '') : '';
            if ($value) $links[$key] = esc_url_raw($value, ['http', 'https']);
        }
        add_option('rl_settings', $links, '', false);
    }
    flush_rewrite_rules(false); // Never rewrite the network's shared .htaccess.
    update_option('rl_rewrite_version', RL_REWRITE_VERSION, false);
}
add_action('init', 'rl_maybe_upgrade', 20);

function rl_activate(bool $network_wide = false): void {
    rl_register_content();
    // Other network sites initialize lazily on their first request, without a network-wide write loop.
    delete_option('rl_rewrite_version');
    rl_maybe_upgrade();
}
function rl_deactivate(bool $network_wide = false): void {
    unregister_post_type('rl_concert');
    unregister_post_type('rl_testimonial');
    if (is_multisite() && $network_wide) {
        // Invalidate only derived routing data; retain every site's content and settings.
        $offset = 0;
        do {
            $ids = get_sites(['network_id' => get_current_network_id(), 'fields' => 'ids', 'number' => 100, 'offset' => $offset, 'orderby' => 'id', 'order' => 'ASC']);
            foreach ($ids as $id) {
                delete_blog_option((int)$id, 'rl_rewrite_version');
                delete_blog_option((int)$id, 'rewrite_rules');
            }
            $offset += count($ids);
        } while (count($ids) === 100);
        return;
    }
    delete_option('rl_rewrite_version');
    flush_rewrite_rules(false);
}
