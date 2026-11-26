<?php
if (!defined('ABSPATH')) exit;
function rl_link_fields(): array {
    return ['instagram'=>'Instagram', 'youtube'=>'YouTube', 'facebook'=>'Facebook', 'gift'=>'Geschenkgutschein-Produkt', 'imprint'=>'Impressum', 'privacy'=>'Datenschutz'];
}
function rl_setting(string $key): string {
    $settings=get_option('rl_settings', []);
    return is_array($settings) && is_string($settings[$key] ?? null) ? $settings[$key] : '';
}
function rl_sanitize_settings($input): array {
    $clean=[];
    foreach (array_keys(rl_link_fields()) as $key) $clean[$key]=rl_safe_url(is_array($input) ? ($input[$key] ?? '') : '');
    return $clean;
}
add_action('admin_menu', function () { add_options_page('Rommelfangen Concerts', 'Rommelfangen Concerts', 'manage_options', 'rl-concerts', 'rl_settings_page'); });
add_action('admin_init', function () {
    register_setting('rl_concerts', 'rl_settings', ['type'=>'array','sanitize_callback'=>'rl_sanitize_settings','default'=>[]]);
    register_setting('rl_concerts', 'rl_site', ['type'=>'array','sanitize_callback'=>'rl_sanitize_site','default'=>[]]);
});
add_action('admin_enqueue_scripts', function ($hook) {
    $screen=get_current_screen();
    if ($hook !== 'settings_page_rl-concerts' && (!$screen || $screen->post_type !== 'rl_concert')) return;
    wp_enqueue_media();
    wp_enqueue_editor();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('rl-admin', plugins_url('assets/admin.css',RL_PLUGIN_FILE), [], RL_VERSION);
    wp_enqueue_script('rl-admin', plugins_url('assets/admin.js',RL_PLUGIN_FILE), ['jquery','jquery-ui-sortable','wp-color-picker'], RL_VERSION, true);
});
function rl_control(string $name, string $label, $value, string $type='text', array $choices=[]): void {
    $id='rl-'.sanitize_html_class(str_replace(['[',']'], ['-',''], $name));
    echo '<div class="rl-field"><label for="'.esc_attr($id).'">'.esc_html($label).'</label>';
    if ($type==='image') {
        echo '<div class="rl-media"><input id="'.esc_attr($id).'" class="rl-image-id" type="hidden" name="'.esc_attr($name).'" value="'.esc_attr((string)$value).'">';
        echo '<div class="rl-media-preview">'.($value ? wp_get_attachment_image((int)$value,'thumbnail') : '').'</div><button type="button" class="button rl-media-select">Bild auswählen</button> <button type="button" class="button rl-media-clear">Entfernen</button></div>';
    } elseif ($type==='select') {
        echo '<select id="'.esc_attr($id).'" name="'.esc_attr($name).'">';
        foreach ($choices as $key=>$text) echo '<option value="'.esc_attr((string)$key).'" '.selected((string)$value,(string)$key,false).'>'.esc_html($text).'</option>';
        echo '</select>';
    } elseif ($type==='checkbox') {
        echo '<input type="hidden" name="'.esc_attr($name).'" value="0"><input id="'.esc_attr($id).'" type="checkbox" name="'.esc_attr($name).'" value="1" '.checked((bool)$value,true,false).'>';
    } elseif ($type==='editor') {
        wp_editor((string)$value, $id, ['textarea_name'=>$name,'textarea_rows'=>5,'media_buttons'=>true]);
    } elseif ($type==='textarea') {
        echo '<textarea id="'.esc_attr($id).'" name="'.esc_attr($name).'" rows="3">'.esc_textarea((string)$value).'</textarea>';
    } else {
        echo '<input id="'.esc_attr($id).'" class="'.($type==='color' ? 'rl-color' : 'regular-text').'" type="'.esc_attr(in_array($type,['number','url'],true) ? $type : 'text').'" name="'.esc_attr($name).'" value="'.esc_attr((string)$value).'">';
    }
    echo '</div>';
}
function rl_section_editor(array $section, string $index, bool $dynamic=false): void {
    $prefix='rl_site[sections]['.$index.']';
    echo '<li class="rl-section-item"><details><summary><span class="rl-drag" aria-hidden="true">☰</span> <strong>'.esc_html($section['title'] ?: (rl_section_types()[$section['type']] ?? 'Bereich')).'</strong></summary>';
    echo '<div class="rl-section-tools"><button class="button rl-move-up" type="button">Nach oben</button> <button class="button rl-move-down" type="button">Nach unten</button> <button class="button-link-delete rl-remove-section" type="button">Bereich entfernen</button></div>';
    echo '<div class="rl-fields">';
    rl_control($prefix.'[enabled]','Bereich anzeigen',$section['enabled'],'checkbox');
    rl_control($prefix.'[hide_empty]','Leere dynamische Bereiche automatisch ausblenden',$section['hide_empty'] ?? true,'checkbox');
    rl_control($prefix.'[type]','Inhaltstyp',$section['type'],'select',rl_section_types());
    rl_control($prefix.'[id]','Sprungmarke (z. B. ueber-uns)',$section['id']);
    rl_control($prefix.'[eyebrow]','Dachzeile',$section['eyebrow']);
    rl_control($prefix.'[title]','Überschrift',$section['title']);
    echo '</div>';
    rl_control($prefix.'[body]','Text / Bilder / Video',$section['body'],$dynamic ? 'textarea' : 'editor');
    echo '<div class="rl-fields">';
    rl_control($prefix.'[image_id]','Bereichsbild / Ersatzbild',$section['image_id'],'image');
    rl_control($prefix.'[layout]','Anordnung',$section['layout'],'select',['split'=>'Text links / Bild rechts','image-first'=>'Bild links / Text rechts','stacked'=>'Untereinander']);
    rl_control($prefix.'[background]','Hintergrund (leer = vererben)',$section['background'],'color');
    rl_control($prefix.'[color]','Textfarbe (leer = vererben)',$section['color'],'color');
    rl_control($prefix.'[accent]','Akzentfarbe (leer = vererben)',$section['accent'],'color');
    rl_control($prefix.'[columns]','Spalten auf großen Bildschirmen',$section['columns'],'select',[1=>'1',2=>'2',3=>'3',4=>'4']);
    rl_control($prefix.'[limit]','Maximale Anzahl Einträge',$section['limit'],'number');
    rl_control($prefix.'[button_label]','Button-Beschriftung',$section['button_label']);
    rl_control($prefix.'[button_url]','Button-Ziel (URL oder #Sprungmarke)',$section['button_url']);
    echo '</div><p class="description">Beim Highlight kommen Titel und Auszug aus dem gewählten Konzert. Ohne Konzert wird der Bereichstext verwendet. Bei Listen ergänzen Texte und Bild die Überschrift. Eine leere Button-Beschriftung blendet den ergänzenden Button aus.</p></details></li>';
}
function rl_settings_page(): void {
    if (!current_user_can('manage_options')) return;
    $site=rl_site();
    echo '<div class="wrap rl-admin"><h1>Rommelfangen Living Room Concerts</h1><p>Alle Änderungen gelten nur für diese Website. Die Startseite verwendet <code>[rommelfangen_homepage]</code>. Der Ticketshop ist sofort unter <a href="'.esc_url(rl_shop_url()).'">'.esc_html(rl_shop_url()).'</a> erreichbar. Alternativ eine Seite mit <code>[rommelfangen_tickets]</code> anlegen.</p>';
    settings_errors();
    echo '<form action="options.php" method="post">'; settings_fields('rl_concerts');
    echo '<details open><summary>Marke & Erscheinungsbild</summary><div class="rl-fields">';
    foreach (['brand'=>'Name','subtitle'=>'Untertitel','footer_text'=>'Footer-Text'] as $key=>$label) rl_control('rl_site['.$key.']',$label,$site[$key]);
    rl_control('rl_site[date_format]','Datumsanzeige',$site['date_format'],'select',['d. F Y · H:i'=>'27. November 2026 · 19:30','d.m.Y · H:i'=>'27.11.2026 · 19:30','D, d.m.Y · H:i'=>'Fr, 27.11.2026 · 19:30']);
    $homepages=[0=>'Konzertstartseite automatisch finden'];
    foreach (get_pages(['post_status'=>'publish']) as $page) if (has_shortcode($page->post_content,'rommelfangen_homepage')) $homepages[$page->ID]=$page->post_title;
    rl_control('rl_site[home_page_id]','Konzertstartseite für Logo und Sprunglinks',$site['home_page_id'],'select',$homepages);
    $pages=[0=>'Sofort verfügbaren Ticketshop verwenden'];
    foreach (get_pages(['post_status'=>'publish']) as $page) if (has_shortcode($page->post_content,'rommelfangen_tickets')) $pages[$page->ID]=$page->post_title;
    rl_control('rl_site[shop_page_id]','Ticketshop-Seite',$site['shop_page_id'],'select',$pages);
    rl_control('rl_site[commerce_shell]','Eigenes Design auch für Warenkorb und Kasse verwenden',$site['commerce_shell'],'checkbox');
    rl_control('rl_site[logo_id]','Logo im Kopfbereich',$site['logo_id'],'image');
    rl_control('rl_site[footer_logo_id]','Logo im Fußbereich (optional; sonst Kopfbereich-Logo)',$site['footer_logo_id'],'image');
    rl_control('rl_site[logo_mode]','Logo-Darstellung',$site['logo_mode'],'select',['combined'=>'Bildzeichen mit zusätzlichem Website-Namen','logo-only'=>'Nur Logo (Schriftzug ist im Bild enthalten)','text-only'=>'Nur Website-Name und Untertitel']);
    foreach (['logo_width'=>'Maximale Logo-Breite im Kopfbereich (80–480 px)','logo_height'=>'Maximale Logo-Höhe (40–240 px)','logo_mobile_width'=>'Maximale Logo-Breite auf Smartphones (80–320 px)','footer_logo_width'=>'Maximale Logo-Breite im Fußbereich (80–400 px)'] as $key=>$label) rl_control('rl_site['.$key.']',$label,$site[$key],'number');
    echo '<p class="description">Transparente PNG-Dateien verwenden. Für Logos mit integriertem Schriftzug „Nur Logo“ wählen. Für den Kopfbereich empfiehlt sich die horizontale Variante, für den Fußbereich auch das Hauptlogo. Ohne gültiges Bild wird immer der Website-Name angezeigt.</p>';
    rl_control('rl_site[fallback_image_id]','Allgemeines Ersatzbild',$site['fallback_image_id'],'image');
    rl_control('rl_site[font]','Überschriften-Schrift',$site['font'],'select',['serif'=>'Klassische Serifenschrift','sans'=>'Klare serifenlose Schrift','mono'=>'Monospace']);
    rl_control('rl_site[background_style]','Hintergrundwirkung',$site['background_style'],'select',['atmosphere'=>'Sanfte Lichtverläufe','plain'=>'Ruhige Farbfläche']);
    rl_control('rl_site[color_scheme]','Darstellung der Formulare',$site['color_scheme'],'select',['dark'=>'Dunkel','light'=>'Hell']);
    rl_control('rl_site[font_size]','Grundschriftgröße (16–22 px)',$site['font_size'],'number');
    rl_control('rl_site[image_fit]','Bilddarstellung',$site['image_fit'],'select',['cover'=>'Fläche ausfüllen / zuschneiden','contain'=>'Vollständiges Bild zeigen']);
    rl_control('rl_site[hero_ratio]','Format des großen Highlight-Bildes',$site['hero_ratio'],'select',['4/5'=>'Hochformat','1/1'=>'Quadratisch','4/3'=>'Querformat','16/9'=>'Breites Querformat']);
    rl_control('rl_site[photo_tilt]','Neigung des Highlight-Bildes (−5 bis 5 Grad)',$site['photo_tilt'],'number');
    foreach (['width'=>'Maximale Breite (960–1600 px)','radius'=>'Eckenradius (0–40 px)','spacing'=>'Bereichsabstand (24–140 px)'] as $key=>$label) rl_control('rl_site['.$key.']',$label,$site[$key],'number');
    foreach (['bg'=>'Seitenhintergrund','surface'=>'Karten / Flächen','ink'=>'Text','muted'=>'Sekundärtext','accent'=>'Akzent / Buttons','button_text'=>'Button-Text','line'=>'Linien'] as $key=>$label) rl_control('rl_site[colors]['.$key.']',$label,$site['colors'][$key],'color');
    echo '</div></details><details><summary>Grunddesign für kommende und vergangene Konzerte</summary><p>Diese Vorgaben gelten für Highlight, Konzertkarten und Konzertdetails. Einzelne Konzerte können sie überschreiben.</p><div class="rl-fields">';
    foreach (['upcoming'=>'Kommende Konzerte','past'=>'Vergangene Konzerte'] as $status=>$label) {
        foreach (['background'=>'Hintergrund','color'=>'Textfarbe','accent'=>'Akzent'] as $key=>$field) rl_control('rl_site[styles]['.$status.']['.$key.']',$label.' · '.$field,$site['styles'][$status][$key],'color');
        rl_control('rl_site[styles]['.$status.'][image_id]',$label.' · Ersatzbild',$site['styles'][$status]['image_id'],'image');
        rl_control('rl_site[styles]['.$status.'][layout]',$label.' · Anordnung',$site['styles'][$status]['layout'],'select',[''=>'Bereichs- / Standardlayout','split'=>'Text links / Bild rechts','image-first'=>'Bild links / Text rechts','stacked'=>'Untereinander']);
    }
    echo '</div></details><h2>Startseite zusammenstellen</h2><p>Bereiche ziehen oder mit den Tasten verschieben. Du kannst Bereiche ausblenden, entfernen oder mehrfach hinzufügen. Alle Felder werden gemeinsam gespeichert.</p><ul id="rl-sections" aria-label="Reihenfolge der Startseitenbereiche">';
    foreach ($site['sections'] as $i=>$section) rl_section_editor($section,(string)$i);
    echo '</ul><p><label for="rl-new-type">Neuer Bereich</label> <select id="rl-new-type">';
    foreach (rl_section_types() as $type=>$label) echo '<option value="'.esc_attr($type).'">'.esc_html($label).'</option>';
    echo '</select> <button type="button" class="button" id="rl-add-section">Bereich hinzufügen</button></p>';
    foreach (rl_section_types() as $type=>$label) {
        echo '<template id="rl-template-'.esc_attr($type).'">'; rl_section_editor(rl_section_defaults($type),'__INDEX__',true); echo '</template>';
    }
    echo '<details><summary>Navigation</summary><p>Reihenfolge und Beschriftungen der eigenen Links. Ticketshop und Warenkorb werden zusätzlich automatisch eingebunden.</p><div id="rl-navigation">';
    foreach ($site['navigation'] as $i=>$item) rl_navigation_editor($item,(string)$i);
    echo '</div><button class="button" id="rl-add-navigation" type="button">Link hinzufügen</button><template id="rl-navigation-template">';rl_navigation_editor(['label'=>'','url'=>'','enabled'=>true],'__INDEX__');echo '</template></details>';
    echo '<details><summary>Social Media & rechtliche Links</summary><div class="rl-fields">';
    foreach (rl_link_fields() as $key=>$label) rl_control('rl_settings['.$key.']',$label.' URL',rl_setting($key));
    echo '</div></details><details><summary>Beschriftungen, Meldungen & Button-Texte</summary><div class="rl-fields">';
    foreach ($site['ui'] as $key=>$value) rl_control('rl_site[ui]['.$key.']',rl_ui_defaults()[$key],$value,'textarea');
    echo '</div></details>';
    submit_button('Website-Einstellungen speichern');echo '</form></div>';
}
function rl_navigation_editor(array $item,string $index): void {
    $p='rl_site[navigation]['.$index.']';
    echo '<div class="rl-nav-item"><div class="rl-fields">';
    rl_control($p.'[enabled]','Anzeigen',$item['enabled'],'checkbox');
    rl_control($p.'[label]','Beschriftung',$item['label']);rl_control($p.'[url]','URL oder #Sprungmarke',$item['url']);
    echo '</div><button type="button" class="button rl-move-up">Nach oben</button> <button type="button" class="button rl-move-down">Nach unten</button> <button class="button-link-delete rl-remove-navigation" type="button">Entfernen</button></div>';
}
