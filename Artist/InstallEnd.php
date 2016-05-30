<?php
namespace RedCat\Framework\Artist;
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
		if(	copy($this->cwd.'.config.env.phps',$this->cwd.'.config.env.php') ){
			$this->output->writeln('.config.env.php created');
		}
		else{
			$this->output->writeln('.config.env.php creation failed');
		}
	}
}