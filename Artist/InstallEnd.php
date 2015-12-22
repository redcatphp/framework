<?php
namespace RedCat\Framework\Artist;
class InstallEnd extends Artist{
	protected $description = "Finalize installation";
	protected $args = [];
	protected $opts = [];
	protected function exec(){
		if(!is_file($this->cwd.'vendor/.htaccess')&&file_put_contents($this->cwd.'vendor/.htaccess','Deny from All'))
			$this->output->writeln('vendor dir protected');
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