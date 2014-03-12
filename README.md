sql2mongo
=========

Query MongoDB documents easily with PHP object SQL oriented style

## Why ?

Because you know very well Sql language and you are a little bit lost with MongoDB.
You can't find any good/simple wrapper and you find that the basic PHP wrapper is hard to use.
I found myself in that situation and decided to build an SQL style PHP class to send commands to Mongo
using the familiar Sql syntax.

## Example

Nothing more simple, create a new instance of MongoClient for connection, pass it to the QueryBuilder,
then stack the methods to build the query.

``` php

$mongoClient = new MongoClient("mongodb://127.0.0.1");
$db = $mongoClient->selectDB('test');
$builder = new QueryBuilder($db);
$builder->select()->from('cars');
$builder->sum('count');
$builder->group('brand');
$cursor = $this->object->query();


```

## Unit testing

Yo dawg i heard you like unit tests ?! You can run :

``` bash
phpunit -c phpunit.xml.dist tests/Sql2Mongo/Tests/QueryBuilderTest.php

```
