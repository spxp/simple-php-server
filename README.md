## Simple SPXP server written in PHP

Easy to install and perfect for playing around and getting your hands dirty with SPXP.

### Prerequisites

The prerequisites are low and covered by most PHP hosting providers:

* PHP with 64bit integers and Sodium module installed
* Apache
* MySQL

### Installation

* Copy the content of this repository into any directory of your web server
* Start your web server and visit its web site
* Follow the setup instructions
  * If installed in a sub-directory the setup process will ask you to upload an additional file to your webserver

Have fun! :rocket:

### Features

This SPXP server implements the [Service Provider](https://github.com/spxp/spxp-specs/blob/master/SPXP-SPE-Spec.md) and the [Profile Management Extension](https://github.com/spxp/spxp-specs/blob/master/SPXP-PME-Spec.md). It supports:

* Registering and thus hosting SPXP profiles
* Publishing and deleting profile posts
* Connecting a hosted profile to another profile
* Migrating a profile to or from the server

### Caveats

* This implementation is as simple and straight forward as possible and not optimized for performance
* More importantly, it does not (yet) offer any kind of moderation or administration options. To deactivate or remove profiles, you need to manually delete these from the database.
