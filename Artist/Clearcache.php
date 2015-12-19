<?php
namespace RedCat\Framework\Artist;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
class Clearcache extends Artist{
	protected $description = "Clear the content of .tmp directory at root of application";

	protected $args = [];
	protected $opts = [];
	
	protected $output;
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->output = $output;
		$path = $this->cwd.'.config.php';
		$rm = $this->rmdir($this->cwd.'.tmp');
		if($rm===true)
			$this->output->writeln('cache cleaned');
		elseif($rm===false)
			$this->output->writeln('cache cleaning failed');
		else
			$this->output->writeln('cache was allready empty');
	}
	private function rmdir($dir){
		if(is_dir($dir)){
			$dh = opendir($dir);
			if($dh){
				while(false!==($file=readdir($dh))){
					if($file!='.'&&$file!='..'){
						$fullpath = $dir.'/'.$file;
						if(is_file($fullpath)){
							if(unlink($fullpath))
								$this->output->writeln('deleted '.$fullpath);
							else
								$this->output->writeln('deletion failed '.$fullpath);
						}
						else{
							self::rmdir($fullpath);
						}
					}
				}
				closedir($dh);
			}
			if(rmdir($dir)){
				$this->output->writeln('deleted '.$dir.'/');
				return true;
			}
			else{
				$this->output->writeln('deletion failed '.$dir.'/');
				return false;
			}
		}
	}
}