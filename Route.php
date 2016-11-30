<?php
namespace RedCat\Framework;

use RedCat\Strategy\Di;
use RedCat\Strategy\CallTrait;
use FoxORM\Bases;
use RedCat\Route\Request;
use RedCat\Route\Router;
use RedCat\Route\Url;
use RedCat\Framework\FrontController\AssetLoader;
use RedCat\Framework\FrontController\FrontController;
use RedCat\Framework\FrontController\RenderInterface;
use RedCat\Framework\Templix\Templix;
use RedCat\Framework\Templix\TemplixL10n;

class Route extends FrontController{
	use CallTrait;
	
	protected $uri;	
	protected $controller;
	protected $controllerNamespace = 'MyApp\Controller';
	protected $templixSubstitution = Templix::class;
	protected $l10n;
	protected $useShared;
	function __construct(Router $router,Request $request, Di $di, $l10n=false, $useShared=false){
		$this->l10n = $l10n;
		$this->useShared = $useShared;
		parent::__construct($router,$request,$di);
		if($this->l10n){
			$this->templixSubstitution = TemplixL10n::class;
		}
	}
	function byTml(){
		$method = __FUNCTION__;
		if($this->l10n){
			$method .= 'L10n';
		}
		return call_user_func([$this,'__call'],$method,func_get_args());
	}
	function load(){
		$this->callLoadRoutes();
	}
	protected function _loadRoutes(){
		
		$this->extension('css|js|png|jpg|jpeg|gif','new:'.AssetLoader::class);
		
		$this->extension('jsonp',[$this,'outputJsonp']);
		$this->extension('json',[$this,'outputJson']);
		$this->append([$this,'findControllerRenderer'],[$this,'controllerApi']);
		$this->append([$this,'findController'],[$this,'outputTml']);
		
		$this->byTml(['','view'],[$this,'view']);
		if($this->useShared){
			$this->byTml(['','shared/view'],[$this,'view']);
		}
		$this->prepend('401',[$this,'view']);
		$this->prepend('403',[$this,'view']);
		$this->prepend('404',[$this,'view']);
		$this->prepend('500',[$this,'view']);
	}
	
	function findControllerRenderer($uri){
		$controller = $this->findController($uri);
		if($controller){
			list($controllerClass,$uri) = $controller;
			if(is_subclass_of($controllerClass,RenderInterface::class))
				return $controllerClass;
		}
	}
	function findController($uri){
		$ctrl = $this->controllerNamespace.'\\'.ucfirst(str_replace(['  ',' '], ['_','\\'], ucwords(str_replace(['/','-'], [' ','  '], $uri))));
		if(substr($ctrl,-1)=='\\') $ctrl .= '_';
		if(class_exists($ctrl)&&(new \ReflectionClass($ctrl))->isInstantiable())
			return [$ctrl,$uri];
	}
	
	function _controllerApi($controllerClass, $params=[], Di $di){
		$controller = $di($controllerClass);
		$this->controller = $controller;
		$method = isset($this->request['method'])?$this->request['method']:'__invoke';
		$params += isset($this->request['params'])?$this->request['params']->getArray():[];
		if($method!='__invoke'&&substr($method,0,1)=='_'){
			throw new \RuntimeException("Underscore prefixed method \"$method\" is not allowed to public api access");
		}
		if(method_exists($controller, $method)){
			if(!(new \ReflectionMethod($controller, $method))->isPublic()) {
				throw new \RuntimeException("The called method is not public");
			}
			return $di->method($controller,$method,$params);
		}
	}
	function _outputTml($params){
		
		list($controllerClass,$uri) = $params;
		
		$data = $this->controllerApi($controllerClass);
		
		if($data===false)
			return;
		
		if(isset($data['_view'])){
			$uri = $data['_view'];
		}
		
		foreach(get_object_vars($this->controller) as $k=>$v){
			$template[$k] = $v;
		}
		$this->view($uri, $data);
	}
	function outputJson($params){
		if($params=$this->findController(array_shift($params))){
			$data = $this->controllerApi(array_shift($params));
		}
		else{
			$data = ['error'=>404];
		}
		if(!headers_sent()){
			header('Content-type:application/json;charset=utf-8');
		}
		echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}
	function outputJsonp($params){
		$callback = $this->request['callback'];
		unset($this->request['callback']);
		if($params=$this->findController(array_shift($params))){
			$data = $this->controllerApi(array_shift($params));
		}
		else{
			$data = ['error'=>404];
		}
		if(!headers_sent()){
			header('Content-type:application/javascript;charset=utf-8');
		}
		echo $callback.'('.json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).');';
	}
	
	function resolveRoute($href){
		$routeVars = [
			
		];
		$routeKeys = array_keys($routeVars);
		foreach($routeKeys as &$rk) $rk = '${'.$rk.'}';
		$routeValues = array_values($routeVars);
		return str_replace($routeKeys,$routeValues,$href);
	}
	
	function view($path, $data=[]){
		$templix = $this->di->get($this->templixSubstitution);
		return $templix($path,$data);
	}
	function redirectBack(Url $url){
		header('Location: '.$url->getBaseHref().(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:''),true,302);
		exit;
	}
	function isAjax(){
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])&&strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest';
	}
	function run($path,$domain=null){
		if(!parent::run($path,$domain)){
			$this->view(404);
			exit;
		}
		return true;
	}
}