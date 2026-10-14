<?php
// Exercise the actual generated edition, rather than only its source template.
$edition=$argv[1] ?? 'free';
$zip=new ZipArchive;
if ($zip->open(__DIR__.'/../dist/plugins/usp-form-builder-1.1.1-'.$edition.'.zip')!==true) { throw new RuntimeException('Build plugin packages first.'); }
$temp=sys_get_temp_dir().'/usp-package-test-'.bin2hex(random_bytes(8));
mkdir($temp);$zip->extractTo($temp);$zip->close();
register_shutdown_function(function () use ($temp) {
    $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) { $file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname()); }
    rmdir($temp);
});
$root=$temp.'/usp-form-builder';
define('ABSPATH',$temp.'/');
function __($s,$d=''){return $s;}
function esc_html__($s,$d=''){return htmlspecialchars($s,ENT_QUOTES);}
function esc_attr($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_html($s){return esc_attr($s);}
function esc_html_e($s,$d=''){echo esc_html($s);}
function esc_attr_e($s,$d=''){echo esc_attr($s);}
function sanitize_key($s){return preg_replace('/[^a-z0-9_-]/','',strtolower($s));}
function absint($v){return abs((int)$v);}
function wp_json_encode($v){return json_encode($v);}
function wp_kses_post($v){return strip_tags($v,'<p><strong><a>');}
function is_admin(){return true;}
function wp_nonce_field($action,$name){echo '<input name="'.esc_attr($name).'" value="test">';}
function wp_strip_all_tags($s){return strip_tags($s);}
class USP_Security {
    public static $active=false;
    public static function is_pro_active(){return self::$active;}
    public static function get_settings(){return ['anti_spam_provider'=>'none'];}
    public static function generate_time_token($id){return 'test';}
}
class USP_DB {
    public static $fields=[];
    public static function get_form($id){return ['status'=>'publish','title'=>'Contact','fields_data'=>self::$fields,'settings'=>[]];}
}
require $root.'/includes/class-usp-form.php';
function verify($ok,$message){if(!$ok){throw new RuntimeException($message);}}
$types=USP_Form::get_supported_field_types();
foreach(['text','email','textarea','select','checkbox','radio','number','date','time','url','tel','html','hidden'] as $type){verify(isset($types[$type]),'Missing Free field '.$type);}
foreach(USP_Form::get_pro_field_types() as $type){verify(isset($types[$type])===($edition==='pro'),'Edition field mismatch: '.$type);}
$render=new ReflectionMethod('USP_Form','render_field');$render->setAccessible(true);
foreach(['text','email','textarea','select','checkbox','radio','number','date','time','url','tel'] as $type){
    ob_start();$render->invoke(null,['type'=>$type,'name'=>'test','label'=>'Example','required'=>true,'options'=>['First','Second']],1,0);$html=ob_get_clean();
    verify(strpos($html,'name="test')!==false,'Field did not render: '.$type);
}
foreach(USP_Form::get_pro_field_types() as $type){
    ob_start();$render->invoke(null,['type'=>$type,'name'=>'premium','label'=>'Premium'],1,0);$html=ob_get_clean();
    verify(strpos($html,'requires an active')!==false,'Unlicensed field was not blocked: '.$type);
}
USP_DB::$fields=[['type'=>'text','name'=>'name','label'=>'Name']];
$html=USP_Form::render_form_html(1);
verify(strpos($html,'name="name"')!==false && strpos($html,'usp_submit_form')!==false,'Free form failed to render');
USP_DB::$fields=[['type'=>'signature','name'=>'signature','label'=>'Sign']];
verify(strpos(USP_Form::render_form_html(1),'temporarily unavailable')!==false,'Imported Pro form must fail closed');
if($edition==='pro'){
    USP_Security::$active=true;
    ob_start();$render->invoke(null,['type'=>'signature','name'=>'signature','label'=>'Sign'],1,0);$html=ob_get_clean();
    verify(strpos($html,'<canvas')!==false,'Licensed Pro signature did not render');
} else {
    verify(!file_exists($root.'/includes/class-usp-address.php')&&!is_dir($root.'/assets/locations'),'Free includes Pro implementation/data');
}
echo "Verified generated $edition package fields, form rendering, locked Pro forms and edition separation.\n";
