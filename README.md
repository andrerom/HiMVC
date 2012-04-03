##HiMVC
Hierarchical injected MVC for PHP.

* Status: Prototype
* Provided: As is
* License: AGPL v3
* Copyright: eZ Systems AS & andrerom

[![Build Status](https://secure.travis-ci.org/andrerom/HiMVC.png)](http://travis-ci.org/andrerom/HiMVC)



#Setup?
HiMVC relies and extends classes in ezp-next, so here is how you can setup the code to work:

* $ git clone git://github.com/andrerom/HiMVC.git himvc
* $ cd himvc
* $ git submodule init
* $ git submodule update
* $ cp config.php-DEVELOPMENT config.php


For testing Hello World:

* $ php index.php


For testing twig generated ouput using ezp-next Public API Content object:

* $ php index.php http://exmpl.cm/content/1


For Running Unit Tests:

* $ phpunit


If you have a webserver (http) setup to the folder you just create then you can also point yout browser towards it.

#What?
HiMVC is yet another MVC prototype in PHP, it is an concept for an MVC stack that forces use of dependency injection in all parts of the stack using a Dependency injection container. It aims to be fast first, but not at the cost of extensibility.

The code has some parts that can be used as is, and some parts that should be given more thought before it can be considered final and interface can be created for these parts.

#Todo?
As the Hierarchical part is not yet done, HiMVC atm has an alternative meaning of "Highly injected MVC". But some missing pices to get this part in:

* PHP view handler
* Add Response objects
* Define where Authentication should be done (pre controller action filter?) and how to pass on user id from session / do basic auth
* Define how cache hints should be part of the system, including vary by logic
* ------- " -------  View Cache should be done ( pre + post cotroller action filter?)
* Add interfaces and unit tests for code that is considered mature (ready)
* @todo's

Things that should be reconsidered:

* The properties on Request object including action vs method
* How the RequestParser works including json/xml addapters
* If request param (GET parmeters) should be validated by controller before view cache is checked
* (...)

#History?
This project has been a kitchen sink for testing different parts of what is needed for eZ Publish 5 since back in 2009 when the project was called Project V.
The code has evolved heavily since then in several iterations, large parts of that w/o version control and the part that did have history had lots of irrelevant info so it was squashed. But in gernal since 2009 it has provided ideas for large performance improvements in eZINI and eZSession in eZ Publish, and certain concepts in ezp-next (current code name for eZ Publish 5).
