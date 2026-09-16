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
        global $wpdb;
        $old = $this->labels($id);
        $oldById = [];
        foreach ($old as $label) $oldById[(int) $label['id']] = $label;
        $keep = [];
        $newIds = [];
        $now = gmdate('Y-m-d H:i:s');
        foreach ($rows as $rowKey => $r) {
            if (!is_array($r)) continue;
            $symbol = sanitize_text_field((string) ($r['symbol'] ?? ''));
            if ($symbol === '') continue;
            $rid = (int) ($r['id'] ?? 0);
            $name = sanitize_text_field((string) ($r['name'] ?? ($oldById[$rid]['name'] ?? '')));
            $data = ['production_id'=>$id,'symbol'=>$symbol,'name'=>$name,'display_order'=>count($keep),'updated_at'=>$now];
            if ($rid > 0 && isset($oldById[$rid])) {
                $wpdb->update($this->labels,$data,['id'=>$rid,'production_id'=>$id],['%d','%s','%s','%d','%s'],['%d','%d']);
            } else {
                $data['created_at']=$now;
                $wpdb->insert($this->labels,$data,['%d','%s','%s','%d','%s','%s']);
                $rid=(int)$wpdb->insert_id;
            }
            if ($rid>0) { $keep[]=$rid; $newIds[(string)$rowKey]=$rid; }
        }
        $deleted=[];
        foreach ($deletedIds as $v) { $rid=(int)$v; if($rid>0)$deleted[$rid]=true; }
        foreach ($old as $x) {
            $rid=(int)$x['id'];
            if (!isset($deleted[$rid])) continue;
            $wpdb->query($wpdb->prepare("UPDATE {$this->performances} SET label_id=NULL WHERE production_id=%d AND label_id=%d",$id,$rid));
            $wpdb->delete($this->labels,['id'=>$rid,'production_id'=>$id],['%d','%d']);
        }
        return $newIds;
    }

    public function savePerformances(int $id, array $rows): void
    {
        global $wpdb;
        $old = $this->performances($id);
        $oldById = [];
        foreach ($old as $x) $oldById[(int) $x['id']] = $x;
        $validLabels = [];
        foreach ($this->labels($id) as $l) $validLabels[(int) $l['id']] = true;
        $keep = [];
        $now = gmdate('Y-m-d H:i:s');
        foreach (array_values($rows) as $i => $r) {
            $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($r['date'] ?? '')) ? $r['date'] : '';
            $start = preg_match('/^\d{2}:\d{2}$/', (string) ($r['start'] ?? '')) ? $r['start'] : '';
            if (!$date || !$start) continue;
            $end = preg_match('/^\d{2}:\d{2}$/', (string) ($r['end'] ?? '')) ? $r['end'] : null;
            $rid = (int) ($r['id'] ?? 0);
            $label = null;
            if (array_key_exists('label_id', $r) && $r['label_id'] !== '') {
                $candidate = (int) $r['label_id'];
                $label = isset($validLabels[$candidate]) ? $candidate : null;
            } elseif ($rid > 0 && isset($oldById[$rid])) {
                $existing = $oldById[$rid]['label_id'];
                $label = $existing !== null ? (int) $existing : null;
            }
            $release = isset($r['release_at']) ? ReleaseDate::toUtc(sanitize_text_field((string) $r['release_at'])) : ($rid > 0 && isset($oldById[$rid]) ? $oldById[$rid]['release_at'] : null);
            $data = ['production_id'=>$id,'performance_date'=>$date,'start_time'=>$start,'end_time'=>$end,'label_id'=>$label,'release_at'=>$release,'updated_at'=>$now];
            if ($rid > 0) $wpdb->update($this->performances, $data, ['id'=>$rid,'production_id'=>$id], ['%d','%s','%s','%s','%d','%s','%s'], ['%d','%d']);
            else { $data['created_at']=$now; $wpdb->insert($this->performances,$data,['%d','%s','%s','%s','%d','%s','%s','%s']); $rid=(int)$wpdb->insert_id; }
            if ($rid > 0) $keep[]=$rid;
        }
        foreach ($old as $x) if (!in_array((int)$x['id'],$keep,true)) $wpdb->delete($this->performances,['id'=>(int)$x['id'],'production_id'=>$id],['%d','%d']);
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
