# PHP Framework - from RedCat Full Stack Framework

Components Overview
===================

Minimum Requirements
--------------------

- PHP >= 5.4
- HTTP Server like Apache, Nginx or LiteSpeed + mod rewrite

Components
----------

 Each component has a [github](https://github.com/surikat?tab=repositories) repository with it's own documentation, light autoloader, [composer](https://getcomposer.org) support and [packagist package](https://packagist.org/users/surikat/packages/). All components follows the [PSR-4 convention](http://www.php-fig.org/psr/psr-4/) for namespace related to directory structure and so, can be loaded using any modern framework [autoloader](http://redcatphp.com/autoload).

Work Flow
---------

 Here is the complete surikat workflow. Don't panic ;), this diagram is for help you to understand deeply the whole use of components working together but many of them are transparent and you don't need to care about until you need thems. The main workspaces are in green.

 [ ![Surikat full-stack workflow diagram](http://redcatphp.com/img/surikat-workflow-diagram.png)](img/surikat-workflow-diagram.png)


 Plugin
=======

surikat/php/RedCat/Plugin
-----------------------

 The *RedCat\\Plugin* namespace is used to add somes php component which are totaly dependent and based on other *RedCat* components. All component in *RedCat* namespace are indenpents from others except Plugin.   
It is the coupling couch where all components meet others and start working together.   
Is where you'll can find implementation of FrontOffice and Backoffice for basic CMS, customization and plugins for Templix (the Template Engine of surikat), and even some independent tools which are dependencies of *RedCat\\Plugin* sub-namespaces.

modular plugins
---------------

 For modular plugins see [Mvc module](http://redcatphp.com/mvc#module).


PHP bootstrap
==============================

 All php surikat components are distributed under the ["RedCat" namespace](https://github.com/surikat/RedCat). They are forming a complete php suite where all components are decoupled and the use cases and couplings can be found in subnamespace ["RedCat\\Plugin"](http://redcatphp.com/plugins) .
 An example of bootstrap is files "index.phps" and "surikat.php" in [Surikat-Framework](https://github.com/surikat/surikat/), where in this case, the [IoC](https://en.wikipedia.org/wiki/Inversion_of_control) is performed accross the whole application from ".config.php" via ["Wire\\Di"](http://redcatphp.com/wire-dependency-injection).
