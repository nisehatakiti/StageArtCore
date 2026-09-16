from pathlib import Path
import re

repo = Path('src/Domain/Production/ProductionRepository.php')
s = repo.read_text()
start = s.index('    public function saveLabels(int $id, array $rows, array $deletedIds = []): array')
end = s.index('\n    public function savePerformances', start)
new = '''    public function saveLabels(int $id, array $rows, array $deletedIds = []): array
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
                $ok = $this->db->update(
                    $this->labels,
                    ['symbol' => $symbol, 'name' => $name, 'display_order' => $order, 'updated_at' => $now],
                    ['id' => $rid, 'production_id' => $id],
                    ['%s', '%s', '%d', '%s'],
                    ['%d', '%d']
                );
                if ($ok === false) wp_die('公演スケジュールラベルの保存に失敗しました。');
            } else {
                $ok = $this->db->insert(
                    $this->labels,
                    ['production_id' => $id, 'symbol' => $symbol, 'name' => $name, 'display_order' => $order, 'created_at' => $now, 'updated_at' => $now],
                    ['%d', '%s', '%s', '%d', '%s', '%s']
                );
                if ($ok === false) wp_die('公演スケジュールラベルの追加に失敗しました。');
                $rid = (int) $this->db->insert_id;
            }
            if ($rid > 0) $newIds[(string) $rowKey] = $rid;
        }

        $deleted = [];
        foreach ($deletedIds as $deletedId) {
            $rid = (int) $deletedId;
            if ($rid > 0) $deleted[$rid] = true;
        }
        foreach ($old as $x) {
            $rid = (int) $x['id'];
            if (!isset($deleted[$rid])) continue;
            $this->db->query($this->db->prepare("UPDATE {$this->performances} SET label_id=NULL WHERE production_id=%d AND label_id=%d", $id, $rid));
            $this->db->delete($this->labels, ['id' => $rid, 'production_id' => $id], ['%d', '%d']);
        }
        return $newIds;
    }
'''
repo.write_text(s[:start] + new + s[end:])

theme = Path('theme/stageart/style.css')
t = theme.read_text()
pattern = r'\\.stageart-performance-list\\{font-size:\\.78rem!important;max-width:640px!important;\\}.*?\\.stageart-production--light \\.stageart-performance-timeline--grid \\.stageart-performance-timeline-line b\\{background:var\\(--production-bg\\)!important;\\}'
replacement = '''.stageart-performance-list{max-width:640px!important;}
.stageart-performance-list-row{gap:12px!important;}
.stageart-performance-list-date{min-width:48px!important;}
.stageart-performance-timeline{max-width:640px!important;margin:0 auto!important;overflow:visible!important;}
.stageart-performance-timeline table{min-width:0!important;width:100%!important;}
.stageart-performance-timeline th,.stageart-performance-timeline td{padding:5px 4px!important;}
.stageart-performance-timeline thead th{padding-bottom:6px!important;}
.stageart-performance-timeline tbody th{width:52px!important;}
.stageart-performance-timeline-line{min-height:22px!important;}
.stageart-performance-timeline-line:before{border-top-width:1px!important;}
.stageart-performance-timeline-line b{padding:0 4px!important;background:var(--paper)!important;}
.stageart-performance-timeline--line td,.stageart-performance-timeline--line th,.stageart-performance-timeline--grid td,.stageart-performance-timeline--grid th{border:0!important;background:transparent!important;}
.stageart-performance-timeline--grid td{position:relative!important;}
.stageart-performance-timeline--grid td:after{content:""!important;position:absolute!important;top:0!important;bottom:0!important;left:50%!important;border-left:1px solid var(--line)!important;transform:translateX(-50%)!important;z-index:1!important;}
.stageart-performance-timeline--grid .stageart-performance-timeline-line:after{content:none!important;}
.stageart-performance-timeline--grid .stageart-performance-timeline-line b{background:var(--paper)!important;z-index:2;}
.stageart-production--dark .stageart-performance-timeline--grid .stageart-performance-timeline-line b{background:var(--production-bg)!important;}
.stageart-production--light .stageart-performance-timeline--grid .stageart-performance-timeline-line b{background:var(--production-bg)!important;}
.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline td{font-size:1.15rem!important;}
.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline-line b{font-size:1.15rem!important;}
.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline td{font-size:.95rem!important;}
.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline-line b{font-size:.95rem!important;}
.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline td{font-size:.8rem!important;}
.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline-line b{font-size:.8rem!important;}'''
t2, n = re.subn(pattern, replacement, t, flags=re.S)
if n != 1:
    raise SystemExit(f'theme block replacements={n}')
theme.write_text(t2)

p = Path('stageart-core.php')
ps = p.read_text().replace('Version: 0.6.5', 'Version: 0.6.6').replace("define('STAGEART_CORE_VERSION','0.6.5');", "define('STAGEART_CORE_VERSION','0.6.6');")
p.write_text(ps)
