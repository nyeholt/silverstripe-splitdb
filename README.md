# SilverStripe Aspects module

This module contains an implementation of Read/Write splitting for database 
queries to allow for master/slave database implementations

## Basic Usage

For using aspects, which is effectively database agnostic, read the 
'ReadWriteSplitterAspect' section. For using a custom database class against
MySQL, read the 'ReadWriteMySQLDatabase' section. 

### ReadWriteSplitterAspect

Use this aspect to direct READ queries to a particular database, and WRITE
queries (ie queries that modify) to a specific master server (that is 
replicating to those slaves). It is assumed replication is managed externally
to SilverStripe. 

Add configuration in your project along the lines of

```

Injector:
  WriteMySQLDatabase:
    class: MySQLDatabase
    constructor:
      - type: MySQLDatabase
        server: write.master.database.hostname
        username: user
        password: pass
        database: project_database
  ProxiedMySQLDatabase:
    class: MySQLDatabase
    constructor:
      - type: MySQLDatabase
        server: readonly.slavecluster.hostname
        username: user
        password: pass
        database: project_database
  MySQLWriteDbQueryAspect:
    class: \SilverStripe\Aspects\Database\ReadWriteSplitterAspect
    properties:
      writeDb: %$WriteMySQLDatabase
  MySQLDatabase:
    class: AopProxyService
    properties:
      proxied: %$ProxiedMySQLDatabase
      beforeCall:
        query: 
          - %$MySQLWriteDbQueryAspect
        manipulate:
          - %$MySQLWriteDbQueryAspect
        getGeneratedID:
          - %$MySQLWriteDbQueryAspect
        affectedRows:
          - %$MySQLWriteDbQueryAspect

```

### ReadWriteMySQLDatabase

Similar to the previous ReadWrite aspect, the ReadWriteMySQLDatabase relies on 
setting up a separate write database connection for directing queries to. So
the initial database configuration is the same as usual, however instead of
using _MySQLDatabase_, use _ReadWriteMySQLDatabase_ 
(or _ReadWriteSQLiteDatabase_). This configuration must point to the _readonly_
database. 

Then, via YAML config, create configuration for the _write_ specific database
(note that the configured Injector object MUST be called 
_SplitterWriteDatabase_)

```

Injector:
  SplitterWriteDatabase:
    class: MySQLDatabase
    constructor:
      - type: MySQLDatabase
        server: write.master.database.hostname
        username: user
        password: pass
        database: project_database

```


## Maintainer Contacts

* Marcus Nyeholt <marcus@silverstripe.com.au>

## Requirements

* SilverStripe 3.1.?

## License

This module is licensed under the BSD license at http://silverstripe.org/BSD-license
