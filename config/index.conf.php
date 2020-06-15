<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

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
                'properties' => [
                    'class' => [
                        'type' => 'text',
                    ],
                    'content' => [
                        'type' => 'text',
                    ],
                    'label' => [
                        'type' => 'text'
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
                        'type' => 'text',
                    ],
                    'label' => [
                        'type' => 'text'
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
                        'type' => 'text',
                    ],
                    'label' => [
                        'type' => 'text'
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
                        'type' => 'text',
                    ],
                    'label' => [
                        'type' => 'text'
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
                        'type' => 'text',
                    ],
                    'label' => [
                        'type' => 'text'
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