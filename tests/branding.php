<?php
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_get_environment_type(),['local','development'],true)) throw new RuntimeException('Disposable test site required.');
function rl_brand_expect($ok,string $label): void { if (!$ok) throw new RuntimeException($label);echo 'PASS: '.$label."\n"; }
$before=get_option('rl_site',null);$ids=[];
try {
 foreach (['Kopfbereich','Fußbereich'] as $title) { $id=wp_insert_attachment(['post_title'=>$title,'post_mime_type'=>'image/png']);$ids[]=$id;update_post_meta($id,'_wp_attached_file','branding-test-'.$id.'.png'); }
 $site=rl_site_defaults();$site['logo_id']=$ids[0];$site['footer_logo_id']=$ids[1];$site['logo_mode']='logo-only';update_option('rl_site',$site);
 ob_start();rl_brand();$header=ob_get_clean();ob_start();rl_brand(true);$footer=ob_get_clean();
 rl_brand_expect(str_contains($header,'branding-test-'.$ids[0].'.png') && str_contains($footer,'branding-test-'.$ids[1].'.png'),'Header and footer can use separate logo attachments');
 rl_brand_expect(!str_contains($header,'brand-copy') && str_contains($header,'aria-label="Rommelfangen') && str_contains($header,'loading="eager"'),'Wordmark logos avoid duplicated text while retaining an accessible brand name');
 rl_brand_expect(str_contains($footer,'loading="lazy"'),'Footer logo does not block the first viewport');
 $site['footer_logo_id']=999999;update_option('rl_site',$site);ob_start();rl_brand(true);$footer_fallback=ob_get_clean();rl_brand_expect(str_contains($footer_fallback,'branding-test-'.$ids[0].'.png'),'Missing footer logo uses the still-valid header logo');
 $site['logo_mode']='combined';update_option('rl_site',$site);ob_start();rl_brand();$combined=ob_get_clean();rl_brand_expect(str_contains($combined,'brand-copy') && str_contains($combined,'<img'),'Icon logos can be combined with editable website text');
 $site['logo_mode']='text-only';update_option('rl_site',$site);ob_start();rl_brand();$text=ob_get_clean();rl_brand_expect(!str_contains($text,'<img') && str_contains($text,'brand-copy'),'Text-only branding remains available');
 $site['logo_mode']='logo-only';$site['logo_id']=999999;$site['footer_logo_id']=0;update_option('rl_site',$site);ob_start();rl_brand();$missing=ob_get_clean();rl_brand_expect(!str_contains($missing,'<img') && str_contains($missing,'brand-copy'),'Missing or deleted logo falls back to visible website text');
 $site['logo_width']=9999;$site['logo_height']=-10;$site['logo_mobile_width']=9999;$site['footer_logo_width']=-5;$site['logo_mode']='invalid';$clean=rl_sanitize_site($site);
 rl_brand_expect($clean['logo_width']===480 && $clean['logo_height']===40 && $clean['logo_mobile_width']===320 && $clean['footer_logo_width']===80 && $clean['logo_mode']==='combined','Logo dimensions and display mode are constrained on save');
 $site['header_menu_gap']=9999;rl_brand_expect(rl_sanitize_site($site)['header_menu_gap']===160,'Header menu gap has a bounded upper limit');$site['header_menu_gap']=-1;rl_brand_expect(rl_sanitize_site($site)['header_menu_gap']===16,'Header menu gap cannot collapse below the minimum');$site['header_menu_gap']=64;update_option('rl_site',$site);rl_brand_expect(str_contains(rl_design_css(),'--header-menu-gap:64px'),'Selected menu spacing is exported to the frontend');
 $legacy=rl_site_defaults();unset($legacy['logo_mode'],$legacy['footer_logo_id'],$legacy['logo_width'],$legacy['header_menu_gap']);$legacy['logo_id']=$ids[0];update_option('rl_site',$legacy);ob_start();rl_brand();$legacy_html=ob_get_clean();rl_brand_expect(str_contains($legacy_html,'<img') && str_contains($legacy_html,'brand-copy'),'Existing image-and-text branding is preserved on upgrade');
 rl_brand_expect(rl_site()['header_menu_gap']===56,'Existing websites receive a compact default gap without losing saved logos');
 echo "Branding integration checks passed.\n";
} finally {
 foreach($ids as $id)wp_delete_attachment($id,true);if($before===null)delete_option('rl_site');else update_option('rl_site',$before);
}
