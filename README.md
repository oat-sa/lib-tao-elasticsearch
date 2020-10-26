# lib-tao-elasticsearch

Elastic Search engine

###Install:

##### Cli script based approach
```
sudo php vendor/oat-sa/lib-tao-elasticsearch/bin/activateElasticSearch.php <pathToTaoRoot> <host> <port> <login> <password>
```
 - `pathToTaoRoot` it's root path of your tao
 - `host` it's host of your elasticsearch environment. `localhost` by default.
 - `port` it's port of your elasticsearch environment. `9200` by default.
 - `login` it's login for you elasticsearch environment. Optional property.
 - `password` it's password for you elasticsearch environment. Optional property.
 
##### Seed file based approach

Following section to be included into seed file and describes an engine, connectivity string, service configuration and fallback  
```
        "tao": {
          "search": {
            "type": "configurableService",
            "class": "oat\\tao\\elasticsearch\\ElasticSearch",
            "options": {
              "hosts": [
                {
                  "host": "elasticsearch",
                  "port": 9200
                }
              ],
              "settings": {
                "analysis": {
                  "filter": {
                    "autocomplete_filter": {
                      "type": "edge_ngram",
                      "min_gram": 1,
                      "max_gram": 100
                    }
                  },
                  "analyzer": {
                    "autocomplete": {
                      "type": "custom",
                      "tokenizer": "standard",
                      "filter": [
                        "lowercase",
                        "autocomplete_filter"
                      ]
                    }
                  }
                }
              },
              "oat\\tao\\model\\search\\strategy\\GenerisSearch": {
                "type": "configurableService",
                "class": "oat\\tao\\model\\search\\strategy\\GenerisSearch"
              }
            }
          }
        }
```

For the proper index structure creation on installation stage following may be used, 
where `indexFiles` contains the absolute path to the declaration, sample provided within this lib (@TODO should be decoupled) 

```
"postInstall": [
    {
      "class": "\\oat\\tao\\elasticsearch\\Action\\IndexCreator",
      "params": [
        "--indexFiles",
        "/var/www/html/lib-tao-elasticsearch/config/index.conf.php"
      ]
    }
  ]
``` 
 

####Setting Up:
```
Add your elasticsearch host to the config/tao/search.conf.php like 
    'hosts' => array(
        'http://localhost:9200'
    ),
   ``` 
   ``` 
Add you castom settings, filters or analysis

    'settings' => array(
           'analysis' => array(
               'filter' => array(
                   'autocomplete_filter' => array(
                       'type' => 'edge_ngram',
                       'min_gram' => 1,
                       'max_gram' => 100
                   )
               ),
               'analyzer' => array(
                   'autocomplete' => array(
                       'type' => 'custom',
                       'tokenizer' => 'standard',
                       'filter' => array(
                           'lowercase',
                           'autocomplete_filter'
                       )
                   )
               )
           )
       ),
    'isMap' => true
```

After this step, you need to fill the index with documents. to do this, you must run:

```bash
$ bash tao/scripts/tools/index/IndexPopulator.sh <TAO_PLATFORM_ROOT>
```

This script will index all resources on the TAO platform for elastic search.