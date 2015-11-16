# friendship-api (in development)
[![Build Status](https://travis-ci.org/nazar256/friendship-api.svg)](https://travis-ci.org/nazar256/friendship-api)

It's a demo application of implementing REST API with Symfony2 and Doctrine MongoDb. 
With an application you can register users, log in, add each other to friends and view friends of friends. 
The ApiDoc resides at "/api/doc" route.

## It uses:
* FosRestBundle
* Doctrine MongoDB ODM
* JMSSerializerBundle
* NelmioApiDocBundle
* DoctrineFixturesBundle
* LiipFunctionalTestBundle

## It does not include
* Doctrine ORM

## Some logic
* anyone can add another to friends (subscribe), this adds desired users to 
"friends" array and performs friend request;
* to accept friendship request you just need to add that guy to your friends

## Some features
* the API authorization is not oAuth or something "right" for REST, it works by sending auth params by POST 
instead to be compatible with JS frameworks, including Backbone;
* for the same reason - easy using with Backbone API does not return Location 
header on item creation, it returns full created object instead
* API does not actually implement HATEOAS as it's not required yet

## TODO
* create vagrant script
* use nelmio/alice for fixtures generation
* write a bundle to test the ApiDoc availability for every route (or find one)
* implement Symfony 2.7 code sniffer standard to all project files!