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

declare(strict_types=1);

namespace oat\tao\elasticsearch;

use ArrayIterator;
use Elasticsearch\Client;
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
    /** @var \Elasticsearch\Client */
    private $client;

    /** @return \Elasticsearch\Client */
    protected function getClient(): Client
    {
        if (is_null($this->client)) {
            $this->client = ClientBuilder::create()
                ->setHosts($this->getOption('hosts'))
                ->build();
        }

        return $this->client;
    }

    protected function getIndexer(): ElasticSearchIndexer
    {
        return new ElasticSearchIndexer($this->getClient());
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
    public function index($documents = []): int
    {
        $documents = $documents instanceof Iterator
            ? $documents
            : new ArrayIterator($documents);

        return $this->getIndexer()->buildIndex($documents);
    }

    public function remove($resourceId): bool
    {
        return $this->getIndexer()->deleteDocument($resourceId);
    }

    public function supportCustomIndex(): bool
    {
        return true;
    }

    public function createIndexes(): void
    {
        $indexes = $this->getOption('indexes');

        $this->getClient()->indices()->create($indexes['items']);
        $this->getClient()->indices()->create($indexes['tests']);
        $this->getClient()->indices()->create($indexes['groups']);
        $this->getClient()->indices()->create($indexes['deliveries']);
        $this->getClient()->indices()->create($indexes['test-takers']);
    }

    public function flush(): array
    {
        return $this->getClient()->indices()->delete([
            'index' => implode(',', IndexerInterface::AVAILABLE_INDEXES),
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
                [$fullstring, $prefix, $fieldname, $value] = $matches;
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
            "index" => implode(',', IndexerInterface::AVAILABLE_INDEXES), //TODO we need to specificy only one index during implementation of task (TAO-10248)
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
    protected function buildResultSet($elasticResult = []): ResultSet
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

    protected function updateIfUri(string $query): string
    {
        if (\common_Utils::isUri($query)) {
            $query = '"' . $query . '"';
        }
        return $query;
    }
}
