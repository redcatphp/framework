<?php
namespace RedCat\Framework\Artist;
use RedCat\Framework\PHPConfig\TokenTree;
class Config extends Artist{
	protected $description = "Update .config.php file at root of application";

	protected $args = [
		'key'=>"The key in '$' associative array, recursive access with dot, eg: dev.php for 'dev'=>['php'=>...]",
		'value'=>"The value to assign",
	];
	protected $opts = [
		'unset'=>'unset a key in array',
		'push'=>'append a value in array',
		'unshift'=>'prepend a value in array',
	];
	
	protected function exec(){
		$key = $this->input->getArgument('key');
		$value = $this->input->getArgument('value');
		
		$unset = $this->input->getOption('unset');
		$push = $this->input->getOption('push');
		$unshift = $this->input->getOption('unshift');
		
		$path = $this->cwd.'.config.php';
		$config = new TokenTree($path);
		if(!$key){
			$print = $config->var_codify($config['$']);
		}
		elseif($unset){
			unset($config['$.'.$key]);
			$print = "$key unsetted in $path";
			file_put_contents($path,(string)$config);
		}
		elseif(!isset($value)){
			$print = $config->var_codify($config['$.'.$key]);
		}
		else{
			$ref = &$config['$.'.$key];
			if($push){
				if(!is_array($ref))
					$ref = (array)$ref;
				array_push($ref,$value);
				$print = "$value appened to $key in $path";
			}
			if($unshift){
				if(!is_array($ref))
					$ref = (array)$ref;
				array_unshift($ref,$value);
				$print = "$value prepended to $key in $path";
			}
			if(!$push&&!$unshift){
				$config['$.'.$key] = $value;
				$print = "$key setted to $value in $path";
			}
			file_put_contents($path,(string)$config);
		}
		$this->output->writeln($print);
	}	
}