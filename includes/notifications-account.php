<?php
if (!defined('ABSPATH')) exit;

add_action('woocommerce_after_order_notes', function ($checkout) {
    woocommerce_form_field('rl_concert_notifications', ['type' => 'checkbox', 'label' => rl_mail_consent(), 'required' => false], false);
});
add_action('woocommerce_checkout_order_processed', function ($id, $data, $order) {
    if (($_POST['rl_concert_notifications'] ?? '') === '1') rl_mail_order_signup($order, 'checkout');
}, 10, 3);
// Checkout Blocks use WooCommerce's supported additional checkout fields API.
add_action('woocommerce_init', function () {
    if (function_exists('woocommerce_register_additional_checkout_field')) {
        woocommerce_register_additional_checkout_field([
            'id' => 'rommelfangen/concert-notifications', 'label' => rl_mail_consent(),
            'location' => 'order', 'type' => 'checkbox', 'required' => false,
        ]);
    }
});
add_action('woocommerce_store_api_checkout_order_processed', function ($order) {
    if ($order->get_meta('_wc_other/rommelfangen/concert-notifications') === '1'
        || $order->get_meta('_wc_other/rommelfangen/concert-notifications') === true) rl_mail_order_signup($order, 'checkout-block');
});
function rl_mail_order_signup($order, string $source): void {
    if ($order->get_meta('_rl_mail_signup_requested')) return;
    rl_mail_subscribe($order->get_billing_email(), $source, ['first_name'=>$order->get_billing_first_name(), 'last_name'=>$order->get_billing_last_name()]);
    $order->update_meta_data('_rl_mail_signup_requested', '1');
    $order->save_meta_data();
}
add_action('woocommerce_account_dashboard', function () {
    $row = rl_mail_subscriber(wp_get_current_user()->user_email);
    echo '<section class="rl-mail-account"><h2>Konzertbenachrichtigungen</h2><p>Für deine Konto-Adresse: ' . esc_html(wp_get_current_user()->user_email) . '</p>';
    if ($row && $row->status === 'confirmed') echo '<p>Du bist angemeldet.</p>';
    elseif ($row && $row->status === 'pending') echo '<p>Bitte bestätige deine Anmeldung über den Link in deiner E-Mail.</p>';
    else echo '<p>Du bist nicht angemeldet.</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="rl_mail_account">';
    wp_nonce_field('rl_mail_account');
    echo '<p><label><input type="checkbox" name="consent" value="1" ' . checked($row && in_array($row->status, ['confirmed','pending'], true), true, false) . '> ' . esc_html(rl_mail_consent()) . '</label></p><button class="button" type="submit">Benachrichtigungen speichern</button></form></section>';
});
add_action('admin_post_rl_mail_account', function () {
    if (!is_user_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') wp_die('Nicht erlaubt.', '', ['response' => 403]);
    check_admin_referer('rl_mail_account');
    $email = wp_get_current_user()->user_email;
    if (($_POST['consent'] ?? '') === '1') rl_mail_subscribe($email, 'account', ['first_name'=>wp_get_current_user()->first_name, 'last_name'=>wp_get_current_user()->last_name]);
    elseif ($row = rl_mail_subscriber($email)) rl_mail_unsubscribe((int)$row->id, rl_mail_token((int)$row->id));
    wp_safe_redirect(wc_get_account_endpoint_url('dashboard'));
    exit;
});

add_filter('wp_privacy_personal_data_exporters', function ($exporters) {
    $exporters['rl-concert-notifications'] = ['exporter_friendly_name' => 'Konzertbenachrichtigungen', 'callback' => 'rl_mail_privacy_export'];
    return $exporters;
});
function rl_mail_privacy_export(string $email, int $page = 1): array {
    global $wpdb;
    $row = rl_mail_subscriber($email);
    if (!$row) return ['data' => [], 'done' => true];
    $items = [];
    if ($page === 1) {
        $data = [];
        foreach (['email' => 'E-Mail', 'first_name'=>'Vorname', 'last_name'=>'Nachname', 'status' => 'Status', 'source' => 'Anmeldequelle', 'consent' => 'Einwilligungstext', 'requested_at' => 'Angefordert (UTC)', 'confirmed_at' => 'Bestätigt (UTC)', 'unsubscribed_at' => 'Abgemeldet (UTC)'] as $key => $label) {
            $value = str_ends_with($key, '_at') ? ((int)$row->$key ? gmdate('c', (int)$row->$key) : '') : $row->$key;
            $data[] = ['name' => $label, 'value' => $value];
        }
        $items[] = ['group_id' => 'rl-mail', 'group_label' => 'Konzertbenachrichtigungen', 'item_id' => 'rl-mail-subscriber-' . $row->id, 'data' => $data];
    }
    $rows = $wpdb->get_results($wpdb->prepare('SELECT d.id, d.status, c.subject FROM ' . rl_mail_table('deliveries') . ' d JOIN ' . rl_mail_table('campaigns') . ' c ON c.id=d.campaign_id WHERE d.subscriber_id=%d ORDER BY d.id LIMIT 50 OFFSET %d', $row->id, (max(1, $page) - 1) * 50));
    foreach ($rows as $delivery) $items[] = ['group_id' => 'rl-mail', 'group_label' => 'Konzertbenachrichtigungen', 'item_id' => 'rl-mail-delivery-' . $delivery->id,
        'data' => [['name' => 'Betreff', 'value' => $delivery->subject], ['name' => 'Versandstatus', 'value' => $delivery->status]]];
    return ['data' => $items, 'done' => count($rows) < 50];
}
function rl_mail_delete(int $id): void {
    global $wpdb;
    // Stop future sends before removing personal records.
    $wpdb->update(rl_mail_table('subscribers'), ['status' => 'unsubscribed'], ['id' => $id]);
    $wpdb->delete(rl_mail_table('deliveries'), ['subscriber_id' => $id]);
    $wpdb->delete(rl_mail_table('subscribers'), ['id' => $id]);
}
add_filter('wp_privacy_personal_data_erasers', function ($erasers) {
    $erasers['rl-concert-notifications'] = ['eraser_friendly_name' => 'Konzertbenachrichtigungen', 'callback' => function ($email, $page = 1) {
        $row = rl_mail_subscriber($email);
        if ($row) rl_mail_delete((int)$row->id);
        return ['items_removed' => (bool)$row, 'items_retained' => false, 'messages' => [], 'done' => true];
    }];
    return $erasers;
});
