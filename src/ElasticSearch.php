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
    const INDEX_MAP_PROPERTIES = 'elastic_search_index_map';

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
    public function query($queryString = '', $type, $start = 0, $count = 10)
    {
        try {
            $searchParams = $this->getSearchParams($queryString, $type, $start, $count);
            $response = $this->getClient()->search($searchParams);
            return $this->buildResultSet($response);

        } catch (\Exception $e ) {
            switch ($e->getCode()) {
                case 400 :
                    $json = json_decode( $e->getMessage(), true );
                    throw new SyntaxException(
                        $queryString,
                        __( 'There is an error in your search query, system returned: %s', $json['error']['reason'] )
                    );
                default :
                    throw new SyntaxException( $queryString, __( 'An unknown error occured during search' ) );
            }

        }

    }

    /**
     * (Re)Generate the index for a given resource
     * @param IndexIterator|array $documents
     * @return integer
     * @throws \common_Exception
     * @throws \common_exception_InconsistentData
     */
    public function index($documents = [])
    {
        $indexer = new ElasticSearchIndexer($this->getClient(), $documents);
        $counts = $indexer->index();
        return $counts;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::remove()
     */
    public function remove($resourceId)
    {
        $indexer = new ElasticSearchIndexer($this->getClient(), null);
        $indexer->deleteIndex($resourceId);
        return true;
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
    public function settingUpIndexes()
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

    /**]
     * @return array
     */
    protected function getMappings()
    {
        $mappings = ['document' => [
            'dynamic_templates' => [
                [
                    'analysed_string_template' => [
                        'path_match' => '*',
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
    public function flush()
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
     * @param string $queryString
     * @param string $type
     * @param number $start
     * @param number $count
     * @return array
     */
    protected function getSearchParams( $queryString, $type, $start = 0, $count = 10)
    {
        $parts = explode( ' ', htmlspecialchars_decode($queryString) );

        foreach ($parts as $key => $part) {

            $matches = array();
            if (preg_match( '/^([^a-z_]*)([a-z_]+):(.*)/', $part, $matches ) === 1) {
                list( $fullstring, $prefix, $fieldname, $value ) = $matches;
                if ($fieldname) {
                    $parts[$key] = $prefix . $fieldname . ':' . str_replace( ':', '\\:', $value );
                }
            }

        }
        $queryString = implode( ' ', $parts );
        $queryString = (strlen($queryString) == 0 ? '' : '(' . $queryString . ') AND ')
            .'type:' . str_replace( ':', '\\:', '"'.$type.'"' );
        $query = [
            'query' => [
                'query_string' =>
                    [
                        "default_operator" => "AND",
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
        $total = 0;
        if ($elasticResult && isset($elasticResult['hits'])) {
            foreach ($elasticResult['hits']['hits'] as $document) {
                $uris[] = $document['_id'];
            }
            $total = $elasticResult['hits']['total'];
        }
        $uris = array_unique($uris);

        return new ResultSet($uris, $total);
    }

}
