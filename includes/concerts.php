<?php
if (!defined('ABSPATH')) exit;

function rl_register_content(): void {
    register_post_type('rl_concert', [
        'labels' => ['name' => 'Konzerte', 'singular_name' => 'Konzert', 'add_new_item' => 'Konzert hinzufügen'],
        'public' => true, 'show_in_rest' => true, 'has_archive' => true,
        'rewrite' => ['slug' => 'konzerte'], 'menu_icon' => 'dashicons-tickets-alt',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'],
        'taxonomies' => ['post_tag'],
    ]);
    register_post_type('rl_testimonial', [
        'labels' => ['name' => 'Gästestimmen', 'singular_name' => 'Gästestimme'],
        'public' => false, 'show_ui' => true, 'show_in_rest' => true,
        'menu_icon' => 'dashicons-format-quote', 'supports' => ['title', 'editor'],
    ]);
    foreach (['rl_start', 'rl_doors', 'rl_venue', 'rl_cancelled', 'rl_product_id'] as $key) {
        register_post_meta('rl_concert', $key, [
            'single' => true, 'type' => $key === 'rl_product_id' ? 'integer' : 'string',
            'show_in_rest' => false, // Private venue notes must not leak via the public REST API.
            'sanitize_callback' => $key === 'rl_product_id' ? 'absint' : 'sanitize_text_field',
            'auth_callback' => static fn($allowed, $key, $id) => current_user_can('edit_post', $id),
        ]);
    }
}
add_action('init', 'rl_register_content');

function rl_timestamp(string $value): int {
    if (!$value) return 0;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, wp_timezone());
    return $date && $date->format('Y-m-d\TH:i') === $value ? $date->getTimestamp() : 0;
}
function rl_events(): array {
    $posts = get_posts(['post_type' => 'rl_concert', 'numberposts' => -1, 'post_status' => 'publish']);
    return array_map(static fn($p) => [
        'id' => $p->ID, 'start' => rl_timestamp((string)get_post_meta($p->ID, 'rl_start', true)),
        'cancelled' => get_post_meta($p->ID, 'rl_cancelled', true) === '1',
    ], $posts);
}
function rl_ticket_product(int $id) {
    if (!function_exists('wc_get_product')) return false;
    $product = wc_get_product((int)get_post_meta($id, 'rl_product_id', true));
    return $product && $product->get_status() === 'publish' ? $product : false;
}
function rl_product_can_link(int $id, int $concert): bool {
    if (!$id) return true;
    $remembered=(int)get_post_meta($id, '_rl_ticket_concert', true);
    if ($remembered && $remembered !== $concert) return false;
    if (!function_exists('wc_get_product')) return false;
    $p = wc_get_product($id);
    if (!$p || !$p->is_type('simple') || !$p->is_virtual() || !$p->managing_stock() || $p->backorders_allowed()) return false;
    $linked = get_posts(['post_type' => 'rl_concert', 'post_status' => ['publish','draft','pending','private','future'], 'numberposts' => -1,
        'meta_key' => 'rl_product_id', 'meta_value' => $id, 'exclude' => [$concert]]);
    return !$linked;
}
add_action('add_meta_boxes', function () {
    add_meta_box('rl_details', 'Konzert & Ticketkontingent', function ($post) {
        wp_nonce_field('rl_save', 'rl_nonce');
        foreach (['rl_start' => ['Konzertbeginn', 'datetime-local'], 'rl_doors' => ['Einlass (z. B. 19:00)', 'text'], 'rl_venue' => ['Öffentliche Ortsangabe (keine private Adresse)', 'text']] as $key => [$label, $type]) {
            echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br><input style="width:100%" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="' . esc_attr($type) . '" value="' . esc_attr(get_post_meta($post->ID, $key, true)) . '"></p>';
        }
        $selected = (int)get_post_meta($post->ID, 'rl_product_id', true);
        echo '<p><label for="rl_product_id">Ticketprodukt</label><br><select id="rl_product_id" name="rl_product_id"><option value="0">Kein Ticketverkauf</option>';
        if (function_exists('wc_get_products')) {
            foreach (wc_get_products(['limit' => -1, 'type' => 'simple', 'status' => ['publish','draft']]) as $p) {
                echo '<option value="' . esc_attr($p->get_id()) . '" ' . selected($selected, $p->get_id(), false) . '>' . esc_html($p->get_name()) . '</option>';
            }
        }
        echo '</select></p><p>Pro Konzert ein eigenes einfaches, virtuelles Produkt. Lagerverwaltung aktivieren, Nachbestellungen verbieten. Bestand = verkaufbare Plätze. Reservierte Künstler-/Helferplätze vorher abziehen. Ein Produkt darf nur einem Konzert zugeordnet sein.</p>';
        echo '<label><input name="rl_cancelled" type="checkbox" value="1" ' . checked(get_post_meta($post->ID, 'rl_cancelled', true), '1', false) . '> Konzert abgesagt</label>';
    }, 'rl_concert', 'normal', 'high');
});
add_action('save_post_rl_concert', function ($id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($id)) return;
    if (!isset($_POST['rl_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rl_nonce'])), 'rl_save') || !current_user_can('edit_post', $id)) return;
    foreach (['rl_start', 'rl_doors', 'rl_venue'] as $key) {
        $value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
        if ($key === 'rl_start' && $value && !rl_timestamp($value)) continue;
        update_post_meta($id, $key, $value);
    }
    update_post_meta($id, 'rl_cancelled', isset($_POST['rl_cancelled']) ? '1' : '');
    $product = absint($_POST['rl_product_id'] ?? 0);
    if (rl_product_can_link($product, $id)) update_post_meta($id, 'rl_product_id', $product);
    else set_transient('rl_error_' . get_current_user_id(), 'Ticketprodukt nicht übernommen: einfaches, virtuelles Produkt mit aktivem Bestand ohne Nachbestellungen erforderlich; Zuordnung muss eindeutig sein.', 60);
});
add_action('admin_notices', function () {
    $key = 'rl_error_' . get_current_user_id();
    if ($error = get_transient($key)) { echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>'; delete_transient($key); }
    if (!class_exists('WooCommerce')) echo '<div class="notice notice-warning"><p>Rommelfangen: WooCommerce installieren und aktivieren, um Tickets zu verkaufen.</p></div>';
});

// Also reject direct product URLs and carts created before a concert expired.
function rl_remember_ticket(int $product_id, int $concert_id): void {
    if ($product_id && $concert_id && !get_post_meta($product_id, '_rl_ticket_concert', true)) update_post_meta($product_id, '_rl_ticket_concert', $concert_id);
}
// Retain ticket identity even when the concert is unlinked, trashed or permanently deleted.
foreach (['added_post_meta','updated_post_meta'] as $hook) add_action($hook, static function ($meta_id, $post_id, $key, $value) {
    if ($key === 'rl_product_id' && get_post_type($post_id) === 'rl_concert') rl_remember_ticket((int)$value, (int)$post_id);
}, 10, 4);
add_action('before_delete_post', static function ($id) {
    if (get_post_type($id) === 'rl_concert') rl_remember_ticket((int)get_post_meta($id, 'rl_product_id', true), (int)$id);
});
add_action('init', static function () {
    if (get_option('rl_ticket_identity_version') === '1') return;
    foreach (get_posts(['post_type'=>'rl_concert','post_status'=>['publish','draft','pending','private','future','trash'],'numberposts'=>-1]) as $event) rl_remember_ticket((int)get_post_meta($event->ID,'rl_product_id',true),(int)$event->ID);
    update_option('rl_ticket_identity_version','1',false);
}, 19);
function rl_ticket_open($product): bool {
    $events = get_posts(['post_type' => 'rl_concert', 'numberposts' => -1, 'post_status' => ['publish','draft','pending','private','future'],
        'meta_key' => 'rl_product_id', 'meta_value' => $product->get_id()]);
    $remembered = (int)get_post_meta($product->get_id(), '_rl_ticket_concert', true);
    if (!$events) return !$remembered; // Only products that have never been tickets are ordinary goods.
    if ($remembered && (count($events) !== 1 || (int)$events[0]->ID !== $remembered)) return false;
    rl_remember_ticket((int)$product->get_id(), (int)$events[0]->ID);
    if (count($events) !== 1) return false;
    $event = $events[0];
    return $event->post_status === 'publish' && get_post_meta($event->ID, 'rl_cancelled', true) !== '1'
        && rl_timestamp((string)get_post_meta($event->ID, 'rl_start', true)) > time()
        && $product->is_type('simple') && $product->is_virtual() && $product->managing_stock() && !$product->backorders_allowed();
}
add_filter('woocommerce_is_purchasable', static fn($ok, $p) => $ok && rl_ticket_open($p), 10, 2);
add_action('woocommerce_check_cart_items', function () {
    foreach (WC()->cart->get_cart() as $item) {
        if (!rl_ticket_open($item['data'])) wc_add_notice('Ein Konzertticket ist nicht mehr verfügbar. Bitte entferne es aus dem Warenkorb.', 'error');
    }
});
// A newly duplicated product is a new template, not the original concert's ticket identity.
add_action('woocommerce_product_duplicate_before_save',static function ($duplicate) { $duplicate->delete_meta_data('_rl_ticket_concert'); });
