<?php

return [
    'items' => [
        'index' => 'items',
        'body' => [
            'mappings' => [
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'content' => [
                        'type' => 'text',
                    ],
                    'label' => [
                        'type' => 'text',
                    ],
                    'model' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'type' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'data_privileges' => [
                        'properties' => [
                            'privilege' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'user_id' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                ],
                'dynamic_templates' => [
                    [
                        'propertyShortText' => [
                            'match_mapping_type' => 'string',
                            'match' => 'propertyShortText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyLongText' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyLongText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyHTML' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyHTML_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyChoice' => [
                            'match_mapping_type' => 'string',
                            'match' => 'propertyChoice_*',
                            'mapping' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                ],
            ],
            'settings' => [
                'index' => [
                    'number_of_shards' => '1',
                    'number_of_replicas' => '1',
                ],
            ],
        ],
    ],
    'tests' => [
        'index' => 'tests',
        'body' => [
            'mappings' => [
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                    ],
                    'type' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'data_privileges' => [
                        'properties' => [
                            'privilege' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'user_id' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                ],
                'dynamic_templates' => [
                    [
                        'propertyShortText' => [
                            'match_mapping_type' => 'string',
                            'match' => 'propertyShortText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyLongText' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyLongText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyHTML' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyHTML_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyChoice' =>
                            [
                                'match_mapping_type' => 'string',
                                'match' => 'propertyChoice_*',
                                'mapping' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 256,
                                ],
                            ],
                    ],
                ],
            ],
            'settings' => [
                'index' => [
                    'number_of_shards' => '1',
                    'number_of_replicas' => '1',
                ],
            ],
        ],
    ],
    'groups' => [
        'index' => 'groups',
        'body' => [
            'mappings' => [
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                    ],
                    'type' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'data_privileges' => [
                        'properties' => [
                            'privilege' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'user_id' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                ],
                'dynamic_templates' => [
                    [
                        'propertyShortText' => [
                            'match_mapping_type' => 'string',
                            'match' => 'propertyShortText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyLongText' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyLongText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyHTML' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyHTML_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyChoice' =>
                            [
                                'match_mapping_type' => 'string',
                                'match' => 'propertyChoice_*',
                                'mapping' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 256,
                                ],
                            ],
                    ],
                ],
            ],
            'settings' => [
                'index' => [
                    'number_of_shards' => '1',
                    'number_of_replicas' => '1',
                ],
            ],
        ],
    ],
    'deliveries' => [
        'index' => 'deliveries',
        'body' => [
            'mappings' => [
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                    ],
                    'type' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'data_privileges' => [
                        'properties' => [
                            'privilege' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'user_id' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                ],
                'dynamic_templates' => [
                    [
                        'propertyShortText' => [
                            'match_mapping_type' => 'string',
                            'match' => 'propertyShortText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyLongText' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyLongText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyHTML' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyHTML_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyChoice' =>
                            [
                                'match_mapping_type' => 'string',
                                'match' => 'propertyChoice_*',
                                'mapping' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 256,
                                ],
                            ],
                    ],
                ],
            ],
            'settings' => [
                'index' => [
                    'number_of_shards' => '1',
                    'number_of_replicas' => '1',
                ],
            ],
        ],
    ],
    'results' => [
        'index' => 'results',
        'body' => [
            'mappings' => [
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                    ],
                    'type' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'data_privileges' => [
                        'properties' => [
                            'privilege' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'user_id' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                ],
                'dynamic_templates' => [
                    [
                        'propertyShortText' => [
                            'match_mapping_type' => 'string',
                            'match' => 'propertyShortText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyLongText' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyLongText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyHTML' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyHTML_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyChoice' =>
                            [
                                'match_mapping_type' => 'string',
                                'match' => 'propertyChoice_*',
                                'mapping' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 256,
                                ],
                            ],
                    ],
                ],
            ],
            'settings' => [
                'index' => [
                    'number_of_shards' => '1',
                    'number_of_replicas' => '1',
                ],
            ],
        ],
    ],
    'test-takers' => [
        'index' => 'test-takers',
        'body' => [
            'mappings' => [
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                    ],
                    'login' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'type' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'data_privileges' => [
                        'properties' => [
                            'privilege' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'user_id' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                ],
                'dynamic_templates' => [
                    [
                        'propertyShortText' => [
                            'match_mapping_type' => 'string',
                            'match' => 'propertyShortText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyLongText' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyLongText_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyHTML' => [
                            'match_mapping_type' => 'long',
                            'match' => 'propertyHTML_*',
                            'mapping' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    [
                        'propertyChoice' => [
                            'match_mapping_type' => 'string',
                            'match' => 'propertyChoice_*',
                            'mapping' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                        ],
                    ],
                ],
            ],
            'settings' => [
                'index' => [
                    'number_of_shards' => '1',
                    'number_of_replicas' => '1',
                ],
            ],
        ],
    ],
];