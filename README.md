php-helpers
===========

Yet another package full of helper functions.

This is pretty much a personal dumping ground for little functions and classes I make which don’t fit anywhere else. Every now and again I extract a whole bunch of related functions into a package of their own.

A lot of the open source code I’ve written depends on this package, which is not a very good thing as it’s actually quite big, especially if you install all the dev dependencies (which composer does by default). So I’m currently (2013-06) in the process of extracting the bits I’m using into minimal packages as described above.

## Contents

* `BarnabyWalters\Doctrine\Types\Json`: A custom type for Doctrine allowing arrays to be stored as JSON in `longtext` fields.
* `BarnabyWalters\Helpers\Helpers`: A class full of static helper methods for doing various bits of text processing and other stuff.
* `BarnabyWalters\Helpers\Microformats`: Static helper methods for processing canonical microformats-2 array structures
* `BarnabyWalters\Posse\*`: A namespace full of helper classes for syndicating content. Includes:
	* THE TRUNCENATOR
	* Twitter syndication methods
	* intelligent ActivityStreams Object => Twitter POST array
	* HTML => Microblogging syntax converter

## Documentation

Auto-generated documentation is available online, but split up a bit.

* [Static Helpers and Microformats Helpers](http://waterpigs.co.uk/docs/namespaces/BarnabyWalters.Helpers.html)
* [Doctrine JSON Type](http://waterpigs.co.uk/docs/classes/BarnabyWalters.Doctrine.Types.Json.html)
* [POSSE including THE TRUNCENATOR and Syndicators](http://waterpigs.co.uk/docs/namespaces/BarnabyWalters.Posse.html)