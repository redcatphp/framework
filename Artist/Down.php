<?php
namespace RedCat\Framework\Artist;
class Down extends Artist{
	protected $description = "Turn on maintenance mode";
	protected $args = [
		'ip' => 'Allow an IP to access site in maintenance, separe them by space to add multiple',
	];
	protected $opts = [];
	private $htaccess307Head = '
RewriteEngine on
RewriteCond $0#%{REQUEST_URI} ([^#]*)#(.*)\1$
RewriteRule ^.*$ - [E=CWD:%2]';
	private $htaccess307Allow = '
RewriteCond %{REMOTE_ADDR} !^';
	private $htaccess307Foot = '
RewriteCond %{REQUEST_URI} !^/maintenance.php$
RewriteRule ^(.*)$ %{ENV:CWD}maintenance.php [R=307,L]
RewriteRule ^maintenance$ %{ENV:CWD}maintenance.php [L]';
	protected function exec(){
		$ip = $this->input->getArgument('ip');
		if(is_array($ip))
			$ip = implode(' ',$ip);
		$ip = explode(' ',$ip);
		if(is_file($this->cwd.'htaccess-up')){
			$this->output->writeln('Application is allready in maintenance');
			return;
		}
		copy(__DIR__.'/sources/maintenance.php',$this->cwd.'maintenance.php');
		copy($this->cwd.'.htaccess',$this->cwd.'htaccess-up');
		$htaccess = file_get_contents($this->cwd.'.htaccess');
		$htaccess .= $this->htaccess307Head;
		foreach($ip as $v){
			$v = trim($v);
			if($v){
				$htaccess .= $this->htaccess307Allow.$v;
			}
		}
		$htaccess .= $this->htaccess307Foot;
		file_put_contents($this->cwd.'.htaccess',$htaccess);
		$this->output->writeln('Application is in maintenance from now');
	}
}