<?php
declare(strict_types=1);
namespace StageArtCore\Infrastructure;
final class AuthCoreBridge{
public const APPLICATION_KEY='stageart';
public const APPLICATION_NAME='StageArt';
public static function available():bool{return defined('AUTHCORE_VERSION')&&class_exists('AuthCore\\ApplicationRegistry')&&class_exists('AuthCore\\UserRepository')&&class_exists('AuthCore\\Auth');}
public static function boot():void{if(self::available())\AuthCore\ApplicationRegistry::ensure(self::APPLICATION_KEY,self::APPLICATION_NAME);}
public static function createUser(string$loginId,string$email,string$password):int{if(!self::available())throw new \RuntimeException('AuthCoreが有効ではありません。');return\AuthCore\UserRepository::create(self::APPLICATION_KEY,$loginId,$email,$password);}
public static function current():?array{return self::available()?\AuthCore\Auth::current(self::APPLICATION_KEY):null;}
}
