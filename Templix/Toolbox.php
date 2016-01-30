<?php
namespace RedCat\Framework\Templix;
class Toolbox{	
	protected $baseHref;
	protected $suffixHref;
	protected $server;
	function __construct($server=null){
		if(!$server)
			$server = &$_SERVER;
		$this->server = $server;
	}
	function autoMIN($Tml){
		if($Tml->templix&&!$Tml->templix->devCss){
			foreach($Tml('link[href][rel=stylesheet],link[href][type="text/css"]') as $l)
				if(strpos($l->href,'://')===false&&substr($l->href,-8)!='.min.css')
					$l->href = (strpos($l->href,'/')!==false?dirname($l->href).'/':'').pathinfo($l->href,PATHINFO_FILENAME).'.min.'.pathinfo($l->href,PATHINFO_EXTENSION);
		}
		if($Tml->templix&&!$Tml->templix->devJs){
			foreach($Tml('script[src]') as $s){
				if(strpos($s->src,'://')===false&&substr($s->src,-7)!='.min.js'){
					$s->src = (strpos($s->src,'/')!==false?dirname($s->src).'/':'').pathinfo($s->src,PATHINFO_FILENAME).'.min.'.pathinfo($s->src,PATHINFO_EXTENSION);
				}
			}
		}
	}
	function setCDN($Tml,$url){
		$url = rtrim($url,'/').'/';
		$Tml('script[src],img[src],link[href]')->each(function($el)use($url,$Tml){
			if($el->attr('no-cdn')||($el->nodeName=='link'&&$el->rel&&$el->rel!='stylesheet'))
				return;
			$k = $el->src?'src':'href';
			if($el->$k&&strpos($el->$k,'://')===false)
				$el->$k = $url.ltrim($el->$k,'/');
		});
		$Tml('base')->attr('data-cdn',$url);
	}
}