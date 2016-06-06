<?php
namespace RedCat\Framework\Artist;
use RedCat\Framework\PHPConfig\TokenTree;
class InstallEnd extends Artist{
	protected $description = "Finalize installation";
	protected $args = [];
	protected $opts = [];
	protected function exec(){
		if(!is_file($this->cwd.'packages/.htaccess')&&file_put_contents($this->cwd.'packages/.htaccess','Deny from All'))
			$this->output->writeln('packages dir protected');
		$dirs = ['.tmp','.data','content'];
		array_walk($dirs,function($dir){
			if(!is_dir($this->cwd.$dir)){
				if(mkdir($this->cwd.$dir)){
					$this->output->writeln($dir.' directory created');
				}
				else{
					$this->output->writeln($dir.' directory creation failed');
				}
			}
			chmod($dir,0777);
		});
		if(!is_file($this->cwd.'.config.env.php')){
			if(	copy($this->cwd.'.config.env.phps',$this->cwd.'.config.env.php') ){
				$this->output->writeln('.config.env.php created');
			}
			else{
				$this->output->writeln('.config.env.php creation failed');
			}
			$this->mergeSubPackagesConfig();
		}
	}
	private function mergeSubPackagesConfig(){
		$modified = false;
		$path = $this->cwd.'.config.php';
		$config = new TokenTree($path);
		$source = $this->cwd.'packages';
		foreach(glob($source.'/*',GLOB_ONLYDIR) as $p){
			if(is_file($f=$p.'/redcat.config.php')){
				self::merge_recursive($config,new TokenTree($f));
				$modified = true;
			}
		}
		if($modified){
			file_put_contents($path,(string)$config);
		}
	}
	
	private static function merge_recursive(&$a,$b){
		foreach($b as $key=>$value){
			if(is_array($value)&&isset($a[$key])&&is_array($a[$key])){
				$a[$key] = self::merge_recursive($a[$key],$value);
			}
			else{
				$a[$key] = $value;
			}
		}
		return $a;
	}
}