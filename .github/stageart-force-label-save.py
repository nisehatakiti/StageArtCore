from pathlib import Path
import re

path = Path('src/Domain/Production/ProductionRepository.php')
s = path.read_text(encoding='utf-8')
start = s.find('    public function savePerformances(int $id, array $rows): void')
end = s.find('\n    public function saveTickets', start)
if start < 0 or end < 0:
    raise SystemExit('savePerformances boundaries not found')
old = s[start:end]
if 'STAGEART_LABEL_SAVE_VERIFIED' in old:
    print('already patched')
    raise SystemExit(0)
new = r'''    public function savePerformances(int $id, array $rows): void
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
            $date = preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', (string) ($r['date'] ?? '')) ? (string) $r['date'] : '';
            $start = preg_match('/^\\d{2}:\\d{2}$/', (string) ($r['start'] ?? '')) ? (string) $r['start'] : '';
            if (!$date || !$start) continue;
            $end = preg_match('/^\\d{2}:\\d{2}$/', (string) ($r['end'] ?? '')) ? (string) $r['end'] : null;
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
'''
# The raw replacement above intentionally contains escaped regex slashes; normalize only the PHP regex literals.
new = new.replace("'/^\\\\d{4}-\\\\d{2}-\\\\d{2}$/'", "'/^\\d{4}-\\d{2}-\\d{2}$/'")
new = new.replace("'/^\\\\d{2}:\\\\d{2}$/'", "'/^\\d{2}:\\d{2}$/'")
path.write_text(s[:start] + new + s[end:], encoding='utf-8')
print('patched', path)
