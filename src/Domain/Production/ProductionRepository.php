<?php

declare(strict_types=1);

namespace StageArtCore\Domain\Production;

use StageArtCore\Domain\Release\ReleaseDate;

final class ProductionRepository
{
    private \wpdb $db;
    private string $participants;
    private string $performances;
    private string $labels;
    private string $tickets;
    private string $slugs;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $p = $this->db->prefix;
        $this->participants = $p . 'stageart_plugin_production_participants';
        $this->performances = $p . 'stageart_plugin_production_performances';
        $this->labels = $p . 'stageart_plugin_performance_labels';
        $this->tickets = $p . 'stageart_plugin_production_ticket_prices';
        $this->slugs = $p . 'stageart_plugin_production_slug_history';
    }

    public function participants(int $productionId, string $kind = ''): array
    {
        $sql = "SELECT * FROM {$this->participants} WHERE production_id=%d";
        $a = [$productionId];
        if ($kind !== '') { $sql .= ' AND kind=%s'; $a[] = $kind; }
        $sql .= ' ORDER BY display_order,id';
        $r = $this->db->get_results($this->db->prepare($sql, ...$a), ARRAY_A) ?: [];
        foreach ($r as &$x) {
            $x['id'] = (int) $x['id'];
            $x['member_id'] = $x['member_id'] !== null ? (int) $x['member_id'] : null;
            $x['auth_user_id'] = $x['auth_user_id'] !== null ? (int) $x['auth_user_id'] : null;
        }
        return $r;
    }

    public function performances(int $id): array
    {
        $r = $this->db->get_results($this->db->prepare("SELECT p.*,l.symbol,l.name AS label_name FROM {$this->performances} p LEFT JOIN {$this->labels} l ON l.id=p.label_id WHERE p.production_id=%d ORDER BY p.performance_date,p.start_time,p.id", $id), ARRAY_A) ?: [];
        foreach ($r as &$x) {
            $x['id'] = (int) $x['id'];
            $x['label_id'] = $x['label_id'] !== null ? (int) $x['label_id'] : null;
        }
        return $r;
    }

    public function labels(int $id): array
    {
        $r = $this->db->get_results($this->db->prepare("SELECT * FROM {$this->labels} WHERE production_id=%d ORDER BY display_order,id", $id), ARRAY_A) ?: [];
        foreach ($r as &$x) $x['id'] = (int) $x['id'];
        return $r;
    }

    public function tickets(int $id): array
    {
        $r = $this->db->get_results($this->db->prepare("SELECT * FROM {$this->tickets} WHERE production_id=%d ORDER BY display_order,id", $id), ARRAY_A) ?: [];
        foreach ($r as &$x) {
            $x['id'] = (int) $x['id'];
            $x['amount'] = (int) $x['amount'];
            $x['show_on_reservation'] = (bool) $x['show_on_reservation'];
        }
        return $r;
    }

    public function saveParticipants(int $id, string $kind, array $rows): void
    {
        global $wpdb;
        $old = $this->participants($id, $kind);
        $keep = [];
        $now = gmdate('Y-m-d H:i:s');
        foreach (array_values($rows) as $i => $r) {
            $name = sanitize_text_field((string) ($r['name'] ?? ''));
            if ($name === '') continue;
            $rid = (int) ($r['id'] ?? 0);
            $data = [
                'kind' => $kind,
                'name' => $name,
                'role' => sanitize_text_field((string) ($r['role'] ?? '')),
                'member_id' => !empty($r['member_id']) ? (int) $r['member_id'] : null,
                'auth_user_id' => !empty($r['auth_user_id']) ? (int) $r['auth_user_id'] : null,
                'display_order' => $i,
                'updated_at' => $now,
                'production_id' => $id,
            ];
            if ($rid > 0) {
                $wpdb->update($this->participants, $data, ['id' => $rid, 'production_id' => $id, 'kind' => $kind], ['%s','%s','%s','%d','%d','%s','%d','%s'], ['%d','%d','%s']);
            } else {
                $data['created_at'] = $now;
                $wpdb->insert($this->participants, $data, ['%s','%s','%s','%d','%d','%d','%s','%d','%s']);
                $rid = (int) $wpdb->insert_id;
            }
            if ($rid > 0) $keep[] = $rid;
        }
        foreach ($old as $x) if (!in_array((int) $x['id'], $keep, true)) $wpdb->delete($this->participants, ['id' => (int) $x['id'], 'production_id' => $id], ['%d','%d']);
    }

    public function saveLabels(int $id, array $rows, array $deletedIds = []): array
    {
        $old = $this->labels($id);
        $oldById = [];
        foreach ($old as $label) $oldById[(int) $label['id']] = $label;
        $newIds = [];
        $now = gmdate('Y-m-d H:i:s');
        foreach ($rows as $rowKey => $r) {
            if (!is_array($r)) continue;
            $symbol = sanitize_text_field((string) ($r['symbol'] ?? ''));
            if ($symbol === '') continue;
            $rid = (int) ($r['id'] ?? 0);
            $name = sanitize_text_field((string) ($r['name'] ?? ($oldById[$rid]['name'] ?? '')));
            $order = count($newIds);
            if ($rid > 0 && isset($oldById[$rid])) {
                $ok = $this->db->update($this->labels, ['symbol'=>$symbol,'name'=>$name,'display_order'=>$order,'updated_at'=>$now], ['id'=>$rid,'production_id'=>$id], ['%s','%s','%d','%s'], ['%d','%d']);
                if ($ok === false) wp_die('公演スケジュールラベルの保存に失敗しました。');
            } else {
                $ok = $this->db->insert($this->labels, ['production_id'=>$id,'symbol'=>$symbol,'name'=>$name,'display_order'=>$order,'created_at'=>$now,'updated_at'=>$now], ['%d','%s','%s','%d','%s','%s']);
                if ($ok === false) wp_die('公演スケジュールラベルの追加に失敗しました。');
                $rid = (int) $this->db->insert_id;
            }
            if ($rid > 0) $newIds[(string) $rowKey] = $rid;
        }
        $deleted = [];
        foreach ($deletedIds as $deletedId) { $rid=(int)$deletedId; if($rid>0)$deleted[$rid]=true; }
        foreach ($old as $x) {
            $rid=(int)$x['id'];
            if(!isset($deleted[$rid])) continue;
            $this->db->query($this->db->prepare("UPDATE {$this->performances} SET label_id=NULL WHERE production_id=%d AND label_id=%d",$id,$rid));
            $this->db->delete($this->labels,['id'=>$rid,'production_id'=>$id],['%d','%d']);
        }
        if ($newIds) {
            $savedById = [];
            foreach ($this->labels($id) as $label) $savedById[(int) $label['id']] = $label;
            foreach ($rows as $rowKey => $r) {
                $rid = (int) ($newIds[(string) $rowKey] ?? 0);
                if ($rid <= 0) continue;
                $expectedSymbol = sanitize_text_field((string) ($r['symbol'] ?? ''));
                $expectedName = sanitize_text_field((string) ($r['name'] ?? ''));
                $actual = $savedById[$rid] ?? null;
                if (!$actual || (string) ($actual['symbol'] ?? '') !== $expectedSymbol || (string) ($actual['name'] ?? '') !== $expectedName) {
                    $this->db->query($this->db->prepare("UPDATE {$this->labels} SET symbol=%s,name=%s,updated_at=%s WHERE id=%d AND production_id=%d", $expectedSymbol, $expectedName, $now, $rid, $id));
                }
            }
            $savedById = [];
            foreach ($this->labels($id) as $label) $savedById[(int) $label['id']] = $label;
            foreach ($rows as $rowKey => $r) {
                $rid = (int) ($newIds[(string) $rowKey] ?? 0);
                if ($rid <= 0) continue;
                $expectedSymbol = sanitize_text_field((string) ($r['symbol'] ?? ''));
                $expectedName = sanitize_text_field((string) ($r['name'] ?? ''));
                $actual = $savedById[$rid] ?? null;
                if (!$actual || (string) ($actual['symbol'] ?? '') !== $expectedSymbol || (string) ($actual['name'] ?? '') !== $expectedName) wp_die('公演スケジュールラベルの保存結果を確認できませんでした。');
            }
        }
        return $newIds;
    }

    public function savePerformances(int $id, array $rows): void
    {
        // STAGEART_LABEL_SAVE_VERIFIED: persist and verify label_id explicitly.
        $old = $this->performances($id);
        $oldById = [];
        foreach ($old as $x) $oldById[(int) $x['id']] = $x;
        $validLabels = [];
        foreach ($this->labels($id) as $l) $validLabels[(int) $l['id']] = true;
        $keep = [];
        $now = gmdate('Y-m-d H:i:s');

        foreach (array_values($rows) as $i => $r) {
            $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($r['date'] ?? '')) ? (string) $r['date'] : '';
            $start = preg_match('/^\d{2}:\d{2}$/', (string) ($r['start'] ?? '')) ? (string) $r['start'] : '';
            if (!$date || !$start) continue;
            $end = preg_match('/^\d{2}:\d{2}$/', (string) ($r['end'] ?? '')) ? (string) $r['end'] : null;
            $rid = (int) ($r['id'] ?? 0);

            $label = null;
            if (array_key_exists('label_id', $r)) {
                $rawLabel = is_scalar($r['label_id']) ? trim((string) $r['label_id']) : '';
                if ($rawLabel !== '' && ctype_digit($rawLabel)) {
                    $candidate = (int) $rawLabel;
                    if (isset($validLabels[$candidate])) $label = $candidate;
                }
            } elseif ($rid > 0 && isset($oldById[$rid]) && $oldById[$rid]['label_id'] !== null) {
                $existing = (int) $oldById[$rid]['label_id'];
                $label = isset($validLabels[$existing]) ? $existing : null;
            }

            $release = isset($r['release_at'])
                ? ReleaseDate::toUtc(sanitize_text_field((string) $r['release_at']))
                : ($rid > 0 && isset($oldById[$rid]) ? $oldById[$rid]['release_at'] : null);

            if ($rid > 0) {
                $where = $this->db->prepare('id=%d AND production_id=%d', $rid, $id);
                $labelSql = $label === null ? 'NULL' : (string) $label;
                $sql = $this->db->prepare(
                    "UPDATE {$this->performances} SET performance_date=%s,start_time=%s,end_time=%s,label_id={$labelSql},release_at=%s,updated_at=%s WHERE {$where}",
                    $date, $start, $end, $release, $now
                );
                $result = $this->db->query($sql);
                if ($result === false) wp_die('公演スケジュールの保存に失敗しました。DB更新エラー: ' . esc_html((string) $this->db->last_error));

                $actual = $this->db->get_row($this->db->prepare("SELECT label_id FROM {$this->performances} WHERE id=%d AND production_id=%d", $rid, $id), ARRAY_A);
                $actualLabel = ($actual && $actual['label_id'] !== null) ? (int) $actual['label_id'] : null;
                if ($actualLabel !== $label) {
                    wp_die('公演スケジュールのラベル保存を確認できませんでした。');
                }
            } else {
                $labelSql = $label === null ? 'NULL' : (string) $label;
                $sql = $this->db->prepare(
                    "INSERT INTO {$this->performances} (production_id,performance_date,start_time,end_time,label_id,release_at,created_at,updated_at) VALUES (%d,%s,%s,%s,{$labelSql},%s,%s,%s)",
                    $id, $date, $start, $end, $release, $now, $now
                );
                $result = $this->db->query($sql);
                if ($result === false) wp_die('公演スケジュールの追加に失敗しました。DB更新エラー: ' . esc_html((string) $this->db->last_error));
                $rid = (int) $this->db->insert_id;
                if ($rid > 0) {
                    $actual = $this->db->get_row($this->db->prepare("SELECT label_id FROM {$this->performances} WHERE id=%d AND production_id=%d", $rid, $id), ARRAY_A);
                    $actualLabel = ($actual && $actual['label_id'] !== null) ? (int) $actual['label_id'] : null;
                    if ($actualLabel !== $label) wp_die('公演スケジュールのラベル保存を確認できませんでした。');
                }
            }
            if ($rid > 0) $keep[] = $rid;
        }

        foreach ($old as $x) {
            $rid = (int) $x['id'];
            if (!in_array($rid, $keep, true)) $this->db->delete($this->performances, ['id'=>$rid,'production_id'=>$id], ['%d','%d']);
        }
    }

    public function saveTickets(int $id, array $rows): void
    {
        global $wpdb;
        $old = $this->tickets($id);
        $keep = [];
        $now = gmdate('Y-m-d H:i:s');
        foreach (array_values($rows) as $i => $r) {
            $name = sanitize_text_field((string) ($r['description'] ?? ''));
            if ($name === '') continue;
            $amount = max(0, (int) ($r['amount'] ?? 0));
            $rid = (int) ($r['id'] ?? 0);
            $data = ['production_id'=>$id,'description'=>$name,'amount'=>$amount,'show_on_reservation'=>!empty($r['show_on_reservation'])?1:0,'display_order'=>$i,'updated_at'=>$now];
            if ($rid > 0) $wpdb->update($this->tickets,$data,['id'=>$rid,'production_id'=>$id],['%d','%s','%d','%d','%d','%s'],['%d','%d']);
            else {$data['created_at']=$now;$wpdb->insert($this->tickets,$data,['%d','%s','%d','%d','%d','%s','%s']);$rid=(int)$wpdb->insert_id;}
            if ($rid > 0) $keep[] = $rid;
        }
        foreach ($old as $x) if (!in_array((int)$x['id'],$keep,true)) $wpdb->delete($this->tickets,['id'=>(int)$x['id'],'production_id'=>$id],['%d','%d']);
    }

    public function addSlugHistory(int $productionId, string $slug): void { if ($slug === '') return; $this->db->query($this->db->prepare("INSERT IGNORE INTO {$this->slugs} (production_id,slug,created_at) VALUES (%d,%s,%s)",$productionId,$slug,gmdate('Y-m-d H:i:s'))); }
    public function productionIdByHistoricalSlug(string $slug): int { return (int) $this->db->get_var($this->db->prepare("SELECT production_id FROM {$this->slugs} WHERE slug=%s LIMIT 1",$slug)); }
    public function historicalSlugs(int $productionId): array { return array_map('strval',$this->db->get_col($this->db->prepare("SELECT slug FROM {$this->slugs} WHERE production_id=%d ORDER BY id",$productionId)) ?: []); }
}
