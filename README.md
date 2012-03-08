##HiMVC
Hierarchical injected MVC for PHP.

#Setup?
HiMVC relies and extends classes available in ezp-next, so here is how you can setup the code to work:

* Checkout HiMVC: $ git clone git://github.com/andrerom/HiMVC.git himvc
* Checkout ezp-next: $ git clone git://github.com/ezsystems/ezp-next.git next
* Symlink eZ: $ cd himvc && ln -s ../next/eZ eZ
* Symlink config: $ ln config.php-DEVELOPMENT config.php
* Test: $ php index.php
* Test: $ phpunit

#What?
HiMVC is yet another MVC prototype in PHP, it is an concept for an MVC stack that forces use of dependency injection in all parts of the stack using a Dependency injection container. It aims to be fast first, but not at the cost of extensibility.

The code has some parts that can be used as is, and some parts that should be given more thought before it can be considered final and interface can be created for these parts.

#Todo?
As the Hierarchical part is not yet done, HiMVC atm has an alternative meaning of "Highly injected MVC". But some missing pices to get this part in:

* Twig view handler with design aware Twig_Loader
* PHP view handler
* Define ways to execute view hierarchically in both view handlers
* Define a way to do page layouts, basic idea is that standard view is changed to "pagelayout" which hierarchically calls full/edit/index/.. view, this is reverse of eZ Publish, but might be more understandable for new users of the system.
* Add interfaces and unit tests for code that is considered mature (ready)

Things that should be reconsidered:

* The properties on Request object including action vs method
* How the RequestParser works
* (...)

#History?
This project has been my kitchen sink for testing different parts of what is needed for eZ Publish 5 since back in 2009 when the project was called Project V.
The code has evolved heavily since then in several iterations, large parts of that w/o version control and the part that did have history had lots of irrelevant info so it was squashed. But in gernal since 2009 it has provided ideas for large performance improvements in eZINI and eZSession in eZ Publish, and certain concepts in ezp-next (current code name for eZ Publish 5).
