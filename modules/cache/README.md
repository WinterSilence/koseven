Koseven Cache library
====================

The cache library for Koseven provides a simple interface to the most common cache solutions. Developers are free to add their own caching solutions that follow the cache design pattern defined within this module.

Supported cache solutions
-------------------------

Currently this module supports the following cache methods.

1. APCu
2. File
3. Memcached
4. Redis
5. SQLite
6. Wincache

Introduction to caching
-----------------------

To use caching to the maximum potential, your application should be designed with caching in mind from the outset. In general, the most effective caches contain lots of small collections of data that are the result of expensive computational operations, such as searching through a large data set.

There are many different caching methods available for PHP, from the very basic file based caching to opcode caching in eAccelerator and APC. Caching engines that use physical memory over disk based storage are always faster, however many do not support more advanced features such as tagging.

Using Cache
-----------

To use Koseven Cache enable the module within the application bootstrap within the section entitled _modules_.

Quick example
-------------

The following is a quick example of how to use Koseven Cache. The example is using the SQLite driver.

	<?php
	// Get a Sqlite Cache instance  
	$mycache = Cache::instance('sqlite');
	
	// Create some data
	$data = array('foo' => 'bar', 'apples' => 'pear', 'BDFL' => 'Shadowhand');
	
	// Save the data to cache, with an id of test_id and a lifetime of 10 minutes
	$mycache->set('test_id', $data, 600);
	
	// Retrieve the data from cache
	$retrieved_data = $mycache->get('test_id');
	
	// Remove the cache item
	$mycache->delete('test_id');
	
	// Clear the cache of all stored items
	$mycache->delete_all();
