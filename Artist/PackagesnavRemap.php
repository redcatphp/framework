<?php
namespace RedCat\Framework\Artist;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
class PackagesnavRemap extends Artist{
	protected $description = "Remap navigator assets from bower vendor directory to structured by source type directories";
	protected $args = [];
	protected $opts = ['keep-min'];
	protected function exec(){
		$source = $this->cwd.'packages-nav';
		$configPath = $this->cwd.'.packages-nav';
		if(!is_file($configPath)){
			$this->output->writeln(".packages-nav definition file not found");
			return;
		}
		$map = json_decode(file_get_contents($configPath),true);
		if(!$map){
			$this->output->writeln(".packages-nav definition file syntax error");
			return;
		}
		
		$rdirectory = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($rdirectory,RecursiveIteratorIterator::SELF_FIRST);
		$keepmin = $this->input->getOption('keep-min');
		$lcwd = strlen($this->cwd);
		foreach($iterator as $item){
			$path = (string)$item;
			$se = pathinfo(pathinfo($path,PATHINFO_FILENAME),PATHINFO_EXTENSION);
			if($se=='min'&&!$keepmin) continue;
			$e = pathinfo($path,PATHINFO_EXTENSION);
			$x = explode('/',$iterator->getSubPathName());
			$lib = array_shift($x);
			$relative = implode('/',$x);
			$original = (string)$item;
			$dirname = dirname($relative);
			$basename = basename($relative);
			$libMap = isset($map[$lib])?$map[$lib]:[];
			if(!$libMap) continue;
			switch($e){
				case 'js':
					$extDir = 'js';
				break;
				case 'scss':
				case 'css':
				case 'sass':
				case 'less':
					$extDir = 'css';
				break;
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'ico':
				case 'png':
					$extDir = 'img';
				break;
				case 'svg':
				case 'eot':
				case 'ttf':
				case 'woff':
				case 'woff2':
				case 'otf':
					$extDir = 'font';
				break;
				default:
					continue 2;
				break;
			}
			if(isset($libMap[$relative])){
				if($libMap[$relative]===false) continue;
				if($libMap[$relative]==''||substr($libMap[$relative],-1)=='/'){
					$relative = $libMap[$relative].$basename;
				}
				else{
					$relative = $libMap[$relative];
				}
			}
			else{
				$x = explode('/',trim($dirname,'/'));
				$i =0;
				do{
					$dn = implode('/',$x);
					if(isset($libMap[$dn.'/'])){
						$relative = trim(rtrim($libMap[$dn.'/'],'/').'/'.$basename,'/');
						if($dn!=$dirname){
							$relative = trim(substr($dirname,strlen($dn)).'/'.$relative,'/');
						}
						break;
					}
					array_pop($x);
					$i++;
				}
				while(!empty($x));
			}
			$destination = $this->cwd.$extDir.'/'.$lib.'/'.$relative;
			$dir = dirname($destination);
			if(is_file($destination)){
				unlink($destination);
			}
			elseif(!is_dir($dir)){
				@mkdir($dir,0777,true);
			}
			copy($original,$destination);
			$this->output->writeln(substr($destination,$lcwd).' from '.substr($original,$lcwd));
		}
	}
}