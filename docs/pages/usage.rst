Usage
=====

Apereo already provides a demo CAS server without no Proxy authentication mechanism enabled.

In order to test the libraries here, I've setup another `CAS server with Proxy authentication enabled`_ this time.

Feel free to use it for your tests.

.. warning:: If your client application is not hosted on a public server and in HTTPS, this won't work.

.. tip:: See more on the page :ref:`development`. if you want to have your own local CAS server.

The test login is `casuser`, password is: `Mellon`

Bare PHP
--------

To get you started with PSR CAS in a simple bare PHP project (*without
using any framework*), you can check the following project: `drupol/psrcas-client-poc`_

Test `the bare PHP demo application`_ now.

Symfony
-------

The PSR CAS library can be used in a Symfony 4 project through the package `drupol/cas-bundle`_

Test `the Symfony 4 bundle demo application`_ now.

See `the documentation of the PSR CAS Bundle`_ for more information.

.. _drupol/cas-bundle: https://github.com/drupol/cas-bundle
.. _the documentation of the PSR CAS Bundle: http://github.com/drupol/cas-bundle
.. _the Symfony 4 bundle demo application: https://cas-bundle-demo.herokuapp.com/
.. _CAS server with Proxy authentication enabled: https://heroku-cas-server.herokuapp.com/cas/login
.. _drupol/psrcas-client-poc: https://github.com/drupol/psrcas-client-poc/
.. _the bare PHP demo application: https://psrcas-php-demo.herokuapp.com/

