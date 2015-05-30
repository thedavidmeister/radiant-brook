bitstamp-symfony
================

A Symfony project created on April 6, 2015, 2:38 pm.


## Release steps for major versions

1. Disable the trade scheduler on Heroku

$ php app/console trade:bitstamp

2. Check environment variables on Heroku

3. Check tests

$ rake tests

4. Screenshot stats from prod

https://radiant-brook-3028.herokuapp.com/trade/trade
https://radiant-brook-3028.herokuapp.com/trade/order_book

5. Push to master so that Codeship picks it up

6. After deploy check screenshots vs prod

7. Enable the trade scheduler on Heroku
