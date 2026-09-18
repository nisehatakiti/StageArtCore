<?php
declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

final class OrganizationAdmin
{
    public function register(): void
    {
        add_submenu_page(
            'stageart-plugin',
            '団体ページ',
            '団体ページ',
            'manage_options',
            'stageart-organization',
            [$this, 'render']
        );

        // 既存データ管理画面はメニューから隠し、団体ページのコンテンツ管理から引き続き利用できるようにする。
        add_submenu_page(null, '団体基本情報', '団体基本情報', 'manage_options', 'stageart-site-settings', [new SiteSettingsAdmin(), 'render']);
        add_submenu_page(null, '連絡先', '連絡先', 'manage_options', 'stageart-contact', [new SiteSettingsAdmin(), 'renderContact']);
        add_submenu_page(null, 'メンバー', 'メンバー', 'manage_options', 'stageart-members', [new MemberAdmin(), 'render']);
        add_submenu_page(null, '共通項目', '共通項目', 'manage_options', 'stageart-member-fields', [new MemberAdmin(), 'renderFields']);
        add_submenu_page(null, '劇団代表挨拶', '劇団代表挨拶', 'manage_options', 'stageart-representative-greeting', [new RepresentativeGreetingAdmin(), 'render']);
        add_submenu_page(null, '自由コンテンツ', '自由コンテンツ', 'manage_options', 'stageart-free-content', [new FreeContentAdmin(), 'renderList']);
        add_submenu_page(null, 'トップページ設定', 'トップページ設定', 'manage_options', 'stageart-homepage', [new SiteStructureAdmin(), 'renderHomepage']);
        add_submenu_page(null, 'テーマ', 'テーマ', 'manage_options', 'stageart-theme', [new ThemeAdmin(), 'render']);
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) return;

        $tab = sanitize_key((string)($_GET['tab'] ?? 'basic'));
        if (!in_array($tab, ['basic', 'content', 'display'], true)) $tab = 'basic';

        echo '<div class="wrap stageart-organization-admin">';
        echo '<h1>団体ページ</h1>';
        echo '<style>
        .sa-org-tabs{display:flex;gap:0;margin:18px 0 0;border-bottom:1px solid #c3c4c7}
        .sa-org-tab{display:inline-block;padding:10px 18px;border:1px solid #c3c4c7;border-bottom:0;background:#f6f7f7;text-decoration:none;font-weight:600;color:#1d2327}
        .sa-org-tab.sa-active{background:#fff;color:#2271b1;position:relative;bottom:-1px}
        .sa-org-panel{background:#fff;border:1px solid #c3c4c7;border-top:0;padding:20px;min-height:120px}
        .sa-org-card{padding:18px;margin:0 0 14px;border:1px solid #dcdcde;background:#fff}
        .sa-org-card:last-child{margin-bottom:0}
        .sa-org-card h2{margin:0 0 8px;font-size:18px}
        .sa-org-card p{margin:6px 0 12px}
        .sa-org-card .button{margin-right:6px}
        .stageart-organization-admin .wrap{margin-left:0;margin-right:0}
        </style>';

        echo '<nav class="sa-org-tabs">';
        $this->tab('basic', '基本情報', $tab);
        $this->tab('content', 'コンテンツ管理', $tab);
        $this->tab('display', '表示管理', $tab);
        echo '</nav><div class="sa-org-panel">';

        if ($tab === 'basic') {
            (new SiteSettingsAdmin())->render();
        } elseif ($tab === 'content') {
            $this->renderContentTab();
        } else {
            $this->renderDisplayTab();
        }

        echo '</div></div>';
    }

    private function tab(string $key, string $label, string $active): void
    {
        $url = add_query_arg(['page' => 'stageart-organization', 'tab' => $key], admin_url('admin.php'));
        echo '<a class="sa-org-tab'.($active === $key ? ' sa-active' : '').'" href="'.esc_url($url).'">'.esc_html($label).'</a>';
    }

    private function link(string $page, array $args = []): string
    {
        return add_query_arg(array_merge(['page' => $page], $args), admin_url('admin.php'));
    }

    private function renderContentTab(): void
    {
        echo '<p>団体ページで使用するコンテンツを管理します。表示するかどうかは「表示管理 → コンテンツ配置」で設定します。</p>';

        $cards = [
            ['連絡先', '所在地・メールアドレス・電話番号を管理します。', 'stageart-contact', '連絡先を管理'],
            ['共通項目', 'メンバーに共通して持たせる追加項目を管理します。', 'stageart-member-fields', '共通項目を管理'],
            ['メンバー', '団体に所属するメンバーの情報・表示順を管理します。', 'stageart-members', 'メンバーを管理'],
            ['代表挨拶', '団体代表として表示するメンバー・肩書き・挨拶本文を管理します。', 'stageart-representative-greeting', '代表挨拶を管理'],
            ['自由コンテンツ', '団体独自のページコンテンツを管理します。', 'stageart-free-content', '自由コンテンツを管理'],
        ];

        foreach ($cards as [$title, $description, $page, $button]) {
            echo '<section class="sa-org-card"><h2>'.esc_html($title).'</h2><p>'.esc_html($description).'</p><a class="button button-primary" href="'.esc_url($this->link($page)).'">'.esc_html($button).'</a></section>';
        }
    }

    private function renderDisplayTab(): void
    {
        echo '<p>団体ページの見た目と、トップページに表示するコンテンツを管理します。</p>';

        echo '<section class="sa-org-card">';
        echo '<h2>Hero</h2>';
        echo '<p>Heroを表示するかどうかと、背景画像を設定します。</p>';
        echo '<p class="description">下の「コンテンツ配置」フォーム内にHero設定を表示します。</p>';
        echo '</section>';

        echo '<section class="sa-org-card">';
        echo '<h2>テーマ</h2>';
        (new ThemeAdmin())->renderOrganizationForm();
        echo '</section>';

        echo '<section class="sa-org-card">';
        echo '<h2>コンテンツ配置</h2>';
        (new SiteStructureAdmin())->renderHomepage();
        echo '</section>';
    }
}
