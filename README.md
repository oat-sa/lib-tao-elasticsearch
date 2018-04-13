# lib-tao-elasticsearch

Elastic Search engine

###Install:
```
sudo php vendor/oat-sa/lib-tao-elasticsearch/bin/activateElasticSearch.php <pathToTaoRoot> <host> <port> <login> <password>
```
 - `pathToTaoRoot` it's root path of your tao
 - `host` it's host of your elasticsearch environment. `localhost` by defaut.
 - `port` it's port of your elasticsearch environment. `9200` by default.
 - `login` it's login for you elasticsearch environment. Optional property.
 - `password` it's password for you elasticsearch environment. Optional property.

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

