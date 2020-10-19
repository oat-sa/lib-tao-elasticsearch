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
              "host": "http://localhost",
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
          "indexes": {
            "items": {
              "index": "items",
              "body": {
                "mappings": {
                  "properties": {
                    "class": {
                      "type": "text"
                    },
                    "content": {
                      "type": "text"
                    },
                    "label": {
                      "type": "text"
                    },
                    "model": {
                      "type": "keyword",
                      "ignore_above": 256
                    },
                    "type": {
                      "type": "keyword",
                      "ignore_above": 256
                    },
                    "read_access": {
                      "type": "keyword",
                      "ignore_above": 256
                    }
                  },
                  "dynamic_templates": [
                    {
                      "HTMLArea": {
                        "match": "HTMLArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextArea": {
                        "match": "TextArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextBox": {
                        "match": "TextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "SearchTextBox": {
                        "match": "SearchTextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "CheckBox": {
                        "match": "CheckBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "ComboBox": {
                        "match": "ComboBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "RadioBox": {
                        "match": "RadioBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    }
                  ]
                },
                "settings": {
                  "index": {
                    "number_of_shards": "1",
                    "number_of_replicas": "1"
                  }
                }
              }
            },
            "tests": {
              "index": "tests",
              "body": {
                "mappings": {
                  "properties": {
                    "class": {
                      "type": "text"
                    },
                    "label": {
                      "type": "text"
                    },
                    "type": {
                      "type": "keyword",
                      "ignore_above": 256
                    },
                    "read_access": {
                      "type": "keyword",
                      "ignore_above": 256
                    }
                  },
                  "dynamic_templates": [
                    {
                      "HTMLArea": {
                        "match": "HTMLArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextArea": {
                        "match": "TextArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextBox": {
                        "match": "TextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "SearchTextBox": {
                        "match": "SearchTextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "CheckBox": {
                        "match": "CheckBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "ComboBox": {
                        "match": "ComboBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "RadioBox": {
                        "match": "RadioBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    }
                  ]
                },
                "settings": {
                  "index": {
                    "number_of_shards": "1",
                    "number_of_replicas": "1"
                  }
                }
              }
            },
            "groups": {
              "index": "groups",
              "body": {
                "mappings": {
                  "properties": {
                    "class": {
                      "type": "text"
                    },
                    "label": {
                      "type": "text"
                    },
                    "type": {
                      "type": "keyword",
                      "ignore_above": 256
                    }
                  },
                  "dynamic_templates": [
                    {
                      "HTMLArea": {
                        "match": "HTMLArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextArea": {
                        "match": "TextArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextBox": {
                        "match": "TextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "SearchTextBox": {
                        "match": "SearchTextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "CheckBox": {
                        "match": "CheckBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "ComboBox": {
                        "match": "ComboBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "RadioBox": {
                        "match": "RadioBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    }
                  ]
                },
                "settings": {
                  "index": {
                    "number_of_shards": "1",
                    "number_of_replicas": "1"
                  }
                }
              }
            },
            "deliveries": {
              "index": "deliveries",
              "body": {
                "mappings": {
                  "properties": {
                    "class": {
                      "type": "text"
                    },
                    "label": {
                      "type": "text"
                    },
                    "type": {
                      "type": "keyword",
                      "ignore_above": 256
                    }
                  }
                },
                "settings": {
                  "index": {
                    "number_of_shards": "1",
                    "number_of_replicas": "1"
                  }
                }
              }
            },
            "delivery-results": {
              "index": "delivery-results",
              "body": {
                "mappings": {
                  "properties": {
                    "label": {
                      "type": "text"
                    },
                    "delivery": {
                      "type": "text"
                    },
                    "type": {
                      "type": "keyword",
                      "ignore_above": 256
                    },
                    "test_taker": {
                      "type": "text"
                    },
                    "test_taker_name": {
                      "type": "text"
                    },
                    "delivery_execution": {
                      "type": "text"
                    },
                    "custom_tag": {
                      "type": "text"
                    },
                    "context_label": {
                      "type": "text"
                    },
                    "context_id": {
                      "type": "keyword",
                      "ignore_above": 256
                    },
                    "resource_link_id": {
                      "type": "keyword",
                      "ignore_above": 256
                    }
                  }
                },
                "settings": {
                  "index": {
                    "number_of_shards": "1",
                    "number_of_replicas": "1"
                  }
                }
              }
            },
            "test-takers": {
              "index": "test-takers",
              "body": {
                "mappings": {
                  "properties": {
                    "class": {
                      "type": "text"
                    },
                    "label": {
                      "type": "text"
                    },
                    "login": {
                      "type": "keyword",
                      "ignore_above": 256
                    },
                    "type": {
                      "type": "keyword",
                      "ignore_above": 256
                    },
                    "read_access": {
                      "type": "keyword",
                      "ignore_above": 256
                    }
                  },
                  "dynamic_templates": [
                    {
                      "HTMLArea": {
                        "match": "HTMLArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextArea": {
                        "match": "TextArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextBox": {
                        "match": "TextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "SearchTextBox": {
                        "match": "SearchTextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "CheckBox": {
                        "match": "CheckBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "ComboBox": {
                        "match": "ComboBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "RadioBox": {
                        "match": "RadioBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    }
                  ]
                },
                "settings": {
                  "index": {
                    "number_of_shards": "1",
                    "number_of_replicas": "1"
                  }
                }
              }
            },
            "assets": {
              "index": "assets",
              "body": {
                "mappings": {
                  "properties": {
                    "class": {
                      "type": "text"
                    },
                    "label": {
                      "type": "text"
                    },
                    "type": {
                      "type": "keyword",
                      "ignore_above": 256
                    },
                    "read_access": {
                      "type": "keyword",
                      "ignore_above": 256
                    }
                  },
                  "dynamic_templates": [
                    {
                      "HTMLArea": {
                        "match": "HTMLArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextArea": {
                        "match": "TextArea_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "TextBox": {
                        "match": "TextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "SearchTextBox": {
                        "match": "SearchTextBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "text"
                        }
                      }
                    },
                    {
                      "CheckBox": {
                        "match": "CheckBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "ComboBox": {
                        "match": "ComboBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    },
                    {
                      "RadioBox": {
                        "match": "RadioBox_*",
                        "match_mapping_type": "string",
                        "mapping": {
                          "type": "keyword"
                        }
                      }
                    }
                  ]
                },
                "settings": {
                  "index": {
                    "number_of_shards": "1",
                    "number_of_replicas": "1"
                  }
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