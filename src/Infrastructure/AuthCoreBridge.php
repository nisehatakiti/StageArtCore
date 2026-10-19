<?php
declare(strict_types=1);
namespace StageArtCore\Infrastructure;
final class AuthCoreBridge{
public const APPLICATION_KEY='stageart';
public const APPLICATION_NAME='StageArt';
public static function available():bool{self::loadRuntime();return defined('AUTHCORE_VERSION')&&defined('AUTHCORE_LOADED')&&class_exists('AuthCore\\ApplicationRegistry')&&class_exists('AuthCore\\UserRepository')&&class_exists('AuthCore\\Auth');}
private static function loadRuntime():void{if(!defined('AUTHCORE_DIR'))return;$files=['Database/Installer.php','ApplicationRegistry.php','UserRepository.php','Auth.php'];foreach($files as $file){$path=AUTHCORE_DIR.'src/'.$file;if(is_file($path))require_once $path;}}
public static function boot():void{if(self::available())\AuthCore\ApplicationRegistry::ensure(self::APPLICATION_KEY,self::APPLICATION_NAME);}
public static function createUser(string$loginId,string$email,string$password):int{if(!self::available())throw new \RuntimeException('AuthCoreが有効ではありません。');return \AuthCore\UserRepository::create(self::APPLICATION_KEY,$loginId,$email,$password);}
public static function current():?array{return self::available()?\AuthCore\Auth::current(self::APPLICATION_KEY):null;}

public static function accounts():array{global $wpdb;if(!self::available())return[];$appTable=$wpdb->prefix.'authcore_applications';$usersTable=$wpdb->prefix.'authcore_user_accounts';$appId=(int)$wpdb->get_var($wpdb->prepare("SELECT application_id FROM {$appTable} WHERE application_key=%s",self::APPLICATION_KEY));if(!$appId)return[];return$wpdb->get_results($wpdb->prepare("SELECT user_account_id,login_id,email,status,email_verified,created_at,last_login_at FROM {$usersTable} WHERE application_id=%d ORDER BY user_account_id ASC",$appId),ARRAY_A)?:[];}
}
