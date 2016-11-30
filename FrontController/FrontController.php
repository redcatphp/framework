<?php
namespace RedCat\Framework\FrontController;
abstract class FrontController extends \RedCat\Route\FrontController implements RouterInterface{
	protected $matchNamespaces = [
		'RedCat\Framework\RouteMatch',
		'RedCat\Route\Match',
	];
	abstract function load();
	function __call($call,$args){
		$call = ucfirst($call);
		$matcher = null;
		foreach($this->matchNamespaces as $ns){
			if(class_exists($c=$ns.'\\'.$call)){
				$params = array_shift($args);
				if(!is_array($params))
					$params = [$params];
				$matcher = $this->di->get($c,$params);
				break;
			}
		}
		if(!$matcher)
			throw new \Exception('Call to undefined method '.$call.' on '.get_class().' and no class matching with '.$call.' in '.print_r($this->matchNamespaces,true));
		array_unshift($args,$matcher);
		return call_user_func_array([$this,'append'],$args);
	}
	function groupWrap($groups,$wrapparams,$method='prefix',$callback=null,$index=null,$continue=true){
		foreach((array)$wrapparams as $wrapparam){
			foreach((array)$groups as $group){
				$this->$method($wrapparam,$callback,$group,$index,$continue);
			}
		}
	}
}