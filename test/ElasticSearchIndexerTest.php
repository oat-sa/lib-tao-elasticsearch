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
 * Copyright (c) 2020-2022 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\tao\test\elasticsearch;

use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\tao\elasticsearch\ElasticSearchIndexer;
use oat\tao\elasticsearch\IndexerInterface;
use oat\tao\model\search\index\IndexDocument;
use oat\tao\model\TaoOntology;
use Elastic\Elasticsearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use ArrayIterator;

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
            ->with(
                '[documentId: "some_id"] Queuing document with types '.
                'http://www.tao.lu/Ontologies/TAOItem.rdf#Item '.
                sprintf('into index "%s"', IndexerInterface::ITEMS_INDEX)
            );

        $this->logger->expects($this->at(1))
            ->method('debug')
            ->with(
                ElasticSearchIndexer::class . '::buildIndex'.
                ': Flushing batch with 1 operations'
            );

        $iterator = $this->createIterator([$document]);
        $iterator->expects($this->once())
            ->method('next');

        $this->client->expects($this->atLeastOnce())
            ->method('bulk')
            ->with([
                'body' => [
                    ['index' => [
                        '_index' => IndexerInterface::ITEMS_INDEX,
                        '_id' => 'some_id'
                    ]],
                    ['type' => [
                            TaoOntology::CLASS_URI_ITEM
                    ]],
                ]
            ])
            ->willReturn(['bulk_response']);

        $this->logger->expects($this->at(2))
            ->method('debug')
            ->with('Processed 1 items (no exceptions, no skipped items)');

        $count = $this->sut->buildIndex($iterator);

        $this->assertSame(1, $count);
    }

    private function createIterator(array $items = []): MockObject
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