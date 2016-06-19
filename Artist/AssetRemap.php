<?php
namespace RedCat\Framework\Artist;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
class AssetRemap extends Artist{
	use AssetTrait;
	protected $description = "Remap navigator assets from bower vendor directory to structured by source type directories";
	protected $args = [];
	protected $opts = ['keep-min'];
	protected function exec(){
		$this->loadAssetInstallerPaths();
		
		$source = $this->cwd.$this->bowerAssetDir;
		$configPath = $this->cwd.'.bower-asset';
		
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
	
	protected function normalizeConfigValue($v){
		if(is_string($v)){
			$v = ['/'=>$v];
		}
		elseif($v===true){
			$v = [];
		}
		return $v;
	}
	protected function mergeSubPackages($source,$map){
		foreach(glob($source.'/*',GLOB_ONLYDIR) as $path){
			if(is_file($f=$path.'/.bower-asset')){
				$m = json_decode(file_get_contents($f),true);
				if(!$m){
					$this->output->writeln("$f definition file syntax error");
					continue;
				}
				$lib = basename($path);
				if(!isset($map[$lib])){
					$map[$lib] = $m;
				}
				elseif($map[$lib]===false||$m===false){
					continue;
				}
				else{
					$map[$lib] = $this->normalizeConfigValue($map[$lib]);
					$m = $this->normalizeConfigValue($m);
					$map[$lib] += $m;
				}
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
		$defaultMap = isset($map['/'])?$map['/']:true;
		if(is_string($defaultMap)) $defaultMap = ['/'=>$defaultMap];
		
		foreach($iterator as $item){
			$original = $path = (string)$item;
			$se = pathinfo(pathinfo($path,PATHINFO_FILENAME),PATHINFO_EXTENSION);
			if($se=='min'&&!$keepmin) continue;
			$e = pathinfo($path,PATHINFO_EXTENSION);
			$x = explode('/',$iterator->getSubPathName());
			$lib = array_shift($x);
			$relative = implode('/',$x);
			$relativeFrom = $relative;
			$dirname = dirname($relative);
			$basename = basename($relative);
			$libMap = isset($map[$lib])?$map[$lib]:$defaultMap;
			
			if($libMap===false) continue;
			elseif(is_string($libMap)) $libMap = ['/'=>$libMap];
			elseif($libMap===true) $libMap = is_bool($defaultMap)?[]:$defaultMap;
			
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
				$i = 0;
				$match = false;
				do{
					$dn = implode('/',$x);
					if(isset($libMap[$dn.'/'])){
						if($libMap[$dn.'/']===false) continue 2;
						$val = trim($libMap[$dn.'/'],'/');
						$rel = ltrim(substr($dirname,strlen($dn)),'/');
						$relative = trim($val.'/'.$rel.'/'.$basename,'/');
						$match = true;
						break;
					}
					array_pop($x);
					$i++;
				}
				while(!empty($x));
				
				if(!$match&&isset($libMap['/'])){
					if($libMap['/']===false) continue;
					$val = trim($libMap['/'],'/');
					$rel = ltrim($dirname,'/');
					$relative = trim($val.'/'.$rel.'/'.$basename,'/');
				}
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
					$relative = self::cleanDotInUrl(ltrim($relativeDir.'/'.$url,'/'));
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
		return preg_replace_callback('/url\(([^\\)]+)\)/s',function($match)use($remapUrl){
			$url = $match[1];
			$url = trim($url);
			$url = trim($url,"'");
			$url = trim($url,'"');
			if(strpos($url,'://')!==false){ //no absolute
				return $match[0];
			}
			if(substr($url,0,5)=='data:'){ //no data
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

			$url = self::cleanDotInUrl($url);
			
			return 'url("'.$url.'")';
		},$content);
	}
}