<?php
namespace RedCat\Framework\Artist;
trait AssetTrait{
	protected $bowerAssetDir = 'vendors/bower-asset';
	protected $npmAssetDir = 'vendors/npm-asset';
	function loadAssetInstallerPaths(){
		if(is_file($this->cwd.'composer.json')){
			$json = json_decode(file_get_contents($this->cwd.'composer.json'),true);
			if(is_array($json)){
				if(isset($json['extra']['asset-installer-paths']['bower-asset-library'])){
					$this->bowerAssetDir = $json['extra']['asset-installer-paths']['bower-asset-library'];
				}
				if(isset($json['extra']['asset-installer-paths']['npm-asset-library'])){
					$this->npmAssetDir = $json['extra']['asset-installer-paths']['npm-asset-library'];
				}
			}
		}
	}
}