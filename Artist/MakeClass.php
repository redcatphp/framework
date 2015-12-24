<?php
namespace RedCat\Framework\Artist;
class MakeClass extends Artist{
	protected $description = "Make a class definition file according to PSR-4 convention";

	protected $args = [
		'class'=>'The fully qualified class name prefixed with namespace',
		'extends'=>'Extends the class from',
		'dir'=>'The source directory corresponding to the root of PSR-4',
	];
	protected $opts = [
	];
	
	protected $defaultDir = 'src';
	
	protected function exec(){
		$class = $this->input->getArgument('class');
		$extends = $this->input->getArgument('extends');
		$dir = $this->input->getArgument('dir');
		
		if(!$dir)
			$dir = $this->defaultDir;
		$class = self::ucw($class);
		if($extends)
			$extends = self::ucw($extends);
		$extending = $extends?' extends '.$extends:'';
		
		$file = $dir.'/'.str_replace('\\','/',$class).'.php';
		if(is_file($file)){
			$this->output->writeln('File '.$file.' allready exists');
			return;
		}
		
		$x = explode('\\',$class);
		$className = array_pop($x);
		$classNamespace = implode('\\',$x);
		
		$classeModel = __DIR__.'sources/classeModels/'.implode('.',explode('\\',$classNamespace)).'.phps';
		
		if(is_file($classeModel)){
			$definition = file_get_contents($classeModel);
			$definition = str_replace('{{CLASSNAME}}',$className,$definition);
		}
		else{
			$definition = '<?php
namespace '.$classNamespace.';
class '.$className.$extending.'{

}';
		}
		
		$directory = dirname($file);
		if(!is_dir($directory)&&!mkdir($directory)){
			$this->output->writeln('Unable to make dir '.$directory.', probably a permission problem');
			return;
		}
		if(!file_put_contents($file,$definition)){
			$this->output->writeln('Unable to make file '.$file.', probably a permission problem');
			return;
		}
	}
	protected static function ucw($str){
		return ucfirst(str_replace(' ', '\\', ucwords(str_replace(['-',':','.','/'], ' ', $str))));
	}
}