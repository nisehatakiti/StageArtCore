<?php

declare(strict_types=1);

namespace StageArtCore\Presentation\PublicSite;

final class SiteStructure
{
    public const HOME_OPTION = 'stageart_core_homepage_sections';
    public const MENU_OPTION = 'stageart_core_menu_structure';

    public static function systemLabels(): array
    {
        return ['home'=>'ホーム','productions'=>'公演一覧','members'=>'メンバー','contact'=>'連絡先','about'=>'団体について','news'=>'お知らせ'];
    }

    public static function systemUrl(string $key): string
    {
        $map=['home'=>home_url('/'),'productions'=>home_url('/#productions'),'members'=>home_url('/members/'),'contact'=>home_url('/contact/'),'about'=>home_url('/#about'),'news'=>home_url('/#news')];
        return $map[$key]??'';
    }

    public static function homepageSections(): array
    {
        $saved=get_option(self::HOME_OPTION,null);
        if(!is_array($saved)){ $saved=self::defaultSections(); update_option(self::HOME_OPTION,$saved,false); }
        return self::normalizeSections($saved);
    }

    public static function menuItems(): array
    {
        $saved=get_option(self::MENU_OPTION,null);
        if(!is_array($saved)){ $saved=self::defaultMenu(); update_option(self::MENU_OPTION,$saved,false); }
        return self::normalizeMenu($saved);
    }

    public static function defaultSections(): array
    {
        return [
            ['section_id'=>wp_generate_uuid4(),'heading'=>'公演','columns'=>3,'layout'=>'horizontal','slots'=>[['type'=>'system','ref'=>'productions']]],
            ['section_id'=>wp_generate_uuid4(),'heading'=>'私たちについて','columns'=>1,'layout'=>'horizontal','slots'=>[['type'=>'system','ref'=>'about']]],
            ['section_id'=>wp_generate_uuid4(),'heading'=>'お知らせ','columns'=>1,'layout'=>'vertical','slots'=>[['type'=>'system','ref'=>'news']]],
        ];
    }

    public static function defaultMenu(): array
    {
        return [
            ['id'=>wp_generate_uuid4(),'parent_id'=>'','type'=>'content','ref_type'=>'system','ref_id'=>'home','label'=>'ホーム','order'=>1],
            ['id'=>wp_generate_uuid4(),'parent_id'=>'','type'=>'content','ref_type'=>'system','ref_id'=>'productions','label'=>'公演','order'=>2],
            ['id'=>wp_generate_uuid4(),'parent_id'=>'','type'=>'content','ref_type'=>'system','ref_id'=>'members','label'=>'メンバー','order'=>3],
            ['id'=>wp_generate_uuid4(),'parent_id'=>'','type'=>'content','ref_type'=>'system','ref_id'=>'contact','label'=>'お問い合わせ','order'=>4],
        ];
    }

    private static function normalizeSections(array $sections): array
    {
        $out=[];
        foreach(array_values($sections) as $i=>$section){
            if(!is_array($section))continue;
            $slots=[];
            foreach((array)($section['slots']??[]) as $slot){
                if(!is_array($slot))continue;
                $type=in_array(($slot['type']??''),['system','production','member','url'],true)?(string)$slot['type']:'system';
                $ref=sanitize_text_field((string)($slot['ref']??''));
                if($ref===''&&$type!=='url')continue;
                $slots[]=['type'=>$type,'ref'=>$type==='url'?esc_url_raw($ref):$ref];
            }
            $out[]=['section_id'=>sanitize_key((string)($section['section_id']??wp_generate_uuid4())),'heading'=>sanitize_text_field((string)($section['heading']??'')),'columns'=>max(1,min(6,(int)($section['columns']??1))),'layout'=>($section['layout']??'horizontal')==='vertical'?'vertical':'horizontal','slots'=>$slots,'order'=>$i+1];
        }
        return $out;
    }

    private static function normalizeMenu(array $items): array
    {
        $ids=[];foreach($items as $item)if(is_array($item)&&!empty($item['id']))$ids[(string)$item['id']]=true;
        $out=[];
        foreach(array_values($items) as $i=>$item){
            if(!is_array($item))continue;
            $type=($item['type']??'content')==='folder'?'folder':'content';
            $parent=sanitize_key((string)($item['parent_id']??''));if($parent!==''&&!isset($ids[$parent]))$parent='';
            $refType=in_array(($item['ref_type']??'system'),['system','production','member','url'],true)?(string)$item['ref_type']:'system';
            $ref=sanitize_text_field((string)($item['ref_id']??''));if($refType==='url')$ref=esc_url_raw($ref);
            $out[]=['id'=>sanitize_key((string)($item['id']??wp_generate_uuid4())),'parent_id'=>$parent,'type'=>$type,'ref_type'=>$refType,'ref_id'=>$ref,'label'=>sanitize_text_field((string)($item['label']??'')),'order'=>max(1,(int)($item['order']??($i+1)))];
        }
        usort($out,static fn(array $a,array $b):int=>$a['order']<=>$b['order']);
        return $out;
    }
}
