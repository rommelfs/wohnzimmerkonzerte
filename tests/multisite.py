#!/usr/bin/env python3
"""Integration runner for a disposable local/development WordPress Multisite."""
import argparse
import json
from pathlib import Path
import subprocess
import uuid

parser = argparse.ArgumentParser()
parser.add_argument('--wordpress', required=True)
parser.add_argument('--wp-cli', required=True, help='Absolute path to wp-cli.phar')
parser.add_argument('--network-url', required=True)
args = parser.parse_args()
repository = Path(__file__).resolve().parent.parent
base = ['php', str(Path(args.wp_cli).resolve()), '--path=' + str(Path(args.wordpress).resolve()), '--require=' + str(repository / 'tests/no-mail.php')]

def wp(*command, url=None):
    result = subprocess.run(base + ['--url=' + (url or args.network_url)] + list(command), capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError('WP-CLI command failed: ' + ' '.join(command[:3]) + '\n' + '\n'.join(line for line in (result.stdout + result.stderr).splitlines() if 'Deprecated:' not in line))
    return '\n'.join(line for line in result.stdout.splitlines() if line.strip() and 'Deprecated:' not in line)

def check(code, label, url):
    wp('eval', 'if (!(' + code + ')) { WP_CLI::error(' + json.dumps(label) + '); }', url=url)
    print('PASS: ' + label, flush=True)

if wp('eval', 'echo wp_get_environment_type();') not in ('local', 'development'):
    raise SystemExit('Refusing to run outside a disposable local/development installation.')
check('is_multisite()', 'WordPress is a Multisite', args.network_url)
check("!is_plugin_active_for_network('wohnzimmerkonzerte/wohnzimmerkonzerte.php')", 'Plugin starts without network activation', args.network_url)

suffix = uuid.uuid4().hex[:10]
ids = []
network_enabled = False
try:
    def site(label):
        slug = 'rl-' + label + '-' + suffix
        site_id = int(wp('site', 'create', '--slug=' + slug, '--title=RL Test ' + label, '--porcelain').splitlines()[-1])
        ids.append(site_id)
        return site_id, wp('eval', f'echo get_home_url({site_id});').splitlines()[-1]

    aid, a = site('a')
    bid, b = site('b')
    # Core domain mapping: no DNS needed for WP-CLI requests to the selected site.
    mapped = 'https://rl-mapped-' + suffix + '.example.test'
    wp('eval', f"$result = wp_update_site({aid}, ['domain'=>'rl-mapped-{suffix}.example.test', 'path'=>'/']); if (is_wp_error($result)) WP_CLI::error($result->get_error_message());")
    wp('option', 'update', 'home', mapped, url=mapped)
    wp('option', 'update', 'siteurl', mapped, url=mapped)
    a = mapped
    wp('option', 'update', 'permalink_structure', '/%postname%/', url=a)
    wp('plugin', 'activate', 'woocommerce', url=a)
    wp('plugin', 'activate', 'wohnzimmerkonzerte', url=a)
    check("function_exists('rl_events') && shortcode_exists('rommelfangen_homepage')", 'Plugin active on mapped concert website', a)
    check("!function_exists('rl_events') && !shortcode_exists('rommelfangen_homepage')", 'Unrelated website has no concert hooks', b)
    check("get_option('rl_settings', null) === null", 'Unrelated website settings are untouched', b)

    wp('eval', "update_option('theme_mods_wohnzimmer', ['rl_youtube'=>'https://example.test/legacy-b']);", url=b)

    event = int(wp('post', 'create', '--post_type=rl_concert', '--post_status=publish', '--post_title=Isolated concert', '--porcelain', url=a).splitlines()[-1])
    wp('eval', f"update_post_meta({event}, 'rl_start', wp_date('Y-m-d\\TH:i', time()+DAY_IN_SECONDS)); update_option('rl_settings', ['instagram'=>'https://example.test/only-a']);", url=a)
    page = int(wp('post', 'create', '--post_type=page', '--post_status=publish', '--post_title=Concert homepage', '--post_content=[rommelfangen_homepage]', '--porcelain', url=a).splitlines()[-1])
    wp('option', 'update', 'show_on_front', 'page', url=a)
    wp('option', 'update', 'page_on_front', str(page), url=a)
    check("count(rl_events()) === 1 && rl_setting('instagram') === 'https://example.test/only-a'", 'Concerts and links belong to target website', a)
    check("str_contains(do_shortcode('[rommelfangen_homepage]'), 'Isolated concert')", 'Shortcode renders concert highlight', a)
    check("(static function () { global $wp_query; $wp_query = new WP_Query(['page_id'=>" + str(page) + "]); do_action('wp_enqueue_scripts'); return apply_filters('template_include','normal.php') === RL_PLUGIN_DIR . 'templates/page.php' && wp_style_is('rl-concerts','enqueued'); })()", 'Homepage selects plugin template and stylesheet', a)
    check("(static function () { global $wp_query; $wp_query = new WP_Query(['post_type'=>'rl_concert','p'=>" + str(event) + "]); return apply_filters('template_include','normal.php') === RL_PLUGIN_DIR . 'templates/single-concert.php'; })()", 'Concert detail selects plugin template', a)
    plain = int(wp('post', 'create', '--post_type=page', '--post_status=publish', '--post_title=Ordinary page', '--porcelain', url=a).splitlines()[-1])
    check("(static function () { global $wp_query; $wp_query = new WP_Query(['page_id'=>" + str(plain) + "]); do_action('wp_enqueue_scripts'); return apply_filters('template_include','normal.php') === 'normal.php' && !wp_style_is('rl-concerts','enqueued'); })()", 'Ordinary page keeps theme and has no concert stylesheet', a)
    check("str_starts_with(get_permalink(" + str(event) + "), '" + mapped + "/konzerte/')", 'Concert permalink uses mapped domain', a)
    check("(new WP_Query(['post_type'=>'rl_concert','post_status'=>'publish']))->post_count === 0", 'Concerts do not leak into unrelated website', b)
    check("get_option('rl_rewrite_version') === RL_REWRITE_VERSION", 'Site-local URL rules initialized', a)
    wp('eval', "delete_option('rl_rewrite_version');", url=a)
    check("get_option('rl_rewrite_version') === RL_REWRITE_VERSION", 'URL rules refresh without plugin reactivation after update', a)
    wp('eval-file', str(repository / 'tests/wordpress.php'), url=a)
    print('PASS: WooCommerce ticket and capacity regression tests on mapped site', flush=True)
    print(wp('eval-file', str(repository / 'tests/editor-shop.php'), url=a), flush=True)
    check("get_option('rl_site', null) === null", 'Editor settings remain isolated from neighboring websites', b)

    wp('plugin', 'activate', 'wohnzimmerkonzerte', '--network')
    network_enabled = True
    check("function_exists('rl_events') && rl_events() === [] && rl_setting('instagram') === ''", 'Network activation preserves per-site isolation', b)
    check("count(rl_events()) === 1 && rl_setting('instagram') === 'https://example.test/only-a'", 'Network activation preserves concert website data', a)
    check("rl_setting('youtube') === 'https://example.test/legacy-b'", 'Legacy theme links migrate on their own website', b)
    wp('eval', "update_option('rl_settings', ['youtube'=>'https://example.test/edited-b']); delete_option('rl_rewrite_version');", url=b)
    check("rl_setting('youtube') === 'https://example.test/edited-b'", 'Later updates preserve edited plugin settings', b)
    cid, c = site('c')
    check("shortcode_exists('rommelfangen_homepage') && get_option('rl_rewrite_version') === RL_REWRITE_VERSION", 'New network website initializes on first request', c)
    wp('plugin', 'deactivate', 'wohnzimmerkonzerte', '--network')
    network_enabled = False
    check("!function_exists('rl_events') && get_option('rl_rewrite_version', null) === null", 'Network deactivation removes routing marker from other website', b)
    check("count(rl_events()) === 1 && rl_setting('instagram') === 'https://example.test/only-a'", 'Site activation and data survive network deactivation', a)
    wp('plugin', 'deactivate', 'wohnzimmerkonzerte', url=a)
    check("get_post(" + str(event) + ")->post_title === 'Isolated concert' && get_option('rl_settings')['instagram'] === 'https://example.test/only-a'", 'Deactivation retains concert data and settings', a)
finally:
    if network_enabled:
        wp('plugin', 'deactivate', 'wohnzimmerkonzerte', '--network')
    for site_id in reversed(ids):
        wp('site', 'delete', str(site_id), '--yes')
print('Multisite integration checks passed; temporary websites removed.', flush=True)
