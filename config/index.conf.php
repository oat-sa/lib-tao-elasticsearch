<?php

$dynamicTemplates = [
    [
        "HTMLArea" => [
            "match" => "HTMLArea_*",
            "match_mapping_type" => "string",
            "mapping" => [
                "type" => "text"
            ]
        ]
    ],
    [
        "TextArea" => [
            "match" => "TextArea_*",
            "match_mapping_type" => "string",
            "mapping" => [
                "type" => "text"
            ]
        ]
    ],
    [
        "TextBox" => [
            "match" => "TextBox_*",
            "match_mapping_type" => "string",
            "mapping" => [
                "type" => "text"
            ]
        ]
    ],
    [
        "CheckBox" => [
            "match" => "CheckBox_*",
            "match_mapping_type" => "string",
            "mapping" => [
                "type" => "keyword"
            ]
        ]
    ],
    [
        "ComboBox" => [
            "match" => "ComboBox_*",
            "match_mapping_type" => "string",
            "mapping" => [
                "type" => "keyword"
            ]
        ]
    ],
    [
        "RadioBox" => [
            "match" => "RadioBox_*",
            "match_mapping_type" => "string",
            "mapping" => [
                "type" => "keyword"
            ]
        ]
    ]
];

return [
    'items' => [
        'index' => 'items',
        'body' => [
            'mappings' => [
                '_source' => [
                    'enabled' => false
                ],
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
                        'store' => true,
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
                'dynamic_templates' => $dynamicTemplates,
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
                '_source' => [
                    'enabled' => false
                ],
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                        'store' => true,
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
                'dynamic_templates' => $dynamicTemplates,
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
                '_source' => [
                    'enabled' => false
                ],
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                        'store' => true,
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
                'dynamic_templates' => $dynamicTemplates,
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
                '_source' => [
                    'enabled' => false
                ],
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                        'store' => true,
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
                'dynamic_templates' => $dynamicTemplates,
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
                '_source' => [
                    'enabled' => false
                ],
                'properties' => [
                    'class' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ],
                    'label' => [
                        'type' => 'text',
                        'store' => true,
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
                'dynamic_templates' => $dynamicTemplates,
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