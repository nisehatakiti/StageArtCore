from pathlib import Path

admin = Path('src/Presentation/Admin/ProductionAdmin.php')
s = admin.read_text(encoding='utf-8')

old = '''<tr><th>情報解禁</th><td><input type="datetime-local" name="performance_release" value="'.esc_attr($this->jst($g('performance_release'))).'" /></td></tr><tr><th>ラベル</th>'''
new = '''<tr><th>情報解禁</th><td><input type="datetime-local" name="performance_release" value="'.esc_attr($this->jst($g('performance_release'))).'" /></td></tr><tr><th>表示方式</th><td><select name="performance_view"><option value="table" '.selected($g('performance_view','table'),'table',false).'>表形式</option><option value="list" '.selected($g('performance_view'),'list',false).'>一覧形式</option><option value="timeline_line" '.selected($g('performance_view'),'timeline_line',false).'>タイムライン（線）</option><option value="timeline_grid" '.selected($g('performance_view'),'timeline_grid',false).'>タイムライン（区切り）</option></select></td></tr><tr><th>表示サイズ</th><td><label><input type="radio" name="performance_size" value="l" '.checked($g('performance_size','l'),'l',false).'> L</label> <label><input type="radio" name="performance_size" value="m" '.checked($g('performance_size','l'),'m',false).'> M</label> <label><input type="radio" name="performance_size" value="s" '.checked($g('performance_size','l'),'s',false).'> S</label></td></tr><tr><th>ラベル</th>'''
if old in s: s = s.replace(old, new, 1)
elif 'name="performance_view"' not in s: raise SystemExit('performance form anchor missing')

old = '''<tr><th>情報解禁</th><td><input type="datetime-local" name="ticket_release" value="'.esc_attr($this->jst($g('ticket_release'))).'" /></td></tr><tr><th>税表示</th>'''
new = '''<tr><th>表示サイズ</th><td><label><input type="radio" name="ticket_size" value="l" '.checked($g('ticket_size','l'),'l',false).'> L</label> <label><input type="radio" name="ticket_size" value="m" '.checked($g('ticket_size','l'),'m',false).'> M</label> <label><input type="radio" name="ticket_size" value="s" '.checked($g('ticket_size','l'),'s',false).'> S</label></td></tr><tr><th>情報解禁</th><td><input type="datetime-local" name="ticket_release" value="'.esc_attr($this->jst($g('ticket_release'))).'" /></td></tr><tr><th>税表示</th>'''
# Correct the quote in the replacement string before applying it.
new = new.replace("value=\"'.esc_attr($this->jst($g('ticket_release'))).'\"", "value=\"'.esc_attr($this->jst($g('ticket_release'))).'\"", 1)
if old in s: s = s.replace(old, new, 1)
elif 'name="ticket_size"' not in s: raise SystemExit('ticket form anchor missing')

old = '<h3>公演スケジュールラベル</h3><table class="widefat" id="sa-labels">'
new = '<h3>公演スケジュールラベル</h3><input type="hidden" name="labels_form_present" value="1"><div id="sa-label-deletions"></div><table class="widefat" id="sa-labels">'
if old in s: s = s.replace(old, new, 1)

if "update_post_meta($id,'performance_view'" not in s:
    anchor = "update_post_meta($id,'legend',in_array($_POST['legend']??'none',['none','above','below'],true)?$_POST['legend']:'none');"
    if anchor not in s: raise SystemExit('legend save anchor missing')
    insert = anchor + "$performanceView=sanitize_key(wp_unslash($_POST['performance_view']??'table'));if(!in_array($performanceView,['table','list','timeline_line','timeline_grid'],true))$performanceView='table';update_post_meta($id,'performance_view',$performanceView);$performanceSize=sanitize_key(wp_unslash($_POST['performance_size']??'l'));if(!in_array($performanceSize,['l','m','s'],true))$performanceSize='l';update_post_meta($id,'performance_size',$performanceSize);$ticketSize=sanitize_key(wp_unslash($_POST['ticket_size']??'l'));if(!in_array($ticketSize,['l','m','s'],true))$ticketSize='l';update_post_meta($id,'ticket_size',$ticketSize);"
    s = s.replace(anchor, insert, 1)

old = "$this->repo->saveLabels($id,$this->cleanRows($_POST['labels']??[]));$this->repo->savePerformances($id,$this->cleanRows($_POST['performances']??[]));"
new = "$labelIds=$this->repo->saveLabels($id,$this->cleanRows($_POST['labels']??[]),(array)($_POST['deleted_labels']??[]));$performanceRows=$this->cleanRows($_POST['performances']??[]);foreach($performanceRows as&$performanceRow){$labelKey=(string)($performanceRow['label_id']??'');if($labelKey!==''&&!ctype_digit($labelKey)&&isset($labelIds[$labelKey]))$performanceRow['label_id']=$labelIds[$labelKey];}unset($performanceRow);$this->repo->savePerformances($id,$performanceRows);"
if old in s: s = s.replace(old, new, 1)
elif '$labelIds=' not in s: raise SystemExit('label save anchor missing')

admin.write_text(s, encoding='utf-8')

repo = Path('src/Domain/Production/ProductionRepository.php')
r = repo.read_text(encoding='utf-8')
start = r.index('    public function saveLabels(')
end = r.index('    public function savePerformances(', start)
method = '''    public function saveLabels(int $id, array $rows, array $deletedIds = []): array
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
            $name = array_key_exists('name', $r) ? sanitize_text_field((string) $r['name']) : (string) ($oldById[$rid]['name'] ?? '');
            $data = ['production_id'=>$id,'symbol'=>$symbol,'name'=>$name,'display_order'=>count($keep),'updated_at'=>$now];
            if ($rid > 0 && isset($oldById[$rid])) {
                $sql = $wpdb->prepare("UPDATE {$this->labels} SET symbol=%s,name=%s,display_order=%d,updated_at=%s WHERE id=%d AND production_id=%d", $symbol, $name, count($keep), $now, $rid, $id);
                if ($wpdb->query($sql) === false) $wpdb->update($this->labels, $data, ['id'=>$rid,'production_id'=>$id], ['%d','%s','%s','%d','%s'], ['%d','%d']);
            } else {
                $data['created_at']=$now;
                $wpdb->insert($this->labels, $data, ['%d','%s','%s','%d','%s','%s']);
                $rid=(int)$wpdb->insert_id;
            }
            if ($rid > 0) { $keep[]=$rid; $newIds[(string)$rowKey]=$rid; }
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

'''
r = r[:start] + method + r[end:]
repo.write_text(r, encoding='utf-8')

router = Path('src/Presentation/PublicSite/ProductionRouter.php')
p = router.read_text(encoding='utf-8')
repls = {
'.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-table th,.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-table td{padding:10px;font-size:.88rem}': '.stageart-production-layout-slot--performances.stageart-size-l{font-size:1rem}.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-table th,.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-table td{padding:10px;font-size:1rem}',
'.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-table th,.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-table td{padding:6px;font-size:.76rem}': '.stageart-production-layout-slot--performances.stageart-size-m{font-size:.84rem}.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-table th,.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-table td{padding:6px;font-size:.84rem}',
'.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-table th,.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-table td{padding:4px;font-size:.66rem}': '.stageart-production-layout-slot--performances.stageart-size-s{font-size:.72rem}.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-table th,.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-table td{padding:4px;font-size:.72rem}',
'.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline{font-size:.82rem}': '.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline{font-size:1rem}',
'.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline{font-size:.72rem}': '.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline{font-size:.84rem}',
'.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline{font-size:.62rem}': '.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline{font-size:.72rem}',
'.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline td{font-size:.82rem}': '.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-l .stageart-performance-timeline td{font-size:1rem}',
'.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline td{font-size:.72rem}': '.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-m .stageart-performance-timeline td{font-size:.84rem}',
'.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline td{font-size:.62rem}': '.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline th,.stageart-production-layout-slot--performances.stageart-size-s .stageart-performance-timeline td{font-size:.72rem}',
}
for a,b in repls.items(): p=p.replace(a,b)
if '.stageart-performance-timeline--grid td:after' not in p:
    p=p.replace('.stageart-performance-timeline-line b{position:relative;z-index:2;', '.stageart-performance-timeline--grid td:after{content:"";position:absolute;top:0;bottom:0;left:50%;border-left:1px solid var(--line);transform:translateX(-50%);z-index:1}\n            .stageart-performance-timeline--grid .stageart-performance-timeline-line:after{content:none}\n            .stageart-performance-timeline-line b{position:relative;z-index:2;')
router.write_text(p, encoding='utf-8')

for path in [admin, router, Path('src/Presentation/PublicSite/ProductionLayout.php')]:
    q = path.read_text(encoding='utf-8').replace('公演回','公演スケジュール')
    path.write_text(q, encoding='utf-8')

print('StageArt schedule/admin fix applied')
