<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

final class SiteSettingsAdmin
{
    public function register(): void
    {
        add_submenu_page('stageart-plugin', '団体基本情報', '団体基本情報', 'manage_options', 'stageart-site-settings', [$this, 'render']);
        add_submenu_page('stageart-plugin', '連絡先', '連絡先', 'manage_options', 'stageart-contact', [$this, 'renderContact']);
        add_action('admin_post_stageart_save_site_settings', [$this, 'save']);
        add_action('admin_post_stageart_save_contact', [$this, 'saveContact']);
        add_action('admin_init', [$this, 'ensureContactPage']);
    }
    private function guard(string $action): void { if (!current_user_can('manage_options')) wp_die('権限がありません。'); check_admin_referer($action); }
    public function render(): void {
        $v = fn($k, $d = '') => get_option('stageart_org_' . $k, $d);
        echo '<div class="wrap"><h1>団体基本情報</h1>'; if (isset($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="stageart_save_site_settings">'; wp_nonce_field('stageart_site_settings');
        echo '<table class="form-table"><tr><th>団体名 *</th><td><input class="regular-text" required name="name" value="' . esc_attr($v('name')) . '" /></td></tr><tr><th>団体紹介</th><td><textarea class="large-text" rows="8" name="description">' . esc_textarea($v('description')) . '</textarea></td></tr>';
        foreach (['x' => 'X', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'facebook' => 'Facebook'] as $k => $label) echo '<tr><th>' . esc_html($label) . '</th><td><input class="regular-text" type="url" name="' . $k . '" value="' . esc_attr($v($k)) . '" /></td></tr>';
        echo '</table>'; submit_button('保存'); echo '</form></div>';
    }
    public function save(): void { $this->guard('stageart_site_settings'); foreach (['name' => 'text', 'description' => 'textarea', 'x' => 'url', 'instagram' => 'url', 'youtube' => 'url', 'facebook' => 'url'] as $k => $type) { $raw = wp_unslash($_POST[$k] ?? ''); $value = $type === 'url' ? esc_url_raw($raw) : ($type === 'textarea' ? sanitize_textarea_field($raw) : sanitize_text_field($raw)); update_option('stageart_org_' . $k, $value, false); } wp_safe_redirect(admin_url('admin.php?page=stageart-site-settings&saved=1')); exit; }
    public function ensureContactPage(): void {
        $id = (int) get_option('stageart_plugin_contact_page_id', 0); if ($id && get_post($id)) return; $existing = get_page_by_title('連絡先', OBJECT, 'page'); if ($existing) { update_option('stageart_plugin_contact_page_id', (int) $existing->ID, false); return; }
        $id = wp_insert_post(['post_title' => '連絡先', 'post_name' => 'contact', 'post_status' => 'draft', 'post_type' => 'page', 'post_content' => '', 'meta_input' => ['_stageart_system_content' => 'contact']], true); if (!is_wp_error($id)) update_option('stageart_plugin_contact_page_id', (int) $id, false);
    }
    public function renderContact(): void {
        $id = (int) get_option('stageart_plugin_contact_page_id', 0); $v = fn($k, $d = '') => get_post_meta($id, '_stageart_contact_' . $k, true) ?: $d;
        echo '<div class="wrap"><h1>連絡先</h1><p>連絡先は独立したシステムコンテンツです。トップページやメニューへの配置は別のコンテンツ配置機能で行います。</p>'; if (isset($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="stageart_save_contact">'; wp_nonce_field('stageart_contact'); echo '<table class="form-table"><tr><th>所在地</th><td><textarea class="large-text" rows="3" name="address">' . esc_textarea($v('address')) . '</textarea></td></tr><tr><th>メールアドレス</th><td><input class="regular-text" type="email" name="email" value="' . esc_attr($v('email')) . '" /></td></tr><tr><th>電話番号</th><td><input class="regular-text" name="phone" value="' . esc_attr($v('phone')) . '" /></td></tr></table>'; submit_button('保存'); echo '</form></div>';
    }
    public function saveContact(): void { $this->guard('stageart_contact'); $id = (int) get_option('stageart_plugin_contact_page_id', 0); update_post_meta($id, '_stageart_contact_address', sanitize_textarea_field(wp_unslash($_POST['address'] ?? ''))); update_post_meta($id, '_stageart_contact_email', sanitize_email(wp_unslash($_POST['email'] ?? ''))); update_post_meta($id, '_stageart_contact_phone', sanitize_text_field(wp_unslash($_POST['phone'] ?? ''))); wp_safe_redirect(admin_url('admin.php?page=stageart-contact&saved=1')); exit; }
}
