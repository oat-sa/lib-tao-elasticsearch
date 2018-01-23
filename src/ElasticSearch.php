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
use oat\tao\model\search\index\IndexService;
use oat\tao\model\search\Search;
use oat\tao\model\search\SyntaxException;
use Solarium\Exception\HttpException;
use oat\tao\model\search\ResultSet;
use oat\oatbox\service\ConfigurableService;

/**
 * Class ElasticSearch
 * @package oat\tao\elasticsearch
 */
class ElasticSearch extends ConfigurableService implements Search
{
    /**
     *
     * @var \Elasticsearch\Client
     */
    private $client;
    /**
     *
     * @return \Elasticsearch\Client
     */
    protected function getClient() {
        if (is_null($this->client)) {
            $this->client = ClientBuilder::create()           // Instantiate a new ClientBuilder
            ->setHosts($this->getOptions()['hosts'])      // Set the hosts
            ->build();
        }
        return $this->client;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::query()
     */
    public function query($queryString, $rootClass = null, $start = 0, $count = 10)
    {
        try {
            $response = [];
            if ($rootClass) {
                $searchParams = $this->getSearchParams($queryString, $rootClass, 'document', $start, $count);
                $response = $this->getClient()->search($searchParams);
            }
            return $this->buildResultSet($response);

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
    public function index(\oat\tao\model\search\index\IndexDocument $document)
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
        /** @var IndexService $indexService */
        $indexService = $this->getServiceLocator()->get(IndexService::SERVICE_ID);
        $indexer = new ElasticSearchIndexer($this->getClient());
        $count = 0;
        while ($resourceTraversable->valid()) {
            /** @var \core_kernel_classes_Resource $resource */
            $resource = $resourceTraversable->current();
            $rootClass = $indexService->getRootClassByResource($resource);
            if ($rootClass) {
                $body = [
                    'label' => $resource->getLabel()
                ];
                $document = new \oat\tao\model\search\index\IndexDocument(
                    $resource->getUri(),
                    $resource->getUri(),
                    $rootClass,
                    $body
                );
                $indexer->addIndex($document);
            }

            $resourceTraversable->next();
            $count += 1;
        }
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
        /** @var IndexService $indexService */
        $indexService = $this->getServiceLocator()->get(IndexService::SERVICE_ID);
        $list = $indexService->getOption('rootClasses');
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
            $index = strtolower('documents-'.\tao_helpers_Uri::encode($index));
            $params = [
                'index' => $index,
                'body' => [
                    'settings' => $this->getOption('settings'),
                    'mappings' => $this->getMappings($fields)
                ]
            ];
            $client->indices()->create($params);

        }
        return true;
    }

    /**
     * @param $fields
     * @return array
     */
    protected function getMappings($fields)
    {
        $properties = [];
        $fields['fields'][] = 'label';
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
            $rootClass = $rootClass->getUri();
        }
        $queryString = strtolower($queryString);
        $index = strtolower('documents-'.\tao_helpers_Uri::encode($rootClass));
        $query = [
            'query' => [
                'multi_match' => [
                    'query' => $queryString,
                    'fields' => '*',
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
     * @param array $elasticResult
     * @return ResultSet
     */
    protected function buildResultSet($elasticResult = [])
    {
        $uris = array();
        $total = 0;
        if ($elasticResult && isset($elasticResult['hits'])) {
            foreach ($elasticResult['hits']['hits'] as $document) {
                $source = $document['_source'];
                $uris[] = $source['response_id'];
            }
            $total = $elasticResult['hits']['total'];
        }
        $uris = array_unique($uris);
        return new ResultSet($uris, $total);
    }

}
