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
 *
 *
 */
namespace oat\tao\elasticsearch;

use Elasticsearch\ClientBuilder;
use oat\tao\model\search\dataProviders\DataProvider;
use oat\tao\model\search\dataProviders\SearchDataProvider;
use oat\tao\model\search\document\Document;
use oat\tao\model\search\document\IndexDocument;
use oat\tao\model\search\Search;
use common_Logger;
use oat\tao\model\search\SyntaxException;
use Solarium\Exception\HttpException;
use oat\tao\model\search\ResultSet;
use oat\oatbox\service\ConfigurableService;

class ElasticSearch extends ConfigurableService implements Search
{
    /**
     *
     * @var \Solarium\Client
     */
    private $client;

    /**
     *
     * @return \Elasticsearch\Client
     */
    protected function getClient() {
        if (is_null($this->client)) {
            $this->client = ClientBuilder::create()           // Instantiate a new ClientBuilder
            ->setHosts([$this->getOptions()['hosts']])      // Set the hosts
            ->build();
        }
        return $this->client;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::query()
     */
    public function query($queryString, $rootClass = null, $start = 0, $count = 10, $options = [])
    {
        try {
            $response = [];
            if ($rootClass) {
                $searchParams = $this->getSearchParams($queryString, $rootClass, 'document', $start, $count);

                $response = $this->getClient()->search($searchParams);
            }
            return $this->buildResultSet($response, $options);

        } catch ( HttpException $e ) {
            switch ($e->getCode()) {
                case 400 :
                    $json = json_decode( $e->getBody(), true );
                    throw new SyntaxException(
                        $queryString,
                        __( 'There is an error in your search query, system returned: %s', $json['error']['msg'] )
                    );
                default :
                    throw new SyntaxException( $queryString, __( 'An unknown error occured during search' ) );
            }

        }

    }

    /**
     * (Re)Generate the index for a given resource
     * @param Document $document
     * @return bool
     * @throws \common_exception_InconsistentData
     */
    public function index(Document $document)
    {
        $indexer = new ElasticSearchIndexer($this->getClient());
        $indexer->addIndex($document);
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::remove()
     */
    public function remove($resourceId)
    {
        $indexer = new ElasticSearchIndexer($this->getClient());
        $indexer->deleteIndex($resourceId);
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::fullReIndex()
     */
    public function fullReIndex(\Traversable $resourceTraversable)
    {
        $this->deleteAllIndexes();
        $this->settingUpIndexes();
        /** @var SearchDataProvider $searchDataProvider */
        $searchDataProvider = $this->getServiceLocator()->get(SearchDataProvider::SERVICE_ID);
        $indexes = $searchDataProvider->prepareAllDataForIndex($resourceTraversable);

        $indexer = new ElasticSearchIndexer($this->getClient());
        $count = $indexer->runReIndex($indexes);
        return $count;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::supportCustomIndex()
     */
    public function supportCustomIndex()
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::needIndex()
     */
    public function needIndex(\core_kernel_classes_Resource $resource)
    {
        $types = $resource->getTypes();
        $classes = current($types)->getParentClasses(true);
        $classes = array_merge($classes, $types);
        /** @var ElasticSearchIndex $compare */
        $resourcesIndexed = $this->getListOfIndexes();
        $compare = current(array_intersect_key($resourcesIndexed, $classes));
        if ($compare) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    protected function deleteAllIndexes()
    {
        $client = $this->getClient();

        $index = 'documents-*';
        $params = [
            'index' => $index,
            'client' => [ 'ignore' => 404 ]
        ];

        $response = $client->indices()->delete($params);

        return $response;
    }

    /**
     * @return array
     */
    protected function getListOfIndexes()
    {
        /** @var SearchDataProvider $searchDataProvider */
        $searchDataProvider = $this->getServiceLocator()->get(SearchDataProvider::SERVICE_ID);
        $list = $searchDataProvider->getAllIndexesMap();
        return $list;
    }


    /**
     * @return bool
     */
    protected function settingUpIndexes()
    {
        $client = $this->getClient();
        $indexesList = $this->getListOfIndexes();

        foreach ($indexesList as $index => $fields) {
            $resource = new \core_kernel_classes_Resource($index);
            $label = str_replace(' ', '_', strtolower(trim($resource->getLabel())));
            if ($label) {
                $index = 'documents-'.$label;
                $params = [
                    'index' => $index,
                    'body' => [
                        'settings' => $this->getOption('settings'),
                        'mappings' => $this->getMappings($fields)
                    ]
                ];
                $client->indices()->create($params);
            }
        }
        return true;
    }

    protected function getMappings($fields)
    {
        $properties = [];
        foreach ($fields['fields'] as $field) {
            $properties[$field] = [
                'type' => 'text',
                'analyzer' => 'autocomplete',
                'search_analyzer' => 'standard'
            ];
        }
        $mappings = ['document' => [
            'properties' => $properties
        ]];

        return $mappings;
    }

    /**
     * @param $queryString
     * @param $rootClass
     * @return array
     */
    protected function getSearchParams( $queryString, $rootClass = null, $type = 'document', $start = 0, $count = 10)
    {
        if ($rootClass instanceof \core_kernel_classes_Class) {
            $rootClassLabel = $rootClass->getLabel();
        } else {
            $rootClass = new \core_kernel_classes_Class($rootClass);
            $rootClassLabel = $rootClass->getLabel();
        }
        $queryString = strtolower($queryString);
        $options = $this->getOptionsByClass($rootClass);
        $label = isset($options[DataProvider::LABEL_CLASS_OPTION]) ? $options[DataProvider::LABEL_CLASS_OPTION] : ( $rootClassLabel ? $rootClassLabel : '');
        $index = 'documents-'.str_replace(' ', '_', strtolower(trim($label)));
        $query = [
            'query' => [
                'multi_match' => [
                    'query' => $queryString,
                    'fields' => $options[DataProvider::FIELDS_OPTION],
                    'type' => 'best_fields',
                    'operator' => 'and'
                ]
            ]
        ];
        $params = [
            "index" => $index,
            "type" => $type,
            "size" => $count,
            "from" => $start,
            "client" => [ "ignore" => 404 ],
            "body" => json_encode($query)
        ];

        return $params;
    }

    /**
     * @param \core_kernel_classes_Class $rootClass
     * @param string                     $type
     * @return array
     */
    protected function getOptionsByClass(\core_kernel_classes_Class $rootClass, $type = 'resource')
    {
        /** @var SearchDataProvider $searchDataProvider */
        $searchDataProvider = $this->getServiceLocator()->get(SearchDataProvider::SERVICE_ID);
        $options = $searchDataProvider->getOptionsByClass($rootClass->getUri());
        return $options;
    }

    /**
     * @param array $elasticResult
     * @param array $options
     * @return ResultSet
     */
    protected function buildResultSet($elasticResult = [], $options = [])
    {
        $uris = array();
        $total = 0;
        if ($elasticResult && isset($elasticResult['hits'])) {
            foreach ($elasticResult['hits']['hits'] as $document) {
                $source = $document['_source'];
                if (isset($source['provider'])) {
                    /** @var DataProvider $dataProvider */
                    $dataProvider = $this->getServiceLocator()->get($source['provider']);
                    if ($dataProvider) {
                        if (isset($options[self::OPTION_RESPONSE_KEY]) && isset($source[$options[self::OPTION_RESPONSE_KEY]])) {
                            $uris[] = $dataProvider->getResults($source[$options[self::OPTION_RESPONSE_KEY]]);
                        } else {
                            $uris[] = $dataProvider->getResults($document['_id']);
                        }
                    }
                }
            }
            $total = $elasticResult['hits']['total'];
        }
        $uris = array_unique($uris);
        return new ResultSet($uris, $total);
    }

}
