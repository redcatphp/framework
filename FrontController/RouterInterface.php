<?php
namespace RedCat\Framework\FrontController;
interface RouterInterface{
	function load();
	function getRoutes();
	function getGroups();
	function find($uri,$server=null);
}