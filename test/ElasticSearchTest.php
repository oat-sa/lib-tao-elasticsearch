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

namespace oat\tao\test\elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use Exception;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\tao\elasticsearch\ElasticSearch;
use oat\tao\elasticsearch\IndexUpdater;
use oat\tao\elasticsearch\QueryBuilder;
use oat\tao\model\search\ResultSet;
use oat\tao\model\search\strategy\GenerisSearch;
use oat\tao\model\search\SyntaxException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use ReflectionObject;

class ElasticSearchTest extends TestCase
{
    /** @var ElasticSearch */
    private $sut;

    /** @var Client|MockObject */
    private $client;

    /** @var GenerisSearch|MockObject */
    private $generisSearch;

    /** @var QueryBuilder|MockObject */
    private $queryBuilder;

    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->generisSearch = $this->createMock(
            GenerisSearch::class
        );

        $this->queryBuilder = $this->createMock(
            QueryBuilder::class
        );

        $this->sut = new ElasticSearch(
            [
                GenerisSearch::class => $this->generisSearch,
                'indexes' => [
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
                    ],
                ]
            ]
        );

        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $serviceLocator = $this->getServiceLocatorMock(
            [
                LoggerService::SERVICE_ID => $this->logger,
                QueryBuilder::class => $this->queryBuilder
            ]
        );

        $this->sut->setServiceLocator($serviceLocator);

        $reflection = new ReflectionObject($this->sut);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue(
            $this->sut,
            $this->client
        );
    }

    public function testQuery_callElasticSearchCaseClassIsSupported(): void
    {
        $validType = 'http://www.tao.lu/Ontologies/TAOItem.rdf#Item';

        $this->generisSearch->expects($this->never())
            ->method('query');

        $this->mockDebugLogger();

        $documentUri = 'https://tao.docker.localhost/ontologies/tao.rdf#i5ef45f413088c8e7901a84708e84ec';

        $this->client->expects($this->once())
            ->method('search')
            ->willReturn(
                [
                    'hits' => [
                        'hits' => [
                            [
                                '_id' => $documentUri
                            ]
                        ],
                        'total' => [
                            'value' => 1
                        ]
                    ]
                ]
            );

        $resultSet = $this->sut->query('item', $validType);

        $this->assertEquals(new ResultSet([$documentUri], 1), $resultSet);
    }

    public function testQuery_callElasticSearchGenericError(): void
    {
        $this->expectException(SyntaxException::class);
        $validType = 'http://www.tao.lu/Ontologies/TAOItem.rdf#Item';

        $this->generisSearch->expects($this->never())
            ->method('query');

        $this->mockDebugLogger();

        $this->logger->expects($this->once())
            ->method('error')
            ->with('An unknown error occured during search', ['']);

        $documentUri = 'https://tao.docker.localhost/ontologies/tao.rdf#i5ef45f413088c8e7901a84708e84ec';

        $this->client->expects($this->once())
            ->method('search')
            ->willThrowException(
                new Exception()
            );

        $resultSet = $this->sut->query('item', $validType);
    }

    public function testQuery_callElasticSearch400Error(): void
    {
        $this->expectException(SyntaxException::class);
        $validType = 'http://www.tao.lu/Ontologies/TAOItem.rdf#Item';

        $this->generisSearch->expects($this->never())
            ->method('query');

        $this->mockDebugLogger();

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'There is an error in your search query, system returned: Error',
                [
                    '{"error":{"reason": "Error"}}'
                ]
            );

        $documentUri = 'https://tao.docker.localhost/ontologies/tao.rdf#i5ef45f413088c8e7901a84708e84ec';

        $this->client->expects($this->once())
            ->method('search')
            ->willThrowException(
                new Exception('{"error":{"reason": "Error"}}', 400)
            );

        $resultSet = $this->sut->query('item', $validType);
    }

    public function testCreateIndexes_callIndexCreationBasedOnIndexOption(): void
    {
        $indexMock = $this->createMock(IndicesNamespace::class);
        $indexMock->expects($this->at(0))
            ->method('create')
            ->with(
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
                ]
            );
        $indexMock->expects($this->at(1))
            ->method('create')
            ->with(
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
            );

        $this->client->expects($this->any())
            ->method('indices')
            ->willReturn($indexMock);

        $this->sut->setOption('indexFiles', './testIndexes.conf.php');
        $this->sut->createIndexes();
    }

    private function mockDebugLogger(): void
    {
        $query = [
            'index' => 'items',
            'size' => 10,
            'from' => 0,
            'client' =>
                [
                    'ignore' => 404,
                ],
            'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(\\"item\\")"}},"sort":{"_id":{"order":"DESC"}}}',
        ];

        $this->queryBuilder->expects($this->once())
            ->method('getSearchParams')
            ->willReturn($query);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                'Query ',
                $query
            );
    }
}
