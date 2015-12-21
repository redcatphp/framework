<?php
namespace RedCat\Framework\Artist;
class Up extends Artist{
	protected $description = "Turn off maintenance mode";
	protected $args = [];
	protected $opts = [];
	protected function exec(){
		$path = $this->cwd.'.htaccess';
		$path = $this->cwd.'htaccess-307';
		if(!is_file($this->cwd.'htaccess-up')){
			$this->output->writeln('Application is not in maintenance');
			return;
		}
		file_put_contents($this->cwd.'.htaccess',file_get_contents($this->cwd.'htaccess-up'));
		unlink($this->cwd.'maintenance.php');
		unlink($this->cwd.'htaccess-up');
		$this->output->writeln('Application is not anymore in maintenance from now');
	}
}