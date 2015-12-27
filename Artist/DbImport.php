<?php
namespace RedCat\Framework\Artist;
use RedCat\DataMap\Bases;
use Symfony\Component\Console\Question\ChoiceQuestion;
class DbImport extends Artist{
	protected $description = "Import from json lines format to a database";

	protected $args = [
		'db'=>'The key of database to save from config map',
		'dir'=>'The storage directory',
	];
	protected $opts = [
	];
	
	protected $defaultDir = '.data/db/';
	protected $bases;
	function __construct($name = null,Bases $bases=null){
		parent::__construct($name);
		$this->bases = $bases;
	}
	
	protected function exec(){
		$db = $this->input->getArgument('db');
		if(is_null($db))
			$db = 0;
		
		$dir = $this->input->getArgument('dir');
		if(!$dir)
			$dir = $this->defaultDir;
		$dir = rtrim($dir,'/').'/';
		
		$b = $this->bases[$db];
		
		$baks = [];
		foreach(glob($dir.$db.'-*',GLOB_ONLYDIR) as $bak){
			$baks[] = substr(basename($bak),strlen($db)+1);
		}
		$baks = array_reverse($baks);
		
		$helper = $this->getHelper('question');
		$question = new ChoiceQuestion(
			'Select the db directory that you want to import',
			$baks,
			0
		);
		$question->setErrorMessage('directory %s is invalid.');
		$directory = $helper->ask($this->input, $this->output, $question);
		
		$dirDb = $dir.$db.'-'.$directory.'/';
		
		foreach(glob($dirDb.'*.rows.jsonl') as $rowsFile){
			$type = substr(basename($rowsFile),0,-11);
			$fp = fopen($rowsFile,'r');
			$table = $b[$type];
			while(false!==$line=fgets($fp)){
				$row = json_decode($line,true);
				$row['_forcePK'] = true;
				$table[] = $row;
			}
			$this->output->writeln('Rows of table '.$type.' imported');
			fclose($fp);
		}
		foreach(glob($dirDb.'*.schema.json') as $schemaFile){
			$type = substr(basename($schemaFile),0,-12);
			if(!$b->tableExists($type))
				$b->createTable($type);
			$schema = json_decode(file_get_contents($schemaFile),true);
			if(isset($schema['fk'])){
				foreach($schema['fk'] as $property=>list($targetType,$targetProperty,$isDep)){
					if(!$b->columnExists($targetType,$targetProperty)){
						$b->addColumn($targetType,$targetProperty,$b->getTypeForID());
					}
					if(!$b->columnExists($type,$property)){
						$b->addColumn($type,$property,$b->getTypeForID());
					}
					$b->addFK($type,$targetType,$property,$targetProperty,$isDep);
				}
			}
			if(isset($schema['uniq'])){
				foreach($schema['uniq'] as $uniq){
					foreach($uniq as $col){
						if(!$b->columnExists($type,$col)){
							$b->addColumn($type,$col,$b->getTypeForID());
						}
					}
					$b->addUniqueConstraint($type,$uniq);
				}
			}
			$this->output->writeln('Schema of table '.$type.' imported');
		}
		$this->output->writeln('JSONL directory '.$dirDb.' imported into  DB '.$db);
	}
}