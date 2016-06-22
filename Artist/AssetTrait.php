<?php
namespace RedCat\Framework\Artist;
trait AssetTrait{
	protected $bowerAssetDir = 'vendor/bower-asset';
	protected $npmAssetDir = 'vendor/npm-asset';
	function loadAssetInstallerPaths(){
		if(is_file('composer.json')){
			$json = json_decode(file_get_contents('composer.json'),true);
			if(is_array($json)){
				if(isset($json['config']['vendor-dir'])){
					$this->bowerAssetDir = $json['config']['vendor-dir'].'/bower-asset';
					$this->npmAssetDir = $json['config']['vendor-dir'].'/npm-asset';
				}
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