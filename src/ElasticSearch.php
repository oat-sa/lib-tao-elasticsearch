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
use oat\tao\model\search\index\IndexDocument;
use oat\tao\model\search\index\IndexIterator;
use oat\tao\model\search\index\IndexService;
use oat\tao\model\search\Search;
use oat\tao\model\search\SyntaxException;
use Solarium\Exception\HttpException;
use oat\tao\model\search\ResultSet;
use oat\oatbox\service\ConfigurableService;

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
                $searchParams = $this->getSearchParams($queryString, $rootClass, $start, $count);
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
     * @param IndexDocument $document
     * @return bool
     * @throws \common_exception_InconsistentData
     */
    public function index(IndexDocument $document)
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
    public function fullReIndex(\Traversable $indexIterator)
    {
        $this->deleteAllIndexes();
        $this->settingUpIndexes();
        $indexer = new ElasticSearchIndexer($this->getClient(), $indexIterator);
        $count = $indexer->reIndex();

        return $count;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::addIndexes()
     */
    public function addIndexes(\Traversable $indexIterator) {
        $indexer = new ElasticSearchIndexer($this->getClient(), $indexIterator);
        $count = $indexer->reIndex();

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
     * @return bool
     */
    protected function settingUpIndexes()
    {
        $client = $this->getClient();


        $params = [
            'index' => 'documents',
            'body' => [
                'settings' => $this->getOption('settings'),
                'mappings' => $this->getMappings()
            ]
        ];
        $client->indices()->create($params);
        return true;
    }

    protected function getMappings()
    {
        $mappings = ['document' => [
            'dynamic_templates' => [
                [
                    'analysed_string_template' => [
                        'path_match' => '*_t_d',
                        'mapping' => [
                            'type' => 'text',
                            'analyzer' => 'autocomplete',
                            'search_analyzer' => 'standard'
                        ]
                    ]
                ]
            ]
        ]];

        return $mappings;
    }

    /**
     * @return array
     */
    protected function deleteAllIndexes()
    {
        $client = $this->getClient();

        $index = 'documents';
        $params = [
            'index' => $index,
            'client' => [ 'ignore' => 404 ]
        ];

        $response = $client->indices()->delete($params);

        return $response;
    }

    /**
     * @param $queryString
     * @param $rootClass
     * @return array
     */
    protected function getSearchParams( $queryString, $rootClass = null, $start = 0, $count = 10)
    {
        $parts = explode( ' ', $queryString );
        /** @var IndexService $indexService */
        $indexService = $this->getServiceLocator()->get(IndexService::SERVICE_ID);
        $indexMap = $indexService->getOption(IndexService::SUBSTITUTION_CONFIG_KEY);

        foreach ($parts as $key => $part) {

            $matches = array();
            if (preg_match( '/^([^a-z_]*)([a-z_]+):(.*)/', $part, $matches ) === 1) {
                list( $fullstring, $prefix, $fieldname, $value ) = $matches;
                if (isset($indexMap[$fieldname])) {
                    $parts[$key] = $prefix . $indexMap[$fieldname] . ':' . $value;
                }
            }
        }
        $queryString = implode( ' ', $parts );
        if ( ! is_null( $rootClass )) {
            $queryString = (strlen($queryString) == 0 ? '' : '(' . $queryString . ') AND ')
                .'type_r:' . str_replace( ':', '\\:', '"'.$rootClass->getUri().'"' );
        }
        $query = [
            'query' => [
                'query_string' =>
                    [
                        "default_operator" => "AND",
                        "fuzzy_transpositions" => false,
                        "query" => $queryString
                    ]
            ]
        ];

        $params = [
            "index" => "documents",
            "type" => "document",
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
        $uris = [];
        $results = [];
        $total = 0;
        if ($elasticResult && isset($elasticResult['hits'])) {
            foreach ($elasticResult['hits']['hits'] as $document) {
                $source = $document['_source'];
                $uris[] = $document['_id'];
                $results[] = new IndexDocument(
                    $document['_id'],
                    $source
                );
            }
            $total = $elasticResult['hits']['total'];
        }
        $uris = array_unique($uris);
        $result = [
            'ids' => $uris,
            'results' => $results
        ];
        return new ResultSet($result, $total);
    }

}
