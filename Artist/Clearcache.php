<?php
namespace RedCat\Framework\Artist;
class Clearcache extends Artist{
	protected $description = "Clear the content of .tmp directory at root of application";
	protected $args = [];
	protected $opts = [];
	
	private $exceptions = ['sessions'];
	private $tmpPath;
	protected function exec(){
		$path = $this->cwd.'.config.php';
		$this->tmpPath = $this->cwd.'.tmp';
		$rm = $this->rmdir($this->tmpPath,true);
		if($rm===true)
			$this->output->writeln('cache cleaned');
		elseif($rm===false)
			$this->output->writeln('cache cleaning failed');
		else
			$this->output->writeln('cache was allready empty');
	}
	private function rmdir($dir, $keepRoot=false){
		$ok = null;
		$relative = substr($dir,strlen($this->tmpPath)+1);
		if(in_array($relative,$this->exceptions))
			return;
		if(is_dir($dir)){
			$dh = opendir($dir);
			if($dh){
				while(false!==($file=readdir($dh))){
					if($file!='.'&&$file!='..'){
						$fullpath = $dir.'/'.$file;
						if(is_file($fullpath)){
							if(unlink($fullpath)){
								$this->output->writeln('deleted '.$fullpath);
								if($ok!==false){
									$ok = true;
								}
							}
							else{
								$this->output->writeln('deletion failed '.$fullpath);
								$ok = false;
							}
						}
						else{
							$r = $this->rmdir($fullpath);
							if($r===true&&$ok!==false)
								$ok = true;
							elseif($r===false)
								$ok = false;
						}
					}
				}
				closedir($dh);
			}
			if(!$keepRoot){
				if(rmdir($dir)){
					$this->output->writeln('deleted '.$dir.'/');
					if($ok!==false){
						$ok = true;
					}
				}
				else{
					$this->output->writeln('deletion failed '.$dir.'/');
					$ok = false;
				}
			}
		}
		return $ok;
	}
}