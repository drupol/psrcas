Configuration
=============

.. code:: yaml

   base_url: https://casserver.herokuapp.com/cas
   protocol:
     login:
       path: /login
       allowed_parameters:
         - service
         - renew
         - gateway
     serviceValidate:
       path: /p3/serviceValidate
       allowed_parameters:
         - service
         - ticket
         - pgtUrl
         - renew
         - format
       default_parameters:
         pgtUrl: https://my-app/casProxyCallback
     logout:
       path: /logout
       allowed_parameters:
         - service
       default_parameters:
         service: https://my-app/homepage
     proxy:
       path: /proxy
       allowed_parameters:
         - targetService
         - pgt
     proxyValidate:
       path: /proxyValidate
       allowed_parameters:
         - service
         - ticket
         - pgtUrl
       default_parameters:
         pgtUrl: https://my-app/casProxyCallback