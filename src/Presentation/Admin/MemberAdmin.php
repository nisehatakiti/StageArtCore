<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

use StageArtCore\Domain\Member\MemberRepository;
use StageArtCore\Domain\Release\ReleaseDate;

final class MemberAdmin
{
    private MemberRepository $repo;

    public function __construct()
    {
        $this->repo = new MemberRepository();
        add_action('admin_post_stageart_save_member', [$this, 'saveMember']);
        add_action('admin_post_stageart_save_field', [$this, 'saveField']);
        add_action('admin_post_stageart_toggle_field', [$this, 'toggleField']);
        add_action('admin_post_stageart_member_order', [$this, 'saveOrder']);
        add_action('admin_post_stageart_field_order', [$this, 'saveFieldOrder']);
        add_action('admin_footer', [$this, 'releaseControl']);
        add_action('admin_footer', [$this, 'orderScripts']);
    }

    public function register(): void
    {
        add_submenu_page('stageart-plugin', 'メンバー', 'メンバー', 'manage_options', 'stageart-members', [$this, 'render']);
        add_submenu_page('stageart-plugin', '共通項目', '共通項目', 'manage_options', 'stageart-member-fields', [$this, 'renderFields']);
    }

    private function guard(): void
    {
        if (!current_user_can('manage_options')) wp_die('権限がありません。');
        check_admin_referer('stageart_member_action');
    }

    public function render(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $member = $id ? $this->repo->find($id) : null;
        $members = $this->repo->all();
        $fields = $this->repo->fields(true);
        echo '<div class="wrap"><h1>メンバー</h1>';
        if (isset($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        if ($id || isset($_GET['new'])) {
            $this->form($member, $fields);
            echo '</div>';
            return;
        }
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=stageart-members&new=1')) . '">＋ メンバーを追加</a> <a class="button" href="' . esc_url(admin_url('admin.php?page=stageart-member-fields')) . '">共通項目を管理</a></p><h2>メンバー一覧</h2><p>公開状態に関係なく登録済みメンバーを表示順で並べ替えできます。並べ替え後に「表示順を保存」を押してください。</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" id="stageart-member-order-form">';
        wp_nonce_field('stageart_member_action');
        echo '<input type="hidden" name="action" value="stageart_member_order"><input type="hidden" name="ids" id="stageart-member-order-ids" value="">';
        echo '<table class="widefat striped"><thead><tr><th style="width:90px">表示順</th><th>名前</th><th>役割</th><th>状態</th><th>操作</th></tr></thead><tbody id="stageart-member-list">';
        foreach ($members as $m) {
            $full = $this->repo->find((int) $m['id']);
            $roles = $full ? array_map(fn($r) => MemberRepository::ROLES[$r] ?? $r, $full['roles']) : [];
            echo '<tr data-id="' . (int) $m['id'] . '" draggable="true"><td class="sa-member-order-handle" title="ドラッグして並べ替え">☰ <span>' . (int) $m['display_order'] . '</span></td><td><strong><a href="' . esc_url(admin_url('admin.php?page=stageart-members&id=' . (int) $m['id'])) . '">' . esc_html($m['name']) . '</a></strong></td><td>' . esc_html(implode('・', $roles)) . '</td><td>' . esc_html($m['status'] === 'published' ? '公開' : '下書き') . '</td><td><a href="' . esc_url(admin_url('admin.php?page=stageart-members&id=' . (int) $m['id'])) . '">編集</a></td></tr>';
        }
        if (!$members) echo '<tr><td colspan="5">メンバーはまだ登録されていません。</td></tr>';
        echo '</tbody></table>';
        if ($members) submit_button('表示順を保存', 'primary', 'submit', false, ['id' => 'stageart-member-order-submit']);
        echo '</form></div>';
    }

    private function form(?array $m, array $fields): void
    {
        $v = fn($k, $d = '') => esc_attr($m[$k] ?? $d);
        $roles = $m['roles'] ?? [];
        $social = $m['social'] ?? [];
        $custom = $m['custom_fields'] ?? [];
        echo '<h2>' . ($m ? 'メンバーを編集' : 'メンバーを追加') . '</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('stageart_member_action');
        echo '<input type="hidden" name="action" value="stageart_save_member"><input type="hidden" name="id" value="' . (int) ($m['id'] ?? 0) . '"><table class="form-table"><tr><th>名前 *</th><td><input class="regular-text" required name="name" value="' . $v('name') . '" /></td></tr><tr><th>Slug</th><td><input class="regular-text" name="slug" value="' . $v('slug') . '" /><p class="description">未入力の場合は名前から自動生成します。</p></td></tr><tr><th>写真</th><td><input type="number" min="0" name="photo_id" value="' . $v('photo_id') . '" /></td></tr><tr><th>役割</th><td>';
        foreach (MemberRepository::ROLES as $key => $label) echo '<label style="display:block"><input type="checkbox" name="roles[]" value="' . esc_attr($key) . '" ' . checked(in_array($key, $roles, true), true, false) . '> ' . esc_html($label) . '</label>';
        echo '</td></tr><tr><th>プロフィール</th><td><textarea class="large-text" rows="8" name="profile">' . esc_textarea($m['profile'] ?? '') . '</textarea></td></tr><tr><th>SNS</th><td>';
        foreach (MemberRepository::SOCIAL_PLATFORMS as $key => $label) echo '<p><label>' . esc_html($label) . ' <input class="regular-text" type="url" name="social[' . esc_attr($key) . ']" value="' . esc_attr($social[$key] ?? '') . '" /></label></p>';
        echo '</td></tr>';
        if ($fields) {
            echo '<tr><th>劇団共通項目</th><td>';
            foreach ($fields as $f) echo '<p><label><strong>' . esc_html($f['name']) . '</strong><br><textarea class="regular-text" rows="2" name="custom_fields[' . (int) $f['id'] . ']">' . esc_textarea($custom[(int) $f['id']] ?? '') . '</textarea>' . ($f['description'] ? '<br><span class="description">' . esc_html($f['description']) . '</span>' : '') . '</label></p>';
            echo '</td></tr>';
        }
        echo '<tr><th>情報解禁</th><td><input type="datetime-local" name="release_at" value="' . esc_attr(ReleaseDate::fromUtc($m['release_at'] ?? null)) . '" /><p class="description">未設定の場合は公開状態になった時点から公開します。</p></td></tr><tr><th>公開状態</th><td><select name="status"><option value="draft" ' . selected($m['status'] ?? 'draft', 'draft', false) . '>下書き</option><option value="published" ' . selected($m['status'] ?? 'draft', 'published', false) . '>公開</option></select></td></tr></table>';
        submit_button('保存');
        echo ' <a class="button" href="' . esc_url(admin_url('admin.php?page=stageart-members')) . '">キャンセル</a></form>';
    }

    public function saveMember(): void
    {
        $this->guard();
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        if ($name === '') wp_die('名前は必須です。');
        $id = $this->repo->save([
            'id' => (int) ($_POST['id'] ?? 0),
            'name' => $name,
            'slug' => sanitize_title(wp_unslash($_POST['slug'] ?? '')),
            'photo_id' => (int) ($_POST['photo_id'] ?? 0),
            'profile' => wp_kses_post(wp_unslash($_POST['profile'] ?? '')),
            'roles' => array_map('sanitize_key', (array) ($_POST['roles'] ?? [])),
            'social' => array_map('esc_url_raw', (array) ($_POST['social'] ?? [])),
            'custom_fields' => array_map('sanitize_textarea_field', (array) ($_POST['custom_fields'] ?? [])),
            'status' => sanitize_key($_POST['status'] ?? 'draft'),
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'release_at' => ReleaseDate::toUtc(sanitize_text_field(wp_unslash($_POST['release_at'] ?? ''))),
        ]);
        wp_safe_redirect(admin_url('admin.php?page=stageart-members&saved=1'));
        exit;
    }

    public function renderFields(): void
    {
        $fields = $this->repo->fields(false);
        $edit = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
        $current = null;
        foreach ($fields as $f) if ((int) $f['id'] === $edit) $current = $f;
        echo '<div class="wrap"><h1>メンバー共通項目</h1>';
        if (isset($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>';
        echo '<p>劇団全体で共通するメンバー項目を管理します。無効化しても入力済みデータは削除されません。</p><p><a class="button" href="' . esc_url(admin_url('admin.php?page=stageart-member-fields&edit=0')) . '">＋ 項目を追加</a></p><table class="widefat striped"><thead><tr><th>表示順</th><th>項目名</th><th>状態</th><th>操作</th></tr></thead><tbody>';
        foreach ($fields as $f) {
            $active = (bool) $f['is_active'];
            $action = wp_nonce_url(admin_url('admin-post.php?action=stageart_toggle_field&id=' . (int) $f['id']), 'stageart_member_action');
            echo '<tr><td>☰ ' . (int) $f['display_order'] . '</td><td><strong>' . esc_html($f['name']) . '</strong><br><span class="description">' . esc_html($f['description']) . '</span></td><td>' . ($active ? '有効' : '無効') . '</td><td><a href="' . esc_url(admin_url('admin.php?page=stageart-member-fields&edit=' . (int) $f['id'])) . '">編集</a> | <a href="' . esc_url($action) . '">' . ($active ? '無効化' : '再利用') . '</a></td></tr>';
        }
        if (!$fields) echo '<tr><td colspan="4">共通項目はまだありません。</td></tr>';
        echo '</tbody></table>';
        if (isset($_GET['edit'])) {
            echo '<hr><h2>' . ($current ? '項目を編集' : '項目を追加') . '</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('stageart_member_action');
            echo '<input type="hidden" name="action" value="stageart_save_field"><input type="hidden" name="id" value="' . (int) ($current['id'] ?? 0) . '"><table class="form-table"><tr><th>項目名 *</th><td><input class="regular-text" required name="name" value="' . esc_attr($current['name'] ?? '') . '" /></td></tr><tr><th>説明</th><td><textarea class="large-text" rows="3" name="description">' . esc_textarea($current['description'] ?? '') . '</textarea></td></tr></table>';
            submit_button('保存');
            echo '</form>';
        }
        echo '</div>';
    }

    public function saveField(): void
    {
        $this->guard();
        $id = $this->repo->saveField(['id' => (int) ($_POST['id'] ?? 0), 'name' => wp_unslash($_POST['name'] ?? ''), 'description' => wp_unslash($_POST['description'] ?? '')]);
        wp_safe_redirect(admin_url('admin.php?page=stageart-member-fields&saved=1'));
        exit;
    }

    public function toggleField(): void
    {
        $this->guard();
        $id = (int) ($_GET['id'] ?? 0);
        foreach ($this->repo->fields(false) as $f) if ((int) $f['id'] === $id) { $this->repo->setFieldState($id, !((bool) $f['is_active'])); break; }
        wp_safe_redirect(admin_url('admin.php?page=stageart-member-fields&saved=1'));
        exit;
    }

    public function saveOrder(): void
    {
        $this->guard();
        $this->repo->setOrder((array) ($_POST['ids'] ?? []));
        wp_safe_redirect(admin_url('admin.php?page=stageart-members&saved=1'));
        exit;
    }

    public function saveFieldOrder(): void
    {
        $this->guard();
        $this->repo->setFieldOrder((array) ($_POST['ids'] ?? []));
        wp_safe_redirect(admin_url('admin.php?page=stageart-member-fields&saved=1'));
        exit;
    }

    public function releaseControl(): void
    {
        if (!is_admin() || ($_GET['page'] ?? '') !== 'stageart-members' || (!isset($_GET['id']) && !isset($_GET['new']))) return;
        echo '<script>(function(){function init(){var form=document.querySelector("form[action*=admin-post]");if(!form)return;var release=form.querySelector("input[name=release_at]");if(!release||release.dataset.saReleaseInit)return;release.dataset.saReleaseInit="1";var cell=release.parentNode;var toggle=document.createElement("input");toggle.type="checkbox";toggle.className="sa-release-toggle";toggle.checked=release.value!=="";var label=document.createElement("label");label.appendChild(toggle);label.appendChild(document.createTextNode(" 情報解禁日を設定する"));cell.insertBefore(label,release);cell.insertBefore(document.createElement("br"),release);release.disabled=!toggle.checked;toggle.addEventListener("change",function(){release.disabled=!toggle.checked;if(toggle.checked)release.focus();});}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();})();</script>';
    }

    public function orderScripts(): void
    {
        if (!is_admin() || ($_GET['page'] ?? '') !== 'stageart-members' || isset($_GET['id']) || isset($_GET['new'])) return;
        echo '<style>#stageart-member-list tr{cursor:grab}#stageart-member-list tr.sa-dragging{opacity:.45}.sa-member-order-handle{font-weight:600;white-space:nowrap}#stageart-member-order-submit{margin-top:12px}</style><script>(function(){function init(){var body=document.getElementById("stageart-member-list"),hidden=document.getElementById("stageart-member-order-ids");if(!body||!hidden)return;var drag=null;function rows(){return Array.prototype.slice.call(body.querySelectorAll("tr[data-id]"));}function sync(){var r=rows();hidden.value=r.map(function(x){return x.getAttribute("data-id");}).join(",");r.forEach(function(x,i){var s=x.querySelector(".sa-member-order-handle span");if(s)s.textContent=String(i+1);});}body.addEventListener("dragstart",function(e){var row=e.target.closest("tr[data-id]");if(!row)return;drag=row;row.classList.add("sa-dragging");e.dataTransfer.effectAllowed="move";});body.addEventListener("dragend",function(){if(drag)drag.classList.remove("sa-dragging");drag=null;sync();});body.addEventListener("dragover",function(e){if(!drag)return;e.preventDefault();var row=e.target.closest("tr[data-id]");if(!row||row===drag)return;var rect=row.getBoundingClientRect();if(e.clientY<rect.top+rect.height/2)body.insertBefore(drag,row);else body.insertBefore(drag,row.nextSibling);});sync();}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();})();</script>';
    }
}
