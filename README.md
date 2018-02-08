# lib-tao-elasticsearch

Elastic Search engine

###Install:
```
sudo php php vendor/oat-sa/lib-tao-elasticsearch/bin/activateElasticSearch.php <pathToTaoRoot> <host> <port> <login> <password>
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

