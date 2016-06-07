<?php
namespace RedCat\Framework\Artist;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use RuntimeException;
abstract class Artist extends Command{
	protected $description;
	
	protected $args = [];
	protected $requiredArgs = [];
	
	protected $opts = [];
	protected $requiredOpts = [];
	protected $shortOpts = [];
	
	protected $cwd;
	protected $input;
	protected $output;
	protected $ioHelper;
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->input = $input;
		$this->output = $output;
		if(isset($GLOBALS['ioDialogRedCat'])){
			$this->ioHelper = $GLOBALS['ioDialogRedCat'];
		}
		$this->exec();
	}
	abstract protected function exec();
	protected function configure(){
		$this->cwd = defined('REDCAT_CWD')?REDCAT_CWD:getcwd().'/';
		$c = explode('\\', get_class($this));
		$c = array_pop($c);
		$c = strtolower(preg_replace('/([^A-Z])([A-Z])/', '$1:$2', $c));
		$this->setName($c);
		if(isset($this->description))
			$this->setDescription($this->description);
		foreach($this->args as $k=>$v){
			if(is_integer($k)){
				$arg = $v;
				$description = '';
			}
			else{
				$arg = $k;
				$description = $v;
			}
			$mode = in_array($arg,$this->requiredArgs)?InputArgument::REQUIRED:InputArgument::OPTIONAL;
			$this->addArgument($arg,$mode,$description);
		}
		foreach($this->opts as $k=>$v){
			if(is_integer($k)){
				$opt = $v;
				$description = '';
			}
			else{
				$opt = $k;
				$description = $v;
			}
			$mode = in_array($opt,$this->requiredOpts)?InputOption::VALUE_REQUIRED:InputOption::VALUE_OPTIONAL;
			$short = isset($this->shortOpts[$opt])?$this->shortOpts[$opt]:null;
			$this->addOption($opt,$short,$mode,$description);
		}
	}
	protected function runCmd($cmd,$input=[],$output=null){
		if(!($input instanceof InputInterface)){
			$input = new ArrayInput((array)$input);
		}
		if(!($output instanceof OutputInterface)){
			$output = $this->output;
		}
		$run = $this->getApplication()->find($cmd);
		if(!$run){
			throw new RuntimeException($cmd.': command not found');
		}
		return $run->run($input, $output);
	}
	protected function askQuestion($sentence,$default=null){
		if($this->ioHelper){
			$helper = $this->ioHelper;
			return $helper->ask($sentence, $default);
		}
		else{
			$helper = $this->getHelper('question');
			$question = new Question($sentence, $default);
			return $helper->ask($this->input, $this->output, $question);
		}
	}
}