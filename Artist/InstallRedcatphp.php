<?php
namespace RedCat\Framework\Artist;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
class InstallRedcatphp extends Artist{
	protected $description = "Install redcatphp package from vendor dir to top level of application";
	protected $args = [];
	protected $opts = ['force'];
	protected function exec(){		
		if($this->recursiveCopy(realpath(__DIR__.'/../../redcatphp'),$this->cwd)){
			$this->output->writeln('redcatphp bootstrap installed');
		}
		else{
			$this->output->writeln('redcatphp bootstrap failed to install');
		}
	}
	private function recursiveCopy($source,$dest){
		$r = true;
		if(!is_dir($dest)){
			$r = mkdir($dest, 0755);
			if($r===false) return false;
		}
		$force = $this->input->getOption('force');
		$rdirectory = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($rdirectory,RecursiveIteratorIterator::SELF_FIRST);
		$l = strlen($source);
		foreach($iterator as $item){
			$sub = $iterator->getSubPathName();
			if(substr($sub,0,5)=='.git/') continue;
			if($item->isDir()){
				$d = $dest. $iterator->getSubPathName();
				if(!is_dir($d)){
					$r = mkdir($d);
					if($r) $this->output->writeln("directory $d created");
					else $this->output->writeln("directory $d failed to create");
				}
				else{
					$this->output->writeln("directory $d allready exists");
				}
			}
			else{
				$f = $dest.$sub;
				if(!is_file($f)){
					$r = copy($item, $f);
					if($r) $this->output->writeln("file $f copied");
					else $this->output->writeln("file $f failed to copy");
				}
				elseif($force){
					unlink($f);
					$r = copy($item, $f);
					if($r) $this->output->writeln("file $f copied (overwrite)");
					else $this->output->writeln("file $f failed to copy (overwrite)");
				}
				else{
					$this->output->writeln("file $f failed to copy, file allready exists (use --force option to overwrite it)");
				}
			}
			if($r===false) return false;
		}
		return true;
	}
}