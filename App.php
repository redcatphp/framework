<?php
namespace RedCat;
class App extends Wire\Di{
	protected $autoload;
	static function bootstrap(){
		$config = [REDCAT.'.config.php'];
		if(REDCAT_CWD!=REDCAT)
			$config[] = REDCAT_CWD.'.config.php';
		$app = static::load($config);
		if($app['dev']['php']){
			$app->create('RedCat\Debug\ErrorHandler')->handle();
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
		return $app;
	}
	function autoload($base_dir=null,$prefix=''){
		if(!isset($this->autoload)){
			$this->autoload = $this->create('RedCat\Autoload\Autoload');
			$this->autoload->splRegister();
		}
		if(is_array($base_dir))
			$this->autoload->addNamespaces($base_dir);
		elseif(func_num_args()>=1)
			$this->autoload->addNamespace($prefix,$base_dir);			
		return $this->autoload;
	}
}