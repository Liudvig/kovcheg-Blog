<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\Auth;
use Kovcheg\DB;
use Throwable;

require_once __DIR__.'/ClassicEditor.php';

final class Studio32
{
    public static function saveEntry(array $input, int $authorId, int $entryId = 0): int
    {
        Studio::require('content');
        $entryId=max(0,$entryId);
        $current=$entryId?Studio::entry($entryId):null;
        if($entryId&&!$current)abort(404,'Материал не найден.');

        $type=(string)($input['type']??'')==='page'?'page':'post';
        $status=in_array((string)($input['status']??''),['draft','published','scheduled','private'],true)?(string)$input['status']:'draft';
        $visibility=in_array((string)($input['visibility']??''),['public','users','private'],true)?(string)$input['visibility']:'public';
        $title=trim((string)($input['title']??''));
        if(mb_strlen($title)<2||mb_strlen($title)>255)abort(422,'Заголовок должен содержать от 2 до 255 символов.');
        $slug=Studio::uniqueSlug(trim((string)($input['slug']??''))?:$title,$entryId);
        $excerpt=mb_substr(trim((string)($input['excerpt']??'')),0,2000);
        $rawContent=(string)($input['content_json']??'[]');
        if(!ClassicEditor::isClassicPayload($rawContent)){
            $fallback=ClassicEditor::sanitize((string)($current['content_html']??''));
            $rawContent=json_encode([[
                'id'=>'classic-'.bin2hex(random_bytes(6)),
                'type'=>'classic',
                'data'=>['html'=>$fallback],
            ]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        }
        $contentJson=ClassicEditor::normalizePayload($rawContent);
        $contentHtml=ClassicEditor::renderPayload($contentJson);
        $featured=self::safeStoredPath((string)($input['featured_image_path']??''));
        $seoTitle=mb_substr(trim((string)($input['seo_title']??'')),0,255);
        $seoDescription=mb_substr(trim((string)($input['seo_description']??'')),0,320);
        $publishedAt=self::normalizeDateTime((string)($input['published_at']??''));
        if($status==='published'&&($publishedAt===null||strtotime($publishedAt)>time()))$publishedAt=date('Y-m-d H:i:s');
        if($status==='scheduled'&&($publishedAt===null||strtotime($publishedAt)<=time()))abort(422,'Для запланированной публикации укажите будущую дату.');
        $flag=static fn(string $key):int=>!empty($input[$key])?1:0;

        DB::pdo()->beginTransaction();
        try{
            if($current){
                DB::run('INSERT INTO content_revisions (entry_id,author_id,title,excerpt,content_json,content_html,created_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)',[$entryId,$authorId,(string)$current['title'],$current['excerpt'],$current['content_json'],$current['content_html']]);
                DB::run('UPDATE content_entries SET author_id=?,type=?,status=?,title=?,slug=?,excerpt=?,content_json=?,content_html=?,featured_image_path=?,template=NULL,visibility=?,comments_enabled=?,reactions_enabled=0,is_featured=?,sort_order=0,seo_title=?,seo_description=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$authorId,$type,$status,$title,$slug,$excerpt?:null,$contentJson,$contentHtml,$featured?:null,$visibility,$flag('comments_enabled'),$flag('is_featured'),$seoTitle?:null,$seoDescription?:null,$publishedAt,$entryId]);
                $id=$entryId;
            }else{
                $id=DB::insert('INSERT INTO content_entries (author_id,type,status,title,slug,excerpt,content_json,content_html,featured_image_path,template,visibility,comments_enabled,reactions_enabled,is_featured,sort_order,seo_title,seo_description,published_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,NULL,?,?,0,?,0,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$authorId,$type,$status,$title,$slug,$excerpt?:null,$contentJson,$contentHtml,$featured?:null,$visibility,$flag('comments_enabled'),$flag('is_featured'),$seoTitle?:null,$seoDescription?:null,$publishedAt]);
            }
            self::syncCategories($id,$type==='post'?(array)($input['category_ids']??[]):[]);
            self::syncTags($id,'');
            self::syncMeta($id,[
                'layout_width'=>'normal',
                'accent'=>'',
                'editor_mode'=>'classic',
            ]);
            DB::run("DELETE FROM content_autosaves WHERE user_id=? AND (entry_id=? OR autosave_key='new')",[$authorId,$id]);
            DB::pdo()->commit();
        }catch(Throwable $e){if(DB::pdo()->inTransaction())DB::pdo()->rollBack();throw $e;}
        audit($current?'cms.content.update':'cms.content.create','content_entry',$id,['type'=>$type,'status'=>$status,'editor'=>'classic']);
        return $id;
    }

    public static function storeMedia(array $file,int $uploaderId,int $folderId=0):array
    {
        $item=Studio::storeMedia($file,$uploaderId);
        if($item&&$folderId>0&&DB::one('SELECT id FROM media_folders WHERE id=?',[$folderId])){
            DB::run('UPDATE media_library SET folder_id=? WHERE id=?',[$folderId,(int)$item['id']]);
            $item['folder_id']=$folderId;
        }
        return $item;
    }

    public static function autosave(int $entryId,int $userId,string $title,string $excerpt,string $contentJson):void
    {
        Studio::require('content');
        $entryId=max(0,$entryId);
        if($entryId&&!DB::one("SELECT id FROM content_entries WHERE id=? AND type IN ('post','page') AND deleted_at IS NULL",[$entryId]))abort(404,'Материал не найден.');
        if(!ClassicEditor::isClassicPayload($contentJson)){
            $contentJson=json_encode([[
                'id'=>'classic-'.bin2hex(random_bytes(6)),
                'type'=>'classic',
                'data'=>['html'=>''],
            ]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        }
        $normalized=ClassicEditor::normalizePayload($contentJson);
        $key=$entryId>0?'entry:'.$entryId:'new';
        DB::run('INSERT INTO content_autosaves (entry_id,user_id,autosave_key,title,excerpt,content_json,saved_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE entry_id=VALUES(entry_id),title=VALUES(title),excerpt=VALUES(excerpt),content_json=VALUES(content_json),saved_at=CURRENT_TIMESTAMP',[$entryId?:null,$userId,$key,mb_substr(trim($title),0,255),mb_substr(trim($excerpt),0,2000),$normalized]);
    }

    public static function presets():array
    {
        $result=[];
        foreach(glob(BASE_PATH.'/presets/*.json')?:[] as $file){
            try{$data=json_decode((string)file_get_contents($file),true,512,JSON_THROW_ON_ERROR);}catch(Throwable){continue;}
            if(!is_array($data)||empty($data['slug'])||empty($data['name']))continue;
            $result[]=$data;
        }
        usort($result,static fn($a,$b)=>strcmp((string)$a['name'],(string)$b['name']));
        return $result;
    }

    public static function applyPreset(string $slug,int $userId):void
    {
        Studio::require('site');
        $preset=null;
        foreach(self::presets() as $candidate)if((string)$candidate['slug']===$slug){$preset=$candidate;break;}
        if(!$preset)abort(404,'Пресет не найден.');
        $settings=is_array($preset['settings']??null)?$preset['settings']:[];
        $allowed=['blog_theme','site_name','blog_tagline','blog_description','blog_footer_text','seo_description','search_indexing'];
        $before=[];
        foreach($allowed as $key){if(!array_key_exists($key,$settings))continue;$before[$key]=(string)setting($key,'');Studio::setSetting($key,mb_substr((string)$settings[$key],0,1000));}
        DB::run('INSERT INTO site_preset_history (user_id,preset_slug,settings_json,created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)',[$userId,$slug,json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
        audit('cms.preset.apply','site_preset',null,['slug'=>$slug]);
    }

    public static function changeUserRole(int $userId,string $role,int $actorId):void
    {
        Studio::require('site');
        if($userId===$actorId&&$role!=='owner')abort(422,'Владелец не может понизить собственную роль.');
        $allowed=['owner','admin','editor','moderator','user'];
        if(!in_array($role,$allowed,true))abort(422,'Неизвестная роль.');
        $user=DB::one('SELECT id,role FROM users WHERE id=?',[$userId]);
        if(!$user)abort(404,'Пользователь не найден.');
        if((string)$user['role']==='owner'&&$role!=='owner'){
            $owners=(int)(DB::one("SELECT COUNT(*) c FROM users WHERE role='owner' AND is_active=1")['c']??0);
            if($owners<2)abort(409,'Нельзя удалить последнего владельца системы.');
        }
        DB::run('UPDATE users SET role=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$role,$userId]);
        DB::run('INSERT INTO user_role_history (user_id,previous_role,new_role,changed_by,created_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP)',[$userId,(string)$user['role'],$role,$actorId]);
        audit('cms.user.role','user',$userId,['from'=>$user['role'],'to'=>$role]);
    }

    private static function syncCategories(int $entryId,array $ids):void
    {
        DB::run('DELETE FROM content_entry_categories WHERE entry_id=?',[$entryId]);
        foreach(array_unique(array_map('intval',$ids)) as $id)if($id>0&&DB::one('SELECT id FROM content_categories WHERE id=?',[$id]))DB::run('INSERT IGNORE INTO content_entry_categories (entry_id,category_id) VALUES (?,?)',[$entryId,$id]);
    }

    private static function syncTags(int $entryId,string $tags):void
    {
        DB::run('DELETE FROM content_entry_tags WHERE entry_id=?',[$entryId]);
        if(trim($tags)==='')return;
        $names=array_slice(array_unique(array_filter(array_map('trim',preg_split('/[,;]+/u',$tags)?:[]))),0,30);
        foreach($names as $name){$name=mb_substr($name,0,120);$slug=Studio::slugify($name);if($slug==='')continue;DB::run('INSERT INTO content_tags (name,slug,created_at,updated_at) VALUES (?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE name=VALUES(name),updated_at=CURRENT_TIMESTAMP',[$name,$slug]);$tag=DB::one('SELECT id FROM content_tags WHERE slug=?',[$slug]);if($tag)DB::run('INSERT IGNORE INTO content_entry_tags (entry_id,tag_id) VALUES (?,?)',[$entryId,(int)$tag['id']]);}
    }

    private static function syncMeta(int $entryId,array $meta):void
    {
        foreach($meta as $key=>$value){DB::run('DELETE FROM content_entry_meta WHERE entry_id=? AND meta_key=?',[$entryId,$key]);if($value!=='')DB::run('INSERT INTO content_entry_meta (entry_id,meta_key,meta_value,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP)',[$entryId,$key,mb_substr((string)$value,0,2000)]);}
    }

    private static function normalizeDateTime(string $value):?string
    {
        $value=trim($value);if($value==='')return null;$time=strtotime($value);if($time===false)abort(422,'Дата публикации указана неверно.');return date('Y-m-d H:i:s',$time);
    }

    private static function safeStoredPath(string $path):string
    {
        $path=trim(str_replace('\\','/',$path));if($path===''||str_contains($path,'..')||str_starts_with($path,'/'))return '';return preg_match('~^[a-zA-Z0-9_./-]{1,255}$~',$path)?$path:'';
    }
}
