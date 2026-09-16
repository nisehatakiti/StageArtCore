<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

final class SiteSettingsAdmin
{
    public const CONTENT_VERSION = '2';

    public function __construct()
    {
        add_action('admin_post_stageart_save_site_settings', [$this, 'save']);
        add_action('admin_post_stageart_save_contact', [$this, 'saveContact']);
    }

    public function register(): void
    {
        add_submenu_page('stageart-plugin', '団体基本情報', '団体基本情報', 'manage_options', 'stageart-site-settings', [$this, 'render']);
        add_submenu_page('stageart-plugin', '連絡先', '連絡先', 'manage_options', 'stageart-contact', [$this, 'renderContact']);
    }

    public static function migrate(): void
    {
        if ((string) get_option('stageart_core_content_version', '') === self::CONTENT_VERSION) return;
        (new self())->ensureContactPage();
        update_option('stageart_core_content_version', self::CONTENT_VERSION, false);
    }

    private function guard(string $action): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        check_admin_referer($action);
    }

    public function render(): void
    {
        $v = static fn($k, $d = '') => get_option('stageart_org_' . $k, $d);
        echo '<div class="wrap"><h1>団体基本情報</h1>';
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="stageart_save_site_settings">';
        wp_nonce_field('stageart_site_settings');
        echo '<table class="form-table"><tr><th>団体名 *</th><td><input class="regular-text" required name="name" value="' . esc_attr($v('name')) . '" /></td></tr><tr><th>団体紹介</th><td><textarea class="large-text" rows="8" name="description">' . esc_textarea($v('description')) . '</textarea></td></tr>';
        foreach (['x' => 'X', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'facebook' => 'Facebook'] as $k => $label) {
            echo '<tr><th>' . esc_html($label) . '</th><td><input class="regular-text" type="url" name="' . $k . '" value="' . esc_attr($v($k)) . '" /></td></tr>';
        }
        echo '</table>';
        submit_button('保存');
        echo '</form></div>';
    }

    public function save(): void
    {
        $this->guard('stageart_site_settings');
        foreach (['name' => 'text', 'description' => 'textarea', 'x' => 'url', 'instagram' => 'url', 'youtube' => 'url', 'facebook' => 'url'] as $k => $type) {
            $raw = wp_unslash($_POST[$k] ?? '');
            $value = $type === 'url' ? esc_url_raw($raw) : ($type === 'textarea' ? sanitize_textarea_field($raw) : sanitize_text_field($raw));
            update_option('stageart_org_' . $k, $value, false);
        }
        wp_safe_redirect(admin_url('admin.php?page=stageart-site-settings&saved=1'));
        exit;
    }

    public function ensureContactPage(): int
    {
        $id = (int) get_option('stageart_plugin_contact_page_id', 0);
        $post = $id ? get_post($id) : null;
        if (!$post || $post->post_type !== 'page') {
            $id = 0;
            $post = null;
        }

        if (!$post) {
            $posts = get_posts([
                'post_type' => 'page',
                'post_status' => ['publish', 'draft', 'pending', 'private', 'trash'],
                'posts_per_page' => 1,
                'meta_key' => '_stageart_system_content',
                'meta_value' => 'contact',
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);
            $post = $posts[0] ?? null;
        }

        if (!$post) {
            $post = get_page_by_path('contact', OBJECT, 'page');
        }

        if (!$post) {
            $posts = get_posts([
                'post_type' => 'page',
                'post_status' => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 1,
                'title' => '連絡先',
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);
            $post = $posts[0] ?? null;
        }

        if (!$post) {
            $newId = wp_insert_post([
                'post_title' => '連絡先',
                'post_name' => 'contact',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '',
                'meta_input' => ['_stageart_system_content' => 'contact'],
            ], true);
            if (is_wp_error($newId)) return 0;
            $id = (int) $newId;
        } else {
            $id = (int) $post->ID;
            if ($post->post_status === 'trash') {
                wp_untrash_post($id);
            }
            wp_update_post([
                'ID' => $id,
                'post_title' => '連絡先',
                'post_name' => 'contact',
                'post_status' => 'publish',
            ]);
            update_post_meta($id, '_stageart_system_content', 'contact');
        }

        update_option('stageart_plugin_contact_page_id', $id, false);
        return $id;
    }

    public function renderContact(): void
    {
        $id = $this->ensureContactPage();
        $v = static fn($k, $d = '') => get_post_meta($id, '_stageart_contact_' . $k, true) ?: $d;
        echo '<div class="wrap"><h1>連絡先</h1><p>連絡先は独立したシステムコンテンツです。トップページやメニューへの配置は別のコンテンツ配置機能で行います。</p>';
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="stageart_save_contact">';
        wp_nonce_field('stageart_contact');
        echo '<table class="form-table"><tr><th>所在地</th><td><textarea class="large-text" rows="3" name="address">' . esc_textarea($v('address')) . '</textarea></td></tr><tr><th>メールアドレス</th><td><input class="regular-text" type="email" name="email" value="' . esc_attr($v('email')) . '" /></td></tr><tr><th>電話番号</th><td><input class="regular-text" name="phone" value="' . esc_attr($v('phone')) . '" /></td></tr></table>';
        submit_button('保存');
        echo '</form></div>';
    }

    public function saveContact(): void
    {
        $this->guard('stageart_contact');
        $id = $this->ensureContactPage();
        update_post_meta($id, '_stageart_contact_address', sanitize_textarea_field(wp_unslash($_POST['address'] ?? '')));
        update_post_meta($id, '_stageart_contact_email', sanitize_email(wp_unslash($_POST['email'] ?? '')));
        update_post_meta($id, '_stageart_contact_phone', sanitize_text_field(wp_unslash($_POST['phone'] ?? '')));
        wp_safe_redirect(admin_url('admin.php?page=stageart-contact&saved=1'));
        exit;
    }
}
