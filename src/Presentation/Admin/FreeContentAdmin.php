<?php
declare(strict_types=1);
namespace StageArtCore\Presentation\Admin;

final class FreeContentAdmin
{
    public const POST_TYPE='stageart_free_content';
    public const OWNER_META='_stageart_free_owner';
    public const CATEGORY_META='_stageart_free_category';
    public function register():void
    {
        register_post_type(self::POST_TYPE,['labels'=>['name'=>'自由コンテンツ','singular_name'=>'自由コンテンツ'],'public'=>false,'show_ui'=>false,'show_in_menu'=>false,'supports'=>['title','editor'],'rewrite'=>false]);
        add_submenu_page('stageart-plugin','自由コンテンツ','自由コンテンツ','manage_options','stageart-free-content',[$this,'renderList']);
        add_action('admin_post_stageart_save_free_content',[$this,'save']);
    }
    private function ownerLabel(string $owner):string { return $owner==='top'?'TOP共通':'公演専用'; }
    public function renderList():void
    {
        $id=(int)($_GET['id']??0);
        echo '<div class="wrap"><h1>自由コンテンツ</h1>';
        if(isset($_GET['saved'])) echo '<div class="notice notice-success"><p>保存しました。</p></div>';
        if($id){$this->form($id);echo '</div>';return;}
        echo '<p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=stageart-free-content&new=1')).'">＋ 自由コンテンツを追加</a></p>';
        $posts=get_posts(['post_type'=>self::POST_TYPE,'post_status'=>['publish','draft'],'numberposts'=>-1,'orderby'=>'date','order'=>'DESC']);
        echo '<table class="widefat striped"><thead><tr><th>タイトル</th><th>分類</th><th>公開状態</th><th>操作</th></tr></thead><tbody>';
        foreach($posts as $p){$owner=(string)get_post_meta($p->ID,self::OWNER_META,true);$cat=(string)get_post_meta($p->ID,self::CATEGORY_META,true);echo '<tr><td><strong>'.esc_html($p->post_title).'</strong></td><td>'.esc_html($this->ownerLabel($owner)).' / '.esc_html($cat?:'自由コンテンツ').'</td><td>'.esc_html($p->post_status==='publish'?'公開':'下書き').'</td><td><a href="'.esc_url(admin_url('admin.php?page=stageart-free-content&id='.$p->ID)).'">編集</a></td></tr>';}
        if(!$posts)echo '<tr><td colspan="4">自由コンテンツはまだありません。</td></tr>';
        echo '</tbody></table></div>';
    }
    private function form(int $id):void
    {
        $p=$id?get_post($id):null;$m=$id?get_post_meta($id):[];$g=fn($k,$d='')=>(string)($m[$k][0]??$d);
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('stageart_free_content');echo '<input type="hidden" name="action" value="stageart_save_free_content"><input type="hidden" name="id" value="'.(int)$id.'"><h2>自由コンテンツ</h2><table class="form-table">';
        echo '<tr><th>タイトル</th><td><input class="regular-text" required name="title" value="'.esc_attr($p?$p->post_title:'').'"></td></tr>';
        echo '<tr><th>Slug</th><td><input class="regular-text" name="slug" value="'.esc_attr($p?$p->post_name:'').'"><p class="description">内部識別用です。公開画面には表示しません。</p></td></tr>';
        echo '<tr><th>分類</th><td><select name="category"><option value="団体" '.selected($g(self::CATEGORY_META,'団体'),'団体',false).'>団体</option><option value="公演" '.selected($g(self::CATEGORY_META),'公演',false).'>公演</option><option value="メンバー" '.selected($g(self::CATEGORY_META),'メンバー',false).'>メンバー</option><option value="お知らせ" '.selected($g(self::CATEGORY_META),'お知らせ',false).'>お知らせ</option><option value="アクセス" '.selected($g(self::CATEGORY_META),'アクセス',false).'>アクセス</option><option value="自由コンテンツ" '.selected($g(self::CATEGORY_META),'自由コンテンツ',false).'>自由コンテンツ</option></select></td></tr>';
        echo '<tr><th>所有範囲</th><td><select name="owner" id="sa-free-owner"><option value="top" '.selected($g(self::OWNER_META,'top'),'top',false).'>TOP共通</option><option value="production" '.selected($g(self::OWNER_META),'production','production',false).'>公演専用</option></select><p class="description">TOP共通はすべての公演から参照できます。公演専用は指定した公演だけです。</p></td></tr>';
        echo '<tr id="sa-free-production"><th>対象公演</th><td><select name="production_id"><option value="0">選択してください</option>';
        foreach(get_posts(['post_type'=>'stageart_production','post_status'=>['publish','draft'],'numberposts'=>-1,'orderby'=>'title','order'=>'ASC']) as $x)echo '<option value="'.(int)$x->ID.'" '.selected((int)$g('_stageart_free_production_id'),$x->ID,false).'>'.esc_html($x->post_title).'</option>';
        echo '</select></td></tr></table>';
        wp_editor($p?$p->post_content:'','stageart_free_body',['textarea_name'=>'content','textarea_rows'=>14,'media_buttons'=>true]);
        echo '<p><label>公開状態 <select name="post_status"><option value="publish" '.selected($p->post_status??'publish','publish',false).'>公開</option><option value="draft" '.selected($p->post_status??'publish','draft',false).'>非公開</option></select></label></p>';
        submit_button('保存');echo ' <a class="button" href="'.esc_url(admin_url('admin.php?page=stageart-free-content')).'">キャンセル</a></form>';
        echo '<script>(function(){var o=document.getElementById("sa-free-owner"),r=document.getElementById("sa-free-production");function x(){r.style.display=o.value==="production"?"":"none"}o.addEventListener("change",x);x()})();</script>';
    }
    public function save():void
    {
        if(!current_user_can('manage_options'))wp_die('権限がありません。');check_admin_referer('stageart_free_content');
        $id=(int)($_POST['id']??0);$title=sanitize_text_field(wp_unslash($_POST['title']??''));if($title==='')wp_die('タイトルは必須です。');
        $status=($_POST['post_status']??'publish')==='draft'?'draft':'publish';$slug=sanitize_title(wp_unslash($_POST['slug']??''));$owner=($_POST['owner']??'top')==='production'?'production':'top';$pid=$owner==='production'?absint($_POST['production_id']??0):0;
        if($owner==='production'&&!$pid)wp_die('公演専用の場合は対象公演を選択してください。');
        $data=['post_title'=>$title,'post_content'=>wp_kses_post(wp_unslash($_POST['content']??'')),'post_status'=>$status,'post_type'=>self::POST_TYPE];if($slug!=='')$data['post_name']=$slug;
        $saved=$id?wp_update_post(array_merge(['ID'=>$id],$data),true):wp_insert_post($data,true);if(is_wp_error($saved))wp_die($saved->get_error_message());$id=(int)$saved;
        update_post_meta($id,self::OWNER_META,$owner);update_post_meta($id,self::CATEGORY_META,sanitize_text_field(wp_unslash($_POST['category']??'自由コンテンツ')));update_post_meta($id,'_stageart_free_production_id',$pid);
        wp_safe_redirect(admin_url('admin.php?page=stageart-free-content&id='.$id.'&saved=1'));exit;
    }
}
