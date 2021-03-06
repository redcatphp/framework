<?php
namespace RedCat\Framework\FrontController;
use RedCat\Route\Router;
use RedCat\Strategy\Di;
use RedCat\Stylize\Server as StylizeServer;
use JShrink\Minifier as JSMin;
class AssetLoader {
	protected $bowerAssetDir = 'vendor/bower-asset';
	protected $npmAssetDir = 'vendor/npm-asset';
	
	protected $pathFS;
	protected $expires = 2592000;
	protected $allowedExtensions = ['css','js','jpg','jpeg','png','gif'];
	protected $dirs = [''];
	protected $subPackageDirs = [];
	protected $di;
	
	public $devJs;
	public $devCss;
	
	public $prefixMinPath = '.tmp/min/';
	public $useModIncludeByHost;
	
	function __construct($pathFS='',$devJs=true,$devCss=true,Di $di,$useModIncludeByHost=false){
		$this->pathFS = rtrim($pathFS,'/');
		if(!empty($this->pathFS))
			$this->pathFS .= '/';
		$this->devJs = $devJs;
		$this->devCss = $devCss;
		$this->di = $di;
		$this->useModIncludeByHost = $useModIncludeByHost;
		$this->loadAssetInstallerPaths();
		$this->subPackageDirs[] = $this->bowerAssetDir;
		$this->subPackageDirs[] = $this->npmAssetDir;
	}
	function __invoke($params){
		list($filename,$extension) = $params;
		$this->appendDir('shared');
		$this->load($this->pathFS.$filename.'.'.$extension);
	}
	
	
	function setDirs($d){
		$this->dirs = (array)$d;
		foreach($this->dirs as $k=>$d){
			if($d)
				$this->dirs[$k] = rtrim($d,'/').'/';
		}
	}
	function prependDir($d){
		array_unshift($this->dirs,$d?rtrim($d,'/').'/':'');
	}
	function appendDir($d){
		$this->dirs[] = $d?rtrim($d,'/').'/':'';
	}
	function load($k){
		$extension = strtolower(pathinfo($k,PATHINFO_EXTENSION));
		if(!in_array($extension,$this->allowedExtensions)){
			http_response_code(403);
			exit;
		}
		$k = preg_replace('#(.*).redcat-deploy-[a-z0-9]{1,9}.(min.|)(js|css)#','$1.$2$3',$k);
		switch($extension){
			case 'js':
				foreach($this->dirs as $d){
					if(is_file($f=$d.$k)){
						header('Expires: '.gmdate('D, d M Y H:i:s', time()+$this->expires).'GMT');
						header('Content-Type: application/javascript; charset:utf-8');
						$this->fileCache($f);
						readfile($f);
						return;
					}
				}
				if(strpos($k,',')!==false){ //concat
					$minify = !isset($_GET['src']);
					$x = explode(',',$k);
					$concat = '';
					foreach($x as $_k){
						$_k = urldecode($_k);
						if(strpos($_k,'://')!==false){
							$concat .= file_get_contents($_k);
							continue;
						}
						if(substr($_k,-7,-3)=='.min'){
							$_k2 = substr($_k,0,-7).'.js';
						}
						else{
							$_k2 = substr($_k,0,-3).'.min.js';
						}
						$found = false;
						foreach($this->dirs as $d){
							if(is_file($_f=$d.$_k)){
								$c = file_get_contents($_f);
								if(!$minify) $concat .= "/* $_f */\n";
								$concat .= $c."\n";
								$found = true;
								break;
							}
						}
						if(!$found){
							foreach($this->dirs as $d){
								if(is_file($_f=$d.$_k2)){
									$c = file_get_contents($_f);
									if(!$minify) $concat .= "/* $_f */\n";
									$concat .= $c."\n";
									$found = true;
									break;
								}
							}
						}
						if(!$found){
							echo "js library not found '$_k'";
							http_response_code(404);
							return;
						}
					}
					
					$f = $this->prefixMinPath.$k;
					$dir = dirname($f);
					if($minify){
						$concat = JSMin::minify($concat,['flaggedComments'=>false]);
					}
					if(!is_dir($dir))
						@mkdir($dir,0777,true);
					file_put_contents($f,$concat,LOCK_EX);
					if($minify){
						$gzfile = $f.'.gz';
						$fp = gzopen($gzfile, 'w9');
						gzwrite($fp,$concat);
						gzclose($fp);
					}
					
					header('Expires: '.gmdate('D, d M Y H:i:s', time()+$this->expires).'GMT');
					header('Content-Type: application/javascript; charset:utf-8');
					$this->fileCache($f);
					readfile($f);
					return;
				}
				
				if(substr($k,-7,-3)=='.min'){
					if($this->useModIncludeByHost)
						$kv = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on'?'https':'http').'://'.$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']&&(int)$_SERVER['SERVER_PORT']!=80?':'.$_SERVER['SERVER_PORT']:'').'/'.substr($k,0,-7).'.js';
					else
						$kv = substr($k,0,-7).'.js';
					if(!$this->minifyJS($kv,$k))
						http_response_code(404);
					return;
				}
				http_response_code(404);
			break;
			case 'css':
				foreach($this->dirs as $d){
					if(is_file($f=$d.$k)){
						header('Expires: '.gmdate('D, d M Y H:i:s', time()+$this->expires).'GMT');
						header('Content-Type: text/css; charset:utf-8');
						$this->fileCache($f);
						readfile($f);
						return;
					}
				}
				if(substr($k,-8,-4)=='.min'){
					if(!$this->minifyCSS(substr($k,0,-8).'.css'))
						http_response_code(404);
					return;
				}
				foreach($this->dirs as $d){
					$file = $d.dirname($k).'/'.pathinfo($k,PATHINFO_FILENAME).'.scss';
					if(is_file($file)){
						if($this->scss($k)===false){
							http_response_code(404);
						}
						return;
					}
				}
				http_response_code(404);
			break;
			case 'png':
			case 'jpg':
			case 'jpeg':
			case 'gif':
				header('Content-Type:image/'.$extension.'; charset=utf-8');
				foreach($this->dirs as $d){
					if(is_file($f=$d.$k)){
						$this->fileCache($f);
						readfile($f);
						return;
					}
				}
				foreach($this->dirs as $d){
					if(is_file($f=$d.'img/404.png')){
						http_response_code(404);
						$this->fileCache($f);
						readfile($f);
						return;
					}
				}
				http_response_code(404);
			break;
		}
	}
	protected function minifyJS($f,$min){
		if(strpos($f,'://')===false&&!is_file($f))
			return false;
		set_time_limit(0);
		$c = JSMin::minify(file_get_contents($f),['flaggedComments' => false]);
		if($this->devJs<2){
			
			if($this->prefixMinPath)
				$min = $this->prefixMinPath.$min;
			
			$dir = dirname($min);
			if(!is_dir($dir))
				@mkdir($dir,0777,true);
			file_put_contents($min,$c,LOCK_EX);
			
			$gzfile = $min.'.gz';
			$fp = gzopen($gzfile, 'w9');
			gzwrite($fp,$c);
			gzclose($fp);
		}
		if(!headers_sent())
			header('Content-Type:application/javascript; charset=utf-8');
		echo $c;
		return true;
	}
	
	protected function minifyCSS($file){
		foreach($this->dirs as $d){
			if(is_file($f=$d.$file)||is_file($f=$d.dirname($file).'/'.pathinfo($file,PATHINFO_FILENAME).'.scss')){
				$e = pathinfo($f,PATHINFO_EXTENSION);
				if($e=='scss'){
					ob_start();
					$this->scss($f);
					$c = ob_get_clean();
				}
				else
					$c = file_get_contents($f);
				
				$css = new CSSmin();
				$c = $css->run($c);
				//$c = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    ',"\ r \ n", "\ r", "\ n", "\ t"],'',preg_replace( '! / \ *[^*]* \ *+([^/][^*]* \ *+)*/!','',preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!','',$c)))
				
				if($this->devCss<2){
					$dir = dirname($file);
					$min = $dir.'/'.pathinfo($file,PATHINFO_FILENAME).'.min.css';
					
					if($this->prefixMinPath){
						$min = $this->prefixMinPath.$min;
						$dir = $this->prefixMinPath.$dir;
					}
					
					if(!is_dir($dir))
						mkdir($dir,0777,true);
					file_put_contents($min,$c,LOCK_EX);
					
					$gzfile = $min.'.gz';
					$fp = gzopen($gzfile, 'w9');
					gzwrite($fp,$c);
					gzclose($fp);
				}
				if(!headers_sent())
					header('Content-Type:text/css; charset=utf-8');
				echo $c;
				return true;
			}
		}
		exit;
		return false;
	}
	protected function scss($path) {
		$from = [];
		foreach($this->dirs as $d){
			$dirname = dirname($path);
			if(is_dir($dir=$d.$dirname)){
				$from[] = $dir;
			}
			if(strpos($dirname,'/')!==false){
				
				foreach($this->subPackageDirs as $sp){
					$sp = rtrim($sp,'/').'/';
					$l = strlen($sp);
					if($sp==substr($dirname,0,$l)){
						$x = explode('/',substr($dirname,$l));
						$dir = $d.$sp.$x[0];
						if(is_dir($dir)&&!in_array($dir,$from)){
							$from[] = $dir;
						}
						break;
					}
				}
				
				$x = explode('/',$dirname);
				$dir = $d.$x[0];
				if(is_dir($dir)&&!in_array($dir,$from)){
					$from[] = $dir;
				}
			}
			if(is_dir(getcwd().'/'.$d)&&!in_array($d,$from)){
				$from[] = $d;
			}
			if(is_dir($dir=$d.'css')&&!in_array($dir,$from)){
				$from[] = $dir;
			}
		}
		$scss = $this->di->get(StylizeServer::class);
		$scss->serveFrom(pathinfo($path,PATHINFO_FILENAME).'.scss',$from);
	}
	function fileCache($output){
		$mtime = filemtime($output);
		$etag = $this->fileEtag($output);
		header('Last-Modified: '.gmdate('D, d M Y H:i:s',$mtime).' GMT', true);
		header('Etag: '.$etag);
		if(!$this->isModified($mtime,$etag)){
			http_response_code(304);
			exit;
		}
	}
	function isModified($mtime,$etag){
		return !((isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])&&@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])>=$mtime)
			||(isset($_SERVER['HTTP_IF_NONE_MATCH'])&&$_SERVER['HTTP_IF_NONE_MATCH'] == $etag));
	}
	function fileEtag($file){
		$s = stat($file);
		return sprintf('%x-%s', $s['size'], base_convert(str_pad($s['mtime'], 16, "0"),10,16));
	}
	function devLevel(){
		if(func_num_args()){
			$this->devLevel = 0;
			foreach(func_get_args() as $l){
				$this->devLevel = $this->devLevel|$l;
			}
		}
		return $this->devLevel;
	}
	
	function loadAssetInstallerPaths(){
		$cwd = property_exists($this,'cwd')?$this->cwd:getcwd();
		if(is_file($cwd.'composer.json')){
			$json = json_decode(file_get_contents($cwd.'composer.json'),true);
			if(is_array($json)){
				if(isset($json['config']['vendor-dir'])){
					$this->bowerAssetDir = $json['config']['vendor-dir'].'/bower-asset';
					$this->npmAssetDir = $json['config']['vendor-dir'].'/npm-asset';
				}
				if(isset($json['extra']['asset-installer-paths']['bower-asset-library'])){
					$this->bowerAssetDir = $json['extra']['asset-installer-paths']['bower-asset-library'];
				}
				if(isset($json['extra']['asset-installer-paths']['npm-asset-library'])){
					$this->npmAssetDir = $json['extra']['asset-installer-paths']['npm-asset-library'];
				}
			}
		}
	}
}
