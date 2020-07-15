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
use oat\tao\model\search\strategy\GenerisSearch;
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

    /** @var QueryBuilder */
    private $queryBuilder;

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


    /** @return QueryBuilder */
    protected function getQueryBuilder(): QueryBuilder
    {
        if (is_null($this->queryBuilder)) {
            $this->queryBuilder = $this->getServiceLocator()->get(QueryBuilder::class);
        }

        return $this->queryBuilder;
    }

    protected function getIndexer(): ElasticSearchIndexer
    {
        return new ElasticSearchIndexer($this->getClient(), $this->getLogger());
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
    public function query($queryString, $type, $start = 0, $count = 10, $order = '_id', $dir = 'DESC'): ResultSet
    {
        if (!isset(IndexerInterface::AVAILABLE_INDEXES[$type])) {
            return $this->getGenerisSearch()->query($queryString, $type, $start, $count, $order, $dir);
        }

        if ($order == 'id') {
            $order = '_id';
        }

        try {
            $query = $this->getQueryBuilder()->getSearchParams($queryString, $type, $start, $count, $order, $dir);
            $this->getLogger()->debug('Query ', $query);

            return $this->buildResultSet(
                $this->getClient()->search(
                    $query
                )
            );
        } catch (\Exception $exception) {
            switch ($exception->getCode()) {
                case 400:
                    $json = json_decode($exception->getMessage(), true);
                    $message = __(
                        'There is an error in your search query, system returned: %s',
                        $json['error']['reason']
                    );
                    $this->getLogger()->error($message, [$exception->getMessage()]);
                    throw new SyntaxException($queryString, $message);
                default:
                    $message = 'An unknown error occured during search';
                    $this->getLogger()->error($message, [$exception->getMessage()]);
                    throw new SyntaxException($queryString, __($message));
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
        return $this->getClient()->indices()->delete(
            [
                'index' => implode(',', IndexerInterface::AVAILABLE_INDEXES),
                'client' => [
                    'ignore' => 404
                ]
            ]
        );
    }

    private function getGenerisSearch(): GenerisSearch
    {
        /** @var GenerisSearch $generisSearch */
        $generisSearch = $this->getOption(GenerisSearch::class);
        $generisSearch->setServiceLocator($this->getServiceLocator());

        return $generisSearch;
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
}
