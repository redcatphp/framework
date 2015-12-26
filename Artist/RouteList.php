<?php
namespace RedCat\Framework\Artist;
use Symfony\Component\Console\Helper\Table;
use RedCat\Framework\FrontController\RouterInterface;
use RedCat\Route\MatchInterface;
class RouteList extends Artist{
	protected $description = 'Show a list of routes defined in the main front controller';
	protected $router;
	function __construct($name = null,RouterInterface $router=null){
		parent::__construct($name);
		$this->router = $router;
	}
	function exec(){
		if(!$this->router){
			$this->output->writeln('No Router defined as a substitution of RouterInterface in Dependency Injection config');
			return;
		}
		$this->router->load();
		$routes = $this->router->getRoutes();
		
		$rows = [];
		
		foreach($routes as $zIndex=>$routeCollection){
			foreach($routeCollection as list($resolver,$callback,$group)){
				
				if(is_object($resolver)){
					$resolverString = get_class($resolver);
					if($resolver instanceof MatchInterface){
						$resolverString = explode('\\',$resolverString);
						$resolverString = array_pop($resolverString);
						$parameter = $resolver->getMatch();
					}
					else{
						$parameter = json_encode($resolver);
					}
				}
				elseif(is_array($resolver)){
					$resolverString = 'Callable Array';
					$parameter = json_encode($resolver);
				}
				else{
					$resolverString = $resolver;
					$parameter = '';
				}
				
				if(is_object($callback))
					$callback = get_class($callback);
				elseif(is_array($callback))
					$callback = json_encode($callback);
				
				
				$rows[] = [$resolverString,$parameter,$callback,$zIndex,$group];
			}
		}
		
		
		
		$table = new Table($this->output);
		$table
			->setHeaders(['Resolver', 'Parameter', 'Callback', 'zIndex','Group'])
			->setRows($rows)
		;
		$table->render();
	}
}