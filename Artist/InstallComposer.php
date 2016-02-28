<?php
namespace RedCat\Framework\Artist;
class InstallComposer extends Artist{
	protected $description = "Install composer locally in root path of application";
	protected $args = [];
	protected $opts = [];
	protected function exec(){
		$file = $this->cwd.'composer.phar';
		$setup = $this->cwd.'composer-setup.php';
		$this->output->writeln('Downloading composer installer');
		$install = file_get_contents('https://getcomposer.org/installer');
		if(!$install){
			$this->output->writeln('An error occured, unable to download composer installer');
			return;
		}
		file_put_contents($setup,$install);
		if(!is_file($setup)){
			$this->output->writeln('An error occured, unable to write composer installer, it\'s probably a rights problem');
			return;
		}
		register_shutdown_function(function()use($setup,$file){
			unlink($setup);
			if(is_file($file)){
				$this->output->writeln('Local composer installed, you can use it from the root path of your application');
			}
			else{
				$this->output->writeln('An error occured, unable to install a local composer');
			}
		});
		includeFile($setup,['argv'=>[]]);
	}
}
function includeFile(){
	if(func_num_args()>1)
		extract(func_get_arg(1));
	return include func_get_arg(0);
}