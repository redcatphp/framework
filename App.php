<?php
namespace RedCat\Framework;
use RedCat\Wire\Di;
use RedCat\Debug\ErrorHandler;
use RedCat\Autoload\Autoload;
class App extends Di{
	protected $autoload;
	protected static $singleton;
	static function get(){
		if(!isset(static::$singleton))
			self::set();
		return static::$singleton;
	}
	static function set(){
		if(func_num_args())
			static::$singleton = func_get_arg(0);
		else{
			$config = [REDCAT.'.config.php'];
			if(REDCAT_CWD!=REDCAT)
				$config[] = REDCAT_CWD.'.config.php';
			static::$singleton = static::load($config);
		}
	}
	static function bootstrap($configMap=null){
		if($configMap)
			self::set(self::load($configMap));
		$app = static::get();
		if($app['dev']['php']){
			$app->create(ErrorHandler::class)->handle();
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
		if($app['autoload']){
			foreach($app['autoload'] as $autoload){
				call_user_func_array([$app,'autoload'],(array)$autoload);
			}
		}
		return $app;
	}
	function autoload($base_dir=null,$prefix=''){
		if(!isset($this->autoload)){
			$this->autoload = $this->create(Autoload::class);
			$this->autoload->splRegister();
		}
		if(is_array($base_dir))
			$this->autoload->addNamespaces($base_dir);
		elseif(func_num_args()>=1)
			$this->autoload->addNamespace($prefix,$base_dir);			
		return $this->autoload;
	}
}