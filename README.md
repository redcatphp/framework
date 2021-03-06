**No longer actively maintained. I work now with NodeJS and I recommand you to take a look at [di-ninja](https://github.com/di-ninja/di-ninja)**

# PHP Framework - from RedCat Full Stack Framework

Components Overview
===================

Minimum Requirements
--------------------

- PHP >= 5.4
- HTTP Server like Apache, Nginx or LiteSpeed + mod rewrite

Components
----------

 Each component has a [github](https://github.com/redcatphp/) repository with it's own documentation, light autoloader, [composer](https://getcomposer.org) support and [packagist package](https://packagist.org/packages/redcatphp/). All components follows the [PSR-4 convention](http://www.php-fig.org/psr/psr-4/) for namespace related to directory structure and so, can be loaded using any modern framework [autoloader](http://redcatphp.com/autoload).

Work Flow
---------

 Here is the complete RedCat workflow. Don't panic ;), this diagram is for help you to understand deeply the whole use of components working together but many of them are transparent and you don't need to care about until you need thems. The main workspaces are in green.

 [ ![RedCat full-stack workflow diagram](http://redcatphp.com/img/redcat-workflow-diagram.png)](img/redcat-workflow-diagram.png)


 Plugin
=======

redcat/php/RedCat/Plugin
-----------------------

 The *RedCat\\Plugin* namespace is used to add somes php component which are totaly dependent and based on other *RedCat* components. All component in *RedCat* namespace are indenpents from others except Plugin.   
It is the coupling couch where all components meet others and start working together.   
Is where you'll can find implementation of FrontOffice and Backoffice for basic CMS, customization and plugins for Templix (the Template Engine of RedCat), and even some independent tools which are dependencies of *RedCat\\Plugin* sub-namespaces.

modular plugins
---------------

 For modular plugins see [Mvc module](http://redcatphp.com/mvc#module).


PHP bootstrap
==============================

 All php RedCat components are distributed under the ["RedCat" namespace](https://github.com/redcatphp/php-components). They are forming a complete php suite where all components are decoupled and the use cases and couplings can be found in subnamespace ["RedCat\\Plugin"](http://redcatphp.com/plugins) .
 An example of bootstrap is files "index.phps" and "redcat.php" in [RedCat-Framework](https://github.com/redcatphp/redcat/), where in this case, the [IoC](https://en.wikipedia.org/wiki/Inversion_of_control) is performed accross the whole application from ".config.php" via ["Strategy\\Di"](http://redcatphp.com/ding-dependency-injection).
