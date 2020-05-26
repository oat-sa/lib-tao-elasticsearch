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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 */

namespace oat\tao\elasticsearch;

use ArrayIterator;
use Elasticsearch\ClientBuilder;
use Iterator;
use oat\tao\model\search\index\IndexIterator;
use oat\tao\model\search\Search;
use oat\tao\model\search\SyntaxException;
use oat\tao\model\search\ResultSet;
use oat\oatbox\service\ConfigurableService;

/**
 * Class ElasticSearch
 * @package oat\tao\elasticsearch
 * @todo Rename to ElasticSearchService according to our best practises
 */
class ElasticSearch extends ConfigurableService implements Search
{
    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @return \Elasticsearch\Client
     */
    protected function getClient()
    {
        if (is_null($this->client)) {
            $this->client = ClientBuilder::create()
                ->setHosts($this->getOptions()['hosts'])
                ->build();
        }

        return $this->client;
    }

    /**
     * @return ElasticSearchIndexer
     */
    protected function getIndexer()
    {
        return new ElasticSearchIndexer($this->getClient(), 'documents', 'document');
    }

    /**
     * @param $queryString
     * @param $type
     * @param int $start
     * @param int $count
     * @param string $order
     * @param string $dir
     * @return ResultSet
     * @throws SyntaxException
     */
    public function query($queryString, $type, $start = 0, $count = 10, $order = '_id', $dir = 'DESC')
    {
        if ($order == 'id') {
            $order = '_id';
        }

        try {
            return $this->buildResultSet(
                $this->getClient()->search(
                    $this->getSearchParams($queryString, $type, $start, $count, $order, $dir)
                )
            );
        } catch (\Exception $e) {
            switch ($e->getCode()) {
                case 400:
                    $json = json_decode($e->getMessage(), true);
                    throw new SyntaxException(
                        $queryString,
                        __('There is an error in your search query, system returned: %s', $json['error']['reason'])
                    );
                default:
                    throw new SyntaxException($queryString, __('An unknown error occured during search'));
            }
        }
    }

    /**
     * (Re)Generate the index for a given resource
     * @param IndexIterator|array $documents
     * @return integer
     */
    public function index($documents = [])
    {
        $documents = $documents instanceof Iterator
            ? $documents
            : new ArrayIterator($documents);

        return $this->getIndexer()->buildIndex($documents);
    }

    /**
     * @param $resourceId
     * @return bool
     */
    public function remove($resourceId)
    {
        return $this->getIndexer()->deleteIndex($resourceId);
    }

    /**
     * @return bool
     */
    public function supportCustomIndex()
    {
        return true;
    }

    /**
     * @return void
     */
    public function createItemsIndex(): void
    {
        $indexSettings = [
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
        ];

        $this->getClient()->indices()->create($indexSettings);
    }

    /**
     * @return void
     */
    public function createTestsIndex(): void
    {
        $indexSettings = [
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
        ];

        $this->getClient()->indices()->create($indexSettings);
    }

    /**
     * @return void
     */
    public function createGroupsIndex(): void
    {
        $indexSettings = [
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
        ];

        $this->getClient()->indices()->create($indexSettings);
    }

    /**
     * @return void
     */
    public function createDeliveriesIndex(): void
    {
        $indexSettings = [
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
        ];

        $this->getClient()->indices()->create($indexSettings);
    }

    /**
     * @return void
     */
    public function createResultsIndex(): void
    {
        $indexSettings = [
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
        ];

        $this->getClient()->indices()->create($indexSettings);
    }

    /**
     * @return void
     */
    public function createtestTakersIndex(): void
    {
        $indexSettings = [
            'index' => 'items',
            'body' => [],
        ];

        $this->getClient()->indices()->create($indexSettings);
    }

    /**
     * @return array
     */
    protected function getMappings()
    {
        return [$this->getIndexer()->getType() => [
            'dynamic_templates' => [[
                'analysed_string_template' => [
                    'path_match' => '*',
                    'mapping' => [
                        'type' => 'text',
                        'analyzer' => 'autocomplete',
                        'search_analyzer' => 'standard'
                    ]
                ]
            ]]
        ]];
    }

    /**
     * @return array
     */
    public function flush()
    {
        return $this->getClient()->indices()->delete([
            'index' => $this->getIndexer()->getIndex(),
            'client' => [
                'ignore' => 404
            ]
        ]);
    }

    /**
     * @param string $queryString
     * @param string $type
     * @param int $start
     * @param int $count
     * @param $order
     * @param $dir
     * @return array
     */
    protected function getSearchParams($queryString, $type, $start, $count, $order, $dir)
    {
        $parts = explode(' ', htmlspecialchars_decode($queryString));

        foreach ($parts as $key => $part) {
            $matches = [];
            $part = $this->updateIfUri($part);
            if (preg_match('/^([^a-z_]*)([a-z_]+):(.*)/', $part, $matches) === 1) {
                list($fullstring, $prefix, $fieldname, $value) = $matches;
                $value = $this->updateIfUri($value);
                if ($fieldname) {
                    $parts[$key] = $prefix . $fieldname . ':' . str_replace(':', '\\:', $value);
                }
            }
        }
        $queryString = implode(' ', $parts);
        $queryString = (strlen($queryString) == 0 ? '' : '(' . $queryString . ') AND ')
            . 'type:' . str_replace(':', '\\:', '"' . $type . '"');

        $query = [
            'query' => [
                'query_string' =>
                    [
                        "default_operator" => "AND",
                        "query" => $queryString
                    ]
            ],
            'sort' => [$order => ['order' => $dir]]
        ];

        $params = [
            "index" => $this->getIndexer()->getIndex(),
            "type" => $this->getIndexer()->getType(),
            "size" => $count,
            "from" => $start,
            "client" => ["ignore" => 404],
            "body" => json_encode($query)
        ];

        return $params;
    }

    /**
     * @param array $elasticResult
     * @return ResultSet
     */
    protected function buildResultSet($elasticResult = [])
    {
        $uris = [];
        $total = 0;
        if ($elasticResult && isset($elasticResult['hits'])) {
            foreach ($elasticResult['hits']['hits'] as $document) {
                $uris[] = $document['_id'];
            }
            // Starts from Elasticsearch 7.0 the `total` attribute is object with two parameters [value,relation]
            $total = is_array($elasticResult['hits']['total'])
                ? $elasticResult['hits']['total']['value']
                : $elasticResult['hits']['total'];
        }
        $uris = array_unique($uris);

        return new ResultSet($uris, $total);
    }

    /**
     * @param $query
     * @return string
     */
    protected function updateIfUri($query)
    {
        if (\common_Utils::isUri($query)) {
            $query = '"' . $query . '"';
        }
        return $query;
    }
}
