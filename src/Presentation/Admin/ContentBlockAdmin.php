<?php
declare(strict_types=1);

namespace StageArtCore\Presentation\Admin;

final class ContentBlockAdmin
{
    private const MAX_COLUMNS = 6;

    public function register(): void
    {
        add_action('admin_head', [$this, 'styles']);
        add_action('admin_footer', [$this, 'script'], 30);
        add_action('admin_post_stageart_save_homepage', [$this, 'normalizePost'], 0);
    }

    private function isHomepage(): bool
    {
        return current_user_can('manage_options') && in_array(($_GET['page'] ?? ''), ['stageart-homepage','stageart-organization'], true);
    }

    public function styles(): void
    {
        if (!$this->isHomepage()) return;
        echo '<style>
        .sa-home-section{position:relative}
        .sa-home-section legend strong:before{content:"コンテンツブロック ";}
        .sa-home-section legend .sa-section-number{display:none}
        .sa-block-columns-note{margin-top:4px}
        .sa-home-slot .sa-slot-controls{display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap}
        .sa-home-slot .sa-slot-controls label{display:inline-block}
        </style>';
    }

    public function script(): void
    {
        if (!$this->isHomepage()) return;
        $max = self::MAX_COLUMNS;
        echo '<script>(function(){
        const root=document.getElementById("stageart-home-sections");
        if(!root)return;
        const MAX_COLUMNS='.$max.';
        function updateSection(sec){
            const cols=sec.querySelector(".sa-columns");
            if(!cols)return;
            Array.from(cols.options).forEach(o=>{if(parseInt(o.value||"0",10)>MAX_COLUMNS)o.remove();});
            const value=Math.max(1,Math.min(MAX_COLUMNS,parseInt(cols.value||"1",10)));
            cols.value=String(value);
            sec.querySelectorAll(".sa-home-slot").forEach((slot,i)=>{slot.hidden=i>=value;});
            const note=sec.querySelector(".sa-block-columns-note");
            if(note)note.textContent="1〜"+MAX_COLUMNS+"列。列数に応じてコンテンツを横方向に配置します。";
        }
        function rename(sec){
            const legend=sec.querySelector("legend");
            if(legend){const n=legend.querySelector(".sa-section-number");legend.innerHTML="<strong>コンテンツブロック</strong>"+(n?"<span class=\\"sa-section-number\\">"+n.textContent+"</span>":"");}
            sec.querySelectorAll("label").forEach(label=>{
                const text=(label.textContent||"").trim();
                if(text.startsWith("表示数")){
                    label.firstChild && (label.firstChild.nodeValue="列数\\n");
                    label.classList.add("sa-block-columns-label");
                }
            });
            const desc=sec.querySelector(".sa-block-columns-note");
            if(!desc){
                const cols=sec.querySelector(".sa-columns");
                if(cols&&cols.parentElement){
                    const p=document.createElement("span");
                    p.className="description sa-block-columns-note";
                    p.style.display="block";
                    cols.parentElement.parentElement.appendChild(p);
                }
            }
        }
        function wire(sec){
            rename(sec);updateSection(sec);
            const cols=sec.querySelector(".sa-columns");
            if(cols&&!cols.dataset.blockColumnsWired){
                cols.dataset.blockColumnsWired="1";
                cols.addEventListener("change",()=>updateSection(sec));
            }
        }
        root.querySelectorAll(".sa-home-section").forEach(wire);
        const observer=new MutationObserver(m=>m.forEach(x=>x.addedNodes.forEach(n=>{if(n.nodeType===1&&n.matches(".sa-home-section"))wire(n);})));observer.observe(root,{childList:true});
        })();</script>';
    }

    public function normalizePost(): void
    {
        if (!current_user_can('manage_options') || !isset($_POST['sections']) || !is_array($_POST['sections'])) return;
        foreach ($_POST['sections'] as &$section) {
            if (!is_array($section)) continue;
            $columns=max(1,min(self::MAX_COLUMNS,absint($section['columns']??1)));
            $section['columns']=(string)$columns;
            if(isset($section['slots'])&&is_array($section['slots'])){
                $section['slots']=array_slice($section['slots'],0,$columns,true);
            }
        }
        unset($section);
    }
}
