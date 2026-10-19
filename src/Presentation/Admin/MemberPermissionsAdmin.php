<?php
declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

use StageArtCore\Infrastructure\AuthCoreBridge;

final class MemberPermissionsAdmin
{
    private const ROLES = [
        'organization_admin' => '団体管理者',
        'manager' => '運営スタッフ',
        'member' => '一般メンバー',
    ];

    public function register(): void
    {
        add_submenu_page(
            'stageart-plugin',
            'ユーザー権限',
            'ユーザー権限',
            'manage_options',
            'stageart-user-permissions',
            [$this, 'render']
        );
        add_action('admin_post_stageart_save_user_permissions', [$this, 'save']);
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        check_admin_referer('stageart_user_permissions');

        if (!AuthCoreBridge::available()) {
            wp_safe_redirect(add_query_arg(
                ['page' => 'stageart-user-permissions', 'error' => 'authcore_unavailable'],
                admin_url('admin.php')
            ));
            exit;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_member_permissions';
        $accounts = AuthCoreBridge::accounts();
        $knownIds = array_map(static fn(array $account): int => (int) $account['user_account_id'], $accounts);
        $roles = (array) ($_POST['role'] ?? []);
        $now = current_time('mysql');

        foreach ($roles as $rawId => $rawRole) {
            $userId = absint($rawId);
            $role = sanitize_key(wp_unslash((string) $rawRole));
            if (!$userId || !in_array($userId, $knownIds, true) || !isset(self::ROLES[$role])) {
                continue;
            }

            $existing = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT authcore_user_id FROM {$table} WHERE authcore_user_id = %d",
                $userId
            ));

            if ($existing) {
                $result = $wpdb->update(
                    $table,
                    ['role_key' => $role, 'updated_at' => $now],
                    ['authcore_user_id' => $userId],
                    ['%s', '%s'],
                    ['%d']
                );
            } else {
                $result = $wpdb->insert(
                    $table,
                    [
                        'authcore_user_id' => $userId,
                        'role_key' => $role,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['%d', '%s', '%s', '%s']
                );
            }

            if ($result === false) {
                wp_safe_redirect(add_query_arg(
                    ['page' => 'stageart-user-permissions', 'error' => 'save_failed'],
                    admin_url('admin.php')
                ));
                exit;
            }
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'stageart-user-permissions', 'saved' => '1'],
            admin_url('admin.php')
        ));
        exit;
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }

        echo '<div class="wrap"><h1>ユーザー権限</h1>';
        echo '<p>AuthCoreのStageArtアプリに登録されているユーザーを確認し、StageArt内での権限区分を設定します。</p>';

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>権限設定を保存しました。</p></div>';
        }
        if (isset($_GET['error'])) {
            $messages = [
                'authcore_unavailable' => 'AuthCoreが有効ではないか、認証エンジンを読み込めません。',
                'save_failed' => '権限設定を保存できませんでした。データベース構造を確認してください。',
            ];
            $key = sanitize_key(wp_unslash((string) $_GET['error']));
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($messages[$key] ?? '処理に失敗しました。') . '</p></div>';
        }

        if (!AuthCoreBridge::available()) {
            echo '<div class="notice notice-warning"><p>AuthCoreを有効にすると、StageArtアプリのユーザー一覧と権限設定を利用できます。AuthCoreが無効でもStageArtCoreの通常のメンバー管理には影響しません。</p></div></div>';
            return;
        }

        global $wpdb;
        $users = AuthCoreBridge::accounts();
        $permissionsTable = $wpdb->prefix . 'stageart_plugin_member_permissions';
        $membersTable = $wpdb->prefix . 'stageart_plugin_members';
        $permissions = $wpdb->get_results("SELECT authcore_user_id, role_key FROM {$permissionsTable}", ARRAY_A) ?: [];
        $roleByUser = [];
        foreach ($permissions as $permission) {
            $roleByUser[(int) $permission['authcore_user_id']] = (string) $permission['role_key'];
        }

        $members = $wpdb->get_results(
            "SELECT id, name, authcore_user_id FROM {$membersTable} WHERE authcore_user_id IS NOT NULL AND authcore_user_id > 0",
            ARRAY_A
        ) ?: [];
        $memberByUser = [];
        foreach ($members as $member) {
            $memberByUser[(int) $member['authcore_user_id']] = $member;
        }

        $linkedCount = 0;
        foreach ($users as $user) {
            if (isset($memberByUser[(int) $user['user_account_id']])) {
                $linkedCount++;
            }
        }

        echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0">';
        echo '<div class="card" style="min-width:150px;margin:0"><h2>' . count($users) . '</h2><p>AuthCoreユーザー</p></div>';
        echo '<div class="card" style="min-width:150px;margin:0"><h2>' . $linkedCount . '</h2><p>メンバー連携済み</p></div>';
        echo '<div class="card" style="min-width:150px;margin:0"><h2>' . (count($users) - $linkedCount) . '</h2><p>メンバー未連携</p></div>';
        echo '</div>';

        if (!$users) {
            echo '<div class="notice notice-info"><p>StageArt用のAuthCoreユーザーはまだ登録されていません。メンバー編集画面からアカウントを作成すると、ここに表示されます。</p></div></div>';
            return;
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('stageart_user_permissions');
        echo '<input type="hidden" name="action" value="stageart_save_user_permissions">';
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>ログインID</th><th>メールアドレス</th><th>StageArtメンバー</th><th>アカウント状態</th><th>最終ログイン</th><th>権限</th></tr></thead><tbody>';

        foreach ($users as $user) {
            $userId = (int) $user['user_account_id'];
            $member = $memberByUser[$userId] ?? null;
            $role = $roleByUser[$userId] ?? 'member';
            if (!isset(self::ROLES[$role])) {
                $role = 'member';
            }

            echo '<tr>';
            echo '<td>' . $userId . '</td>';
            echo '<td><strong>' . esc_html((string) $user['login_id']) . '</strong></td>';
            echo '<td>' . esc_html((string) $user['email']) . '</td>';
            echo '<td>';
            if ($member) {
                echo '<a href="' . esc_url(admin_url('admin.php?page=stageart-members&id=' . (int) $member['id'])) . '">' . esc_html((string) $member['name']) . '</a>';
            } else {
                echo '<span style="color:#996800">未連携</span>';
            }
            echo '</td>';
            echo '<td>' . esc_html((string) $user['status']) . '</td>';
            echo '<td>' . esc_html($user['last_login_at'] ? (string) $user['last_login_at'] : 'ログイン履歴なし') . '</td>';
            echo '<td><select name="role[' . $userId . ']">';
            foreach (self::ROLES as $key => $label) {
                echo '<option value="' . esc_attr($key) . '" ' . selected($role, $key, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        submit_button('権限設定を保存');
        echo '</form>';
        echo '<p class="description">権限区分はStageArtCore側に保存されます。各画面・操作へのアクセス制御は、メンバー向け機能の実装時にこの区分を参照して適用します。</p>';
        echo '</div>';
    }
}
