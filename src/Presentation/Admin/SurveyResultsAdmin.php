<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;
use StageArtCore\Domain\Survey\SurveyRepository;

final class SurveyResultsAdmin
{
    private SurveyRepository $repo;

    public function __construct()
    {
        $this->repo = new SurveyRepository();
    }

    public function register(): void
    {
        add_submenu_page(null, 'アンケート結果', 'アンケート結果', 'manage_options', 'stageart-survey-results', [$this, 'render']);
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        $sid = (int) ($_GET['survey_id'] ?? 0);
        $survey = $this->findSurvey($sid);
        if (!$survey) {
            wp_die('アンケートが指定されていません。');
        }
        $production = get_post((int) $survey['production_id']);
        if (!$production) {
            wp_die('公演が見つかりません。');
        }
        $questions = $this->repo->questions($sid);
        $responses = $this->repo->responses($sid);
        $performances = $this->repo->performancesForSurvey((int) $production->ID);
        $pn = [];
        foreach ($performances as $performance) {
            $pn[(int) $performance['id']] = $performance['performance_date'] . ' ' . substr((string) $performance['start_time'], 0, 5);
        }
        if (isset($_GET['print'])) {
            $this->printPage($survey, $production, $questions, $responses, $pn);
            return;
        }
        echo '<div class="wrap"><h1>アンケート結果</h1><p><strong>公演：</strong>' . esc_html($production->post_title) . '　<strong>回答数：</strong>' . count($responses) . '件</p>';
        echo '<p><a class="button button-primary" target="_blank" href="' . esc_url(admin_url('admin.php?page=stageart-survey-results&survey_id=' . $sid . '&print=1')) . '">集計結果を印刷</a></p>';
        foreach ($questions as $question) {
            $this->summary($question, $responses);
        }
        echo '<h2>回答一覧</h2><table class="widefat striped"><thead><tr><th>日時</th><th>公演回</th>';
        foreach ($questions as $question) {
            echo '<th>' . esc_html($question['label']) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($responses as $response) {
            echo '<tr><td>' . esc_html($response['submitted_at']) . '</td><td>' . esc_html($pn[(int) $response['performance_id']] ?? '未選択') . '</td>';
            foreach ($questions as $question) {
                $value = $response['answers'][(string) $question['id']] ?? '';
                echo '<td>' . esc_html(is_array($value) ? implode('、', $value) : (string) $value) . '</td>';
            }
            echo '</tr>';
        }
        if (!$responses) {
            echo '<tr><td colspan="' . (2 + count($questions)) . '">回答はまだありません。</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    private function findSurvey(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'stageart_plugin_production_surveys';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $id), ARRAY_A);
        return $row ?: null;
    }

    private function summary(array $question, array $responses): void
    {
        $type = $question['type'];
        echo '<div class="postbox" style="padding:12px;margin:15px 0"><h2>' . esc_html($question['label']) . '</h2>';
        if (in_array($type, ['radio', 'select', 'rating', 'checkbox'], true)) {
            $counts = [];
            foreach ((array) $question['options'] as $option) {
                $counts[(string) $option] = 0;
            }
            if ($type === 'rating') {
                for ($n = 1; $n <= 5; $n++) {
                    $counts[(string) $n] = 0;
                }
            }
            foreach ($responses as $response) {
                $value = $response['answers'][(string) $question['id']] ?? [];
                $values = $type === 'checkbox' ? (array) $value : [$value];
                foreach ($values as $answer) {
                    if (isset($counts[(string) $answer])) {
                        $counts[(string) $answer]++;
                    }
                }
            }
            echo '<table class="widefat"><tr><th>回答</th><th>件数</th></tr>';
            foreach ($counts as $answer => $count) {
                echo '<tr><td>' . esc_html($answer) . '</td><td>' . (int) $count . '</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p>自由回答 ' . count($responses) . '件</p>';
        }
        echo '</div>';
    }

    private function printPage(array $survey, \WP_Post $production, array $questions, array $responses, array $pn): void
    {
        echo '<!doctype html><html><head><meta charset="utf-8"><title>アンケート集計</title><style>body{font-family:sans-serif;margin:30px}table{border-collapse:collapse;width:100%;margin-bottom:25px}th,td{border:1px solid #999;padding:6px;text-align:left}section{break-inside:avoid}@media print{button{display:none}}</style></head><body>';
        echo '<h1>' . esc_html($survey['title']) . '</h1><p>' . esc_html($production->post_title) . '　回答数：' . count($responses) . '件</p>';
        foreach ($questions as $question) {
            echo '<section><h2>' . esc_html($question['label']) . '</h2>';
            if (in_array($question['type'], ['radio', 'select', 'rating', 'checkbox'], true)) {
                $counts = [];
                foreach ((array) $question['options'] as $option) {
                    $counts[(string) $option] = 0;
                }
                if ($question['type'] === 'rating') {
                    for ($n = 1; $n <= 5; $n++) {
                        $counts[(string) $n] = 0;
                    }
                }
                foreach ($responses as $response) {
                    $value = $response['answers'][(string) $question['id']] ?? [];
                    $values = $question['type'] === 'checkbox' ? (array) $value : [$value];
                    foreach ($values as $answer) {
                        if (isset($counts[(string) $answer])) {
                            $counts[(string) $answer]++;
                        }
                    }
                }
                echo '<table><tr><th>回答</th><th>件数</th></tr>';
                foreach ($counts as $answer => $count) {
                    echo '<tr><td>' . esc_html($answer) . '</td><td>' . (int) $count . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<table><tr><th>回答</th><th>公演回</th></tr>';
                foreach ($responses as $response) {
                    $value = $response['answers'][(string) $question['id']] ?? '';
                    echo '<tr><td>' . esc_html(is_array($value) ? implode('、', $value) : (string) $value) . '</td><td>' . esc_html($pn[(int) $response['performance_id']] ?? '未選択') . '</td></tr>';
                }
                echo '</table>';
            }
            echo '</section>';
        }
        echo '<button onclick="window.print()">印刷</button></body></html>';
        exit;
    }
}
