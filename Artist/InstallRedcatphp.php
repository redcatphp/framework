<?php
namespace RedCat\Framework\Artist;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
class InstallRedcatphp extends Artist{
	protected $description = "Install redcatphp package from vendor dir to top level of application";
	protected $args = [];
	protected $opts = [];
	protected function exec(){		
		if($this->recursiveCopy(realpath(__DIR__.'/../../redcatphp'),$this->cwd)){
			$this->output->writeln('redcatphp bootstrap installed');
		}
		else{
			$this->output->writeln('redcatphp bootstrap failed to install');
		}
	}
	private function recursiveCopy($source,$dest){
		if(!is_dir($dest)){
			$r = mkdir($dest, 0755);
			if($r===false) return false;
		}
		$rdirectory = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($rdirectory,RecursiveIteratorIterator::SELF_FIRST);
		foreach($iterator as $item){
			if($item->isDir()){
				$r = mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			}
			else{
				$r = copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			}
			if($r===false) return false;
		}
		return true;
	}
}