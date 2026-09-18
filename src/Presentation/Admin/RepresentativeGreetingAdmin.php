<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

use StageArtCore\Domain\Member\MemberRepository;
use StageArtCore\Domain\Release\ReleaseDate;
use StageArtCore\Domain\Site\RepresentativeGreeting;

final class RepresentativeGreetingAdmin
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu'], 22);
        add_action('admin_post_stageart_save_representative_greeting', [$this, 'save']);
    }

    public function registerMenu(): void
    {
        // 団体ページの「コンテンツ管理」から編集します。
    }

    private function guard(): void
    {
        if (!current_user_can('manage_options')) wp_die('権限がありません。');
        check_admin_referer('stageart_representative_greeting');
    }

    public function render(): void
    {
        $v = RepresentativeGreeting::get();
        $members = (new MemberRepository())->all(false);
        echo '<div class="wrap"><h1>劇団代表挨拶</h1>';
        if (isset($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        echo '<p>対象メンバーの写真・名前・メンバーページへのリンクはメンバー情報から自動取得します。ここでは表示肩書き、挨拶本文、情報解禁を管理します。</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('stageart_representative_greeting');
        echo '<input type="hidden" name="action" value="stageart_save_representative_greeting"><table class="form-table"><tr><th>表示肩書き</th><td><input class="regular-text" name="label" value="' . esc_attr($v['label'] ?: '劇団代表') . '" placeholder="劇団代表"></td></tr><tr><th>対象メンバー</th><td><select name="member_id"><option value="0">選択してください</option>';
        foreach ($members as $m) echo '<option value="' . (int) $m['id'] . '" ' . selected($v['member_id'], (int) $m['id'], false) . '>' . esc_html($m['name']) . '</option>';
        echo '</select></td></tr><tr><th>挨拶本文</th><td><textarea class="large-text" rows="12" name="body">' . esc_textarea($v['body']) . '</textarea></td></tr><tr><th>情報解禁</th><td><input type="datetime-local" name="release_at" value="' . esc_attr(ReleaseDate::fromUtc($v['release_at'])) . '"><p class="description">チェックを外して保存すると、劇団代表挨拶は公開状態になった時点から公開します。</p></td></tr></table>';
        submit_button('保存');
        echo '</form><script>(function(){var input=document.querySelector("input[name=release_at]");if(!input)return;var toggle=document.createElement("input");toggle.type="checkbox";toggle.checked=input.value!=="";var label=document.createElement("label");label.appendChild(toggle);label.appendChild(document.createTextNode(" 情報解禁日を設定する"));input.parentNode.insertBefore(label,input);input.parentNode.insertBefore(document.createElement("br"),input);input.disabled=!toggle.checked;toggle.addEventListener("change",function(){input.disabled=!toggle.checked;if(toggle.checked)input.focus();});})();</script></div>';
    }

    public function save(): void
    {
        $this->guard();
        RepresentativeGreeting::save((int) ($_POST['member_id'] ?? 0), wp_unslash($_POST['body'] ?? ''), ReleaseDate::toUtc(sanitize_text_field(wp_unslash($_POST['release_at'] ?? ''))), sanitize_text_field(wp_unslash($_POST['label'] ?? '劇団代表')));
        wp_safe_redirect(admin_url('admin.php?page=stageart-representative-greeting&saved=1'));
        exit;
    }
}
