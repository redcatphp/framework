<?php
namespace RedCat\Framework;
use RedCat\Strategy\Di;
use RedCat\Strategy\App as StrategyApp;
use RedCat\Debug\ErrorHandler;
class App extends StrategyApp{
	protected $loader;
	protected static $singleton;
	static function getMe(){
		if(!isset(static::$singleton))
			self::set();
		return static::$singleton;
	}
	static function set(){
		if(func_num_args())
			static::$singleton = func_get_arg(0);
		else{
			$config = [REDCAT.'config/default.php'];
			if(REDCAT_CWD!=REDCAT)
				$config[] = REDCAT_CWD.'config/default.php';
			$config[] = REDCAT_CWD.'config/env.php';
			$config[] = REDCAT_CWD.'config/app.php';
			static::$singleton = static::load($config);
		}
	}
	static function bootstrap($loader=null){
		$app = static::getMe();
		if($loader){
			$app['loader'] = $loader;
		}
		if(isset($app['dev'])&&isset($app['dev']['php'])&&$app['dev']['php']){
			$app->getMe(ErrorHandler::class,[$app['dev']['php']])->handle();
		}
		else{
			error_reporting(0);
			ini_set('display_startup_errors',false);
			ini_set('display_errors',false);
			register_shutdown_function(function(){
				$error = error_get_last();
				if($error&&$error['type']&(E_ERROR|E_USER_ERROR|E_PARSE|E_CORE_ERROR|E_COMPILE_ERROR|E_RECOVERABLE_ERROR))
					header('Location: /500',true,302);
			});
		}
		if($app['loader']&&isset($app['autoload'])){
			foreach((array)$app['autoload'] as $autoload){
				list($dir,$ns) = $autoload;
				$app['loader']->addPsr4($ns,$dir);
			}
		}
		return $app;
	}
}