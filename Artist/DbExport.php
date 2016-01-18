<?php
namespace RedCat\Framework\Artist;
use RedCat\DataMap\Bases;
use RedCat\DataMap\DataSource\SQL;
class DbExport extends Artist{
	protected $description = "Export a database to json lines format";

	protected $args = [
		'db'=>'The key of database to save from config map',
		'dir'=>'The storage directory',
	];
	protected $opts = [
	];
	
	protected $defaultDir = '.data/db';
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
		$dirDb = $dir.$db.'-'.date('Ymd-His').'/';
		
		if(!is_dir($dirDb)&&!mkdir($dirDb,0777,true)){
			$this->output->writeln('Unable to make dir '.$dirDb.', probably a rights problem');
			return;
		}
		
		$b = $this->bases[$db];
		
		foreach($b as $tableName=>$table){
			$fp = fopen($dirDb.$tableName.'.rows.jsonl','a');
			$i = 0;
			foreach($table as $row){
				$properties = [];
				foreach($row as $k=>$v){
					if(substr($k,0,1)!='_')
						$properties[$k] = $v;
				}
				fwrite($fp,json_encode($properties)."\n");
				$i++;
			}
			$this->output->writeln('table '.$tableName.' exported: '.$i.' rows');
			fclose($fp);
			
			if($b instanceof SQL){
				$schema = [];
				$fkeys = $b->getKeyMapForType($tableName);
				foreach($fkeys as $fk){
					$schema['fk'][$fk['from']] = [$b->unprefixTable($fk['table']),$fk['to'],$fk['on_delete']=='CASCADE'||$fk['on_delete']=='CASCADE'];
				}
				$schema['uniq'] = $b->getUniqueConstraints($tableName);				
				if(!empty($schema)){
					file_put_contents($dirDb.$tableName.'.schema.json',json_encode($schema));
					$this->output->writeln('Schema of table '.$tableName.' exported');
				}
			}
			
		}
		$this->output->writeln("DB $db exported to JSONL in $dirDb\n");
		
	}
}