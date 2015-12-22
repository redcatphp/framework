<?php
namespace RedCat\Framework\FrontController;
class FrontController extends \RedCat\Route\FrontController{
	protected $matchNamespaces = [
		'RedCat\Framework\RouteMatch',
		'RedCat\Route\Match',
	];
	function __call($call,$args){
		$call = ucfirst($call);
		$matcher = null;
		foreach($this->matchNamespaces as $ns){
			if(class_exists($c=$ns.'\\'.$call)){
				$matcher = $this->di->create($c,[array_shift($args)]);
				break;
			}
		}
		if(!$matcher)
			throw new \Exception('Call to undefined method '.$call.' on '.get_class().' and no class matching with '.$call.' in '.print_r($this->matchNamespaces,true));
		array_unshift($args,$matcher);
		return call_user_func_array([$this,'append'],$args);
	}
}