SQL Schema Manager
==================

Feature
-------

* MySQL only (more next time).
* Schema file format json and yaml.
* Dump current database schema to json, yaml and sql format.

Schema
------

Json format (schema.json)
~~~json
{
    "config": {
        "driver": "pdo_mysql",
        "host": "127.0.0.1",
        "port": "3306",
        "dbname": "demo",
        "user": "demo",
        "password": "demo",
        "charset": "utf8"
    },
    "schemas": []
}
~~~

Yaml format (schema.yml)
~~~yaml
config:
    driver: pdo_mysql
    host: 127.0.0.1
    port: '3306'
    dbname: demo
    user: demo
    password: demo
    charset: utf8
schemas: ~
~~~

Usage
-----


Create database schema.
~~~shell
php schema.phar create
~~~

Update database schema.
~~~shell
php schema.phar update --force
~~~

Dump database schema.
~~~shell
php schema.phar dump --format=sql
~~~
