<?php
namespace RedCat\Framework;
class ComposerScript{
	static function __callStatic($func,$args){
		$c = 'MyApp\\ComposerScript';
		if(!class_exists($c)&&is_file('plugins/composer/ComposerScript.php')){
			include 'plugins/composer/ComposerScript.php';
		}
		if(class_exists($c)&&is_callable([$c,$func])){
			call_user_func_array([$c,$func],$args);
		}
	}
}