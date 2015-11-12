# friendship-api (in development)
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

## TODO
* Drop test database on every test run
* create vagrant script
* use nelmio/alice for fixtures generation
* add request friendship ability
* add accept friend request ability
* add ability to see friends
* add ability to see friends of friend on N-th level
* write a test to check the ApiDoc availability for every route