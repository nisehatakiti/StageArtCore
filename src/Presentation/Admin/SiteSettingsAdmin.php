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
        wp_enqueue_media();
        $v = static fn($k, $d = '') => get_option('stageart_org_' . $k, $d);
        echo '<div class="wrap"><h1>団体基本情報</h1>';
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="stageart_save_site_settings">';
        wp_nonce_field('stageart_site_settings');
        echo '<table class="form-table"><tr><th>団体名 *</th><td><input class="regular-text" required name="name" value="' . esc_attr($v('name')) . '" /></td></tr><tr><th>団体ロゴ</th><td><div id="stageart-logo-preview">';$logoId=(int)get_option('stageart_org_logo_id',0);if($logoId)echo wp_get_attachment_image($logoId,'medium',['style'=>'max-width:320px;height:auto;display:block;margin-bottom:8px']);echo '</div><input type="hidden" name="logo_id" id="stageart-logo-id" value="'.esc_attr((string)$logoId).'" /><button type="button" class="button" id="stageart-select-logo">画像を選択</button> <button type="button" class="button" id="stageart-remove-logo">ロゴを削除</button><p class="description">サイトヘッダーなどで使用する団体ロゴを設定できます。</p></td></tr><tr><th>団体紹介</th><td><textarea class="large-text" rows="8" name="description">' . esc_textarea($v('description')) . '</textarea></td></tr>';
        foreach (['x' => ['label'=>'X','placeholder'=>'https://x.com/your-account'], 'instagram' => ['label'=>'Instagram','placeholder'=>'https://www.instagram.com/your-account/'], 'youtube' => ['label'=>'YouTube','placeholder'=>'https://www.youtube.com/@your-channel'], 'facebook' => ['label'=>'Facebook','placeholder'=>'https://www.facebook.com/your-page']] as $k => $info) {
            echo '<tr><th>' . esc_html($info['label']) . '</th><td><input class="regular-text" type="url" name="' . $k . '" value="' . esc_attr($v($k)) . '" placeholder="' . esc_attr($info['placeholder']) . '" /><p class="description">例：' . esc_html($info['placeholder']) . '</p></td></tr>';
        }
        echo '</table><script>(function($){$(function(){var frame;$(\'#stageart-select-logo\').on(\'click\',function(e){e.preventDefault();if(frame){frame.open();return;}frame=wp.media({title:\'団体ロゴを選択\',button:{text:\'この画像を使用\'},multiple:false,library:{type:\'image\'}});frame.on(\'select\',function(){var a=frame.state().get(\'selection\').first().toJSON();$(\'#stageart-logo-id\').val(a.id);var u=a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url;$(\'#stageart-logo-preview\').html(\'<img src="\'+u.replace(/"/g,"&quot;")+\'" style="max-width:320px;height:auto;display:block;margin-bottom:8px">\');});frame.open();});$(\'#stageart-remove-logo\').on(\'click\',function(){$(\'#stageart-logo-id\').val(0);$(\'#stageart-logo-preview\').empty();});});})(jQuery);</script>';
        submit_button('保存');
        echo '</form></div>';
    }

    public function save(): void
    {
        $this->guard('stageart_site_settings');
        update_option('stageart_org_logo_id', max(0, (int)($_POST['logo_id'] ?? 0)), false);
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
