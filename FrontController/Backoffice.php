<?php
namespace RedCat\Framework\FrontController;
use RedCat\Identify\Auth;
use RedCat\Identify\AuthServer;
use RedCat\Identify\Session;
use RedCat\Autoload\Autoload;
use RedCat\Route\Router;
use RedCat\Wire\Di;
use RedCat\Framework\Templix\Templix;
class Backoffice extends FrontController{
	public $pathFS = 'plugin/backoffice';
	function load(){
		$this
			->append(['new:RedCat\Route\Match\Extension','css|js|png|jpg|jpeg|gif'],['new:RedCat\Framework\FrontController\Synaptic',$this->pathFS])
			->append(['new:RedCat\Framework\RouteMatch\ByTmlL10n','',$this->pathFS],function(){
				$this->lock();
				return 'new:RedCat\Framework\Templix\TemplixL10n';
			})
			->append(['new:RedCat\Framework\RouteMatch\ByPhpX','',$this->pathFS],function($paths){
				//$this->lock();
				list($dir,$file,$adir,$afile) = $paths;
				chdir($adir);
				include $file;
			})
		;
		return $this;
	}
	function lock(){
		$Session = $this->di->create(Session::class,['name'=>'redcat_backoffice']);
		$Auth = $this->di->create(Auth::class,[$Session]);
		$AuthServer = $this->di->create(AuthServer::class,[$Auth]);
		$AuthServer->htmlLock('RIGHT_MANAGE',true);
	}
	function __invoke($uri,$domain=null){
		Autoload::getInstance()->addNamespace('',REDCAT_CWD.$this->pathFS.'/php');
		return $this->run($uri);
	}
	function run($path,$domain=null){
		if(!parent::run($path,$domain)){
			$this->di->create(Templix::class)->query(404);
			exit;
		}
		return true;
	}
}