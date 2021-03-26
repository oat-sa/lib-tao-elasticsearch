<?php

return [
    [
        'index' => 'items',
        'body' => [
            'mappings' => [
                'properties' => [
                    'class' => [
                        'type' => 'text',
                    ],
                ]
            ]
        ]
    ],
    [
        'index' => 'tests',
        'body' => [
            'mappings' => [
                'properties' => [
                    'use' => [
                        'type' => 'keyword',
                    ],
                ]
            ]
        ]
    ]
];
