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
			$rewriteCssUrl = false;
			switch($e){
				case 'js':
					$extDir = 'js';
				break;
				case 'scss':
				case 'css':
				case 'sass':
					$rewriteCssUrl = true;
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
						if($libMap[$dn.'/']===false) continue;
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
			if($rewriteCssUrl){
				/* TODO define $baseUrl
				$content = file_get_contents($destination);
				$content = $this->rewriteCssUrl($content,$baseUrl);
				file_put_contents($destination,$content);
				$this->output->writeln(substr($destination,$lcwd).' urls rewrited');
				*/
			}
		}
	}
	protected function rewriteCssUrl($content,$baseUrl){
		return preg_replace_callback('#url\((.*)\)#',function($match)use($baseUrl){
			$url = $match[1];
			$url = trim($url);
			$url = trim($url,"'\"");
			if(strpos($url,'://')!==false){
				return $match[0]; //no absolute
			}
			if(strpos($url,'#{$')!==false){
				//todo: notify in cli, to resolve manually
				return $match[0]; //no scss/sass var interporlated
			}
			
			$url = $baseUrl.$url;
			
			//clean ..
			$x = explode('/',$url);
			$l = count($x);
			$r = [];
			for($i=0; $i<$l; $i++){
				if($x[$i]=='..'&&!empty($r)){
					array_pop($r);
				}
				else{
					$r[] = $x[$i];
				}
			}
			$url = implode('/',$r);
			
			return 'url("'.$url.'")';
		},$content);
	}
}