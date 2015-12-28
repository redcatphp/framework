<?php
namespace RedCat\Framework\Artist;
class InstallEnd extends Artist{
	protected $description = "Finalize installation";
	protected $args = [];
	protected $opts = [];
	protected function exec(){
		if(!is_file($this->cwd.'packages/.htaccess')&&file_put_contents($this->cwd.'packages/.htaccess','Deny from All'))
			$this->output->writeln('packages dir protected');
		if(!is_dir($this->cwd.'.tmp')){
			if(mkdir($this->cwd.'.tmp')){
				$this->output->writeln('.tmp created');
			}
			else{
				$this->output->writeln('.tmp creation failed');
			}
		}
	}
}