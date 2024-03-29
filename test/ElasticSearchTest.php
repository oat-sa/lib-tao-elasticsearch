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
use oat\tao\elasticsearch\Query;
use oat\tao\elasticsearch\QueryBuilder;
use oat\tao\elasticsearch\SearchResult;
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

    public function testSearch(): void
    {
        $query = [
            'index' => 'indexName',
            'body' => json_encode(
                [
                    'query' => [
                        'query_string' => [
                            'default_operator' => 'AND',
                            'query' => 'a:"b"'
                        ]
                    ],
                    'size' => 777,
                    'from' => 7,
                    'sort' => [],
                ]
            )
        ];

        $this->client
            ->method('search')
            ->with($query)
            ->willReturn([]);

        $query = (new Query('indexName'))
            ->setOffset(7)
            ->setLimit(777)
            ->addCondition('a:"b"');

        $this->assertEquals(new SearchResult([], 0), $this->sut->search($query));
    }

    public function testCountDocuments(): void
    {
        $this->client
            ->method('count')
            ->with(
                [
                    'index' => 'indexName',
                ]
            )
            ->willReturn(
                [
                    'count' => 777,
                ]
            );

        $this->assertEquals(777, $this->sut->countDocuments('indexName'));
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
                                '_id' => $documentUri,
                                '_source' => [
                                    'attr1' => 'attr1 Value',
                                    'attr2' => 'attr2 Value',
                                    'attr3' => 'attr3 Value',
                                ],
                            ]
                        ],
                        'total' => [
                            'value' => 1
                        ]
                    ]
                ]
            );

        $resultSet = $this->sut->query('item', $validType);

        $this->assertInstanceOf(ResultSet::class, $resultSet);
        $this->assertCount(1, $resultSet->getArrayCopy());
        $this->assertCount(4, $resultSet->getArrayCopy()[0]);
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
            ->with('Elasticsearch: An unknown error occurred during search "internal error"');

        $documentUri = 'https://tao.docker.localhost/ontologies/tao.rdf#i5ef45f413088c8e7901a84708e84ec';

        $this->client->expects($this->once())
            ->method('search')
            ->willThrowException(new Exception('internal error'));

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
            ->with('Elasticsearch: There is an error in your search query, system returned: Error {"error":{"reason": "Error"}}');

        $documentUri = 'https://tao.docker.localhost/ontologies/tao.rdf#i5ef45f413088c8e7901a84708e84ec';

        $this->client->expects($this->once())
            ->method('search')
            ->willThrowException(new Exception('{"error":{"reason": "Error"}}', 400));

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

        $this->sut->setOption('indexFiles', __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'testIndexes.conf.php');

        $this->sut->createIndexes();
    }

    /**
     * @dataProvider pingProvider
     */
    public function testPing(bool $clientPing, bool $expected): void
    {
        $this->client
            ->expects($this->once())
            ->method('ping')
            ->willReturn($clientPing);

        $this->assertEquals($expected, $this->sut->ping());
    }

    public function pingProvider(): array
    {
        return [
            'True' => [
                'clientPing' => true,
                'expected' => true,
            ],
            'False' => [
                'clientPing' => false,
                'expected' => false,
            ],
        ];
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
            ->with('Elasticsearch Query ' . json_encode($query));
    }
}
