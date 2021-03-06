<?php
namespace RedCat\Framework\RouteMatch;
use RedCat\Route\MatchInterface;
class ByTml implements MatchInterface{
	protected $uriDir;
	protected $physicalDir;
	protected $extension;
	function __construct($uriDir=null,$physicalDir=null,$extension='.tml'){
		$this->uriDir = $uriDir;
		$this->physicalDir = isset($physicalDir)?$physicalDir:$uriDir;
		$this->extension = $extension;
	}
	function __invoke($uri,$domain=null){
		if($this->uriDir&&strpos($uri,$this->uriDir.'/')!==0)
			return;
		if($this->uriDir)
			$uri = substr($uri,strlen($this->uriDir));
		$file = $this->physicalDir.'/'.ltrim($uri,'/').$this->extension;
		if(is_file($file))
			return [$this->physicalDir,$uri];
	}
	function getMatch(){
		return [$this->uriDir=>$this->physicalDir,$this->extension];
	}
}