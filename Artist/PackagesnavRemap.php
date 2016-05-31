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
		
		if(is_file($configPath)){
			$map = json_decode(file_get_contents($configPath),true);
			if(!$map){
				$this->output->writeln("$configPath definition file syntax error");
				$map = [];
			}
		}
		else{
			$map = [];
		}
		
		$map = $this->mergeSubPackages($source,$map);
		$this->remap($source,$map);
	}
	protected function mergeSubPackages($source,$map){
		foreach(glob($source.'/*',GLOB_ONLYDIR) as $path){
			if(is_file($f=$path.'/.packages-nav')){
				$m = json_decode(file_get_contents($f),true);
				if(!$m){
					$this->output->writeln("$f definition file syntax error");
					continue;
				}
				$lib = basename($path);
				if(!isset($map[$lib]))
					$map[$lib] = [];
				$map[$lib] += $m;
			}
		}
		return $map;
	}
	protected function remap($source,$map){
		
		$rdirectory = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($rdirectory,RecursiveIteratorIterator::SELF_FIRST);
		$keepmin = $this->input->getOption('keep-min');
		$lcwd = strlen($this->cwd);
		$movemap = [];
		foreach($iterator as $item){
			$path = (string)$item;
			$se = pathinfo(pathinfo($path,PATHINFO_FILENAME),PATHINFO_EXTENSION);
			if($se=='min'&&!$keepmin) continue;
			$e = pathinfo($path,PATHINFO_EXTENSION);
			$x = explode('/',$iterator->getSubPathName());
			$lib = array_shift($x);
			$relative = implode('/',$x);
			$relativeFrom = $relative;
			$original = (string)$item;
			$dirname = dirname($relative);
			$basename = basename($relative);
			$libMap = isset($map[$lib])?$map[$lib]:[];
			if($libMap===false) continue;
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
						if($libMap[$dn.'/']===false) continue 2;
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
			$path = $extDir.'/'.$lib.'/'.$relative;
			$destination = $this->cwd.$path;
			$dir = dirname($destination);
			if(is_file($destination)){
				unlink($destination);
			}
			elseif(!is_dir($dir)){
				@mkdir($dir,0777,true);
			}
			copy($original,$destination);
			$movemap[$lib.'/'.$relativeFrom] = [$path,$destination];
			$this->output->writeln(substr($destination,$lcwd).' from '.substr($original,$lcwd));
		}
		foreach($movemap as $relativeFrom=>list($path,$destination)){
			$e = pathinfo($destination,PATHINFO_EXTENSION);
			if(in_array($e,['css','scss','sass','less'])){
				//todo: $notifier
				$content = file_get_contents($destination);
				$content = $this->rewriteCssUrl($content,function($url)use($movemap,$relativeFrom){
					$suffix = '';
					if(false!==($p=strpos($url,'?'))){
						$suffix = substr($url,$p);
						$url = substr($url,0,$p);
					}
					$relativeDir = dirname($relativeFrom);
					$relative = $this->cleanDotInUrl(ltrim($relativeDir.'/'.$url,'/'));
					if(isset($movemap[$relative])){
						$url = $movemap[$relative][0];
					}
					$url = $url.$suffix;
					return $url;
				});
				file_put_contents($destination,$content);
				$this->output->writeln(substr($destination,$lcwd).' urls rewrited');
			}
		}
	}
	protected function rewriteCssUrl($content,$remapUrl,$notifier=null){
		return preg_replace_callback('/url\(([^\\)]+)/s',function($match)use($remapUrl){
			$url = $match[1];
			$url = trim($url);
			$url = trim($url,"'\"");
			if(strpos($url,'://')!==false){ //no absolute
				return $match[0];
			}
			if(strpos($url,'#{$')!==false){ //no scss/sass var interporlated
				if(isset($notifier)){
					call_user_func($notifier,$url);
				}
				return $match[0];
			}

			if(is_string($remapUrl)){
				$url = $remapUrl.$url;
			}
			elseif(is_array($remapUrl)){
				if(isset($remapUrl[$url])){
					$url = $remapUrl[$url];
				}
			}
			else{
				$url = call_user_func($remapUrl,$url);
			}

			$url = $this->cleanDotInUrl($url);
			
			return 'url("'.$url.'")';
		},$content);
	}
	protected function cleanDotInUrl($url){
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
		return $url;
	}
}