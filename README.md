EventStore PHP client
=====================

PHP client for [EventStore 3.x HTTP API](http://docs.geteventstore.com/http-api/latest)

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/dbellettini/php-eventstore-client?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)
[![Build Status](https://travis-ci.org/tetsuobe/php-eventstore-client.svg?branch=master)](https://travis-ci.org/tetsuobe/php-eventstore-client)

Roadmap
-------

Development started in April 2014. Not ready for production use. Things may break between versions.

API can currently:

- Read from streams
- Navigate streams
- Read events
- Write to streams
- Delete streams

New:
- Create projections
- Read projection
- Update projection
- Delete projection
- Commands:
    - enable
    - disable
    - reset


Integrations
------------
* [EventStore Client Bundle](https://github.com/tetsuobe/eventstore-client-bundle) integrates this project in Symfony 2 and Symfony 3

Contributing
------------

See [CONTRIBUTING](/CONTRIBUTING.md) file.


License
-------

EventStore PHP Client is released under the MIT License. See the bundled
[LICENSE](/LICENSE) file for details.

See also
--------
If you are looking for the TCP implementation you may be interested in [madkom/event-store-client](https://github.com/madkom/event-store-client)

Disclaimer
----------

This project is not endorsed by Event Store LLP.
