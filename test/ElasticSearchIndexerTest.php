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
 *
 */

declare(strict_types=1);

namespace oat\tao\test\elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\ClientErrorResponseException;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\tao\elasticsearch\ElasticSearchIndexer;
use oat\tao\elasticsearch\IndexerInterface;
use oat\tao\model\search\index\IndexDocument;
use oat\tao\model\TaoOntology;
use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * ElasticSearchIndexerTest
 *
 * @package oat\tao\test\elasticsearch
 */
class ElasticSearchIndexerTest extends TestCase
{

    /** @var Client|\PHPUnit\Framework\MockObject\MockObject  */
    private $client;

    /** @var LoggerService|\PHPUnit\Framework\MockObject\MockObject  */
    private $logger;

    /** @var ElasticSearchIndexer $sut */
    private $sut;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerService::class);

        $this->sut = new ElasticSearchIndexer($this->client, $this->logger);
    }

    public function testGetIndexNameByDocument(): void
    {
        $document = $this->createMock(IndexDocument::class);
        $document->expects($this->once())
            ->method('getBody')
            ->willReturn([
                'type' => [
                    TaoOntology::CLASS_URI_ITEM
                ]
            ]);

        $indexName = $this->sut->getIndexNameByDocument($document);

        $this->assertSame(IndexerInterface::ITEMS_INDEX, $indexName);
    }

    public function testGetIndexNameByDocumentForUnclassifieds(): void
    {
        $document = $this->createMock(IndexDocument::class);
        $document->expects($this->once())
            ->method('getBody')
            ->willReturn([
                'type' => [
                    'Some_Unclassified'
                ]
            ]);

        $indexName = $this->sut->getIndexNameByDocument($document);

        $this->assertSame(IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX, $indexName);
    }

    public function testBuildIndexBulkErrorResponseAfter100(): void
    {
        $this->expectException(ClientErrorResponseException::class);
        $this->expectExceptionMessage('some reason; some other reason');

        $document = $this->createMock(IndexDocument::class);
        $document->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'type' => [
                    TaoOntology::CLASS_URI_ITEM,
                ],
            ]);

        $document->expects($this->any())
            ->method('getId')
            ->willReturn('some_id');

        $this->client
            ->expects($this->atMost(100))
            ->method('bulk')
            ->willReturn([
                'errors' => true,
                'items' => [
                    [
                        [
                            'error' => [
                                'reason' => 'some reason',
                            ],
                        ],
                    ],
                    [
                        [
                            'error' => [
                                'reason' => 'some other reason',
                            ],
                        ],
                    ],
                ],
            ]);

        $iterator = $this->createIterator($this->getMultipleDocs($document, 101));


        $this->sut->buildIndex($iterator);
    }

    private function getMultipleDocs(MockObject $doc, $quantity)
    {
        $bigArray = [];
        for ($i = 1; $i <= $quantity; $i++) {
            array_push($bigArray, $doc);
        }

        return $bigArray;
    }


    public function testBuildIndexBulkErrorResponse(): void
    {
        $this->expectException(ClientErrorResponseException::class);
        $this->expectExceptionMessage('some reason; some other reason');
        $document = $this->createMock(IndexDocument::class);
        $document->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'type' => [
                    TaoOntology::CLASS_URI_ITEM,
                ],
            ]);

        $document->expects($this->any())
            ->method('getId')
            ->willReturn('some_id');

        $this->client
            ->method('bulk')
            ->willReturn([
                'errors' => true,
                'items' => [
                    [
                        [
                            'error' => [
                                'reason' => 'some reason',
                            ],
                        ],
                    ],
                    [
                        [
                            'error' => [
                                'reason' => 'some other reason',
                            ],
                        ],
                    ],
                ],
            ]);
        $iterator = $this->createIterator([$document]);

        $this->sut->buildIndex($iterator);
    }

    public function testBuildIndex(): void
    {
        $document = $this->createMock(IndexDocument::class);
        $document->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'type' => [
                    TaoOntology::CLASS_URI_ITEM
                ]
            ]);
        $document->expects($this->any())
            ->method('getId')
            ->willReturn('some_id');

        $this->logger->expects($this->at(0))
            ->method('info')
            ->with('indexname:' . IndexerInterface::ITEMS_INDEX);

        $this->logger->expects($this->at(1))
            ->method('info')
            ->with('adding document "some_id" to be indexed');

        $iterator = $this->createIterator([$document]);
        $iterator->expects($this->once())
            ->method('next');

        $this->client->expects($this->atLeastOnce())
            ->method('bulk')
            ->with([
                'body' => [
                    ['delete' => [
                        '_index' => IndexerInterface::ITEMS_INDEX,
                        '_id' => 'some_id'
                    ]],
                    ['create' => [
                        '_index' => IndexerInterface::ITEMS_INDEX,
                        '_id' => 'some_id'
                    ]],
                    ['type' => [
                        TaoOntology::CLASS_URI_ITEM
                    ]],
                    ['update' => [
                        '_index' => IndexerInterface::ITEMS_INDEX,
                        '_id' => 'some_id'
                    ]],
                    ['doc' => [
                        'type' => [
                            TaoOntology::CLASS_URI_ITEM
                        ]
                    ]],
                ]
            ])
            ->willReturn(['bulk_response']);

        $this->logger->expects($this->at(2))
            ->method('debug')
            ->with('client response: '. json_encode(['bulk_response']));

        $count = $this->sut->buildIndex($iterator);

        $this->assertSame(1, $count);
    }

    private function createIterator(array $items = []): \PHPUnit\Framework\MockObject\MockObject
    {
        $iteratorMock = $this->createMock(ArrayIterator::class);

        $iterator = new ArrayIterator($items);

        $iteratorMock
            ->method('rewind')
            ->willReturnCallback(function () use ($iterator): void {
                $iterator->rewind();
            });

        $iteratorMock
            ->method('current')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->current();
            });

        $iteratorMock
            ->method('key')
            ->willReturnCallback(function () use ($iterator) {
                return $iterator->key();
            });

        $iteratorMock
            ->method('next')
            ->willReturnCallback(function () use ($iterator): void {
                $iterator->next();
            });

        $iteratorMock
            ->method('valid')
            ->willReturnCallback(function () use ($iterator): bool {
                return $iterator->valid();
            });

        return $iteratorMock;
    }
}