<?php
namespace RedCat\Framework;
class ComposerScript{
	static function __callStatic($func,$args){
		$c = 'MyApp\\Composer\EventsHandler';
		if(!class_exists($c)&&is_file($inc='plugins/composer/EventsHandler.php')){
			include $inc;
		}
		if(class_exists($c)&&is_callable([$c,$func])){
			call_user_func_array([$c,$func],$args);
		}
	}
}