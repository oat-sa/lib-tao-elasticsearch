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
 * Copyright (c) 2018-2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch;

use oat\tao\model\search\index\IndexDocument;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Exception;
use Iterator;
use RuntimeException;
use Throwable;

class ElasticSearchIndexer implements IndexerInterface
{
    use LogIndexOperationsTrait;

    private const INDEXING_BLOCK_SIZE = 100;

    /** @var Client */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    protected function getClient(): Client
    {
        return $this->client;
    }

    public function getIndexNameByDocument(IndexDocument $document): string
    {
        $documentBody = $document->getBody();

        if (!isset($documentBody['type'])) {
            throw new RuntimeException('type property is undefined on the document');
        }

        $documentType = is_string($documentBody['type']) ? [$documentBody['type']] : $documentBody['type'];

        foreach (self::AVAILABLE_INDEXES as $ontology => $indexName) {
            if (in_array($ontology, $documentType)) {
                return $indexName;
            }
        }

        return self::UNCLASSIFIEDS_DOCUMENTS_INDEX;
    }

    /**
     * @param Iterator $documents
     * @return int The number of indexed documents
     */
    public function buildIndex(Iterator $documents): int
    {
        $visited = 0;
        $skipped = 0;
        $exceptions = 0;
        $count = 0;
        $blockSize = 0;
        $params = [];

        while ($documents->valid()) {
            /** @var IndexDocument $document */
            $document = $documents->current();
            $visited++;

            try {
                $indexName = $this->getIndexNameByDocument($document);
            } catch (Exception $e) {
                $this->logIndexFailure($this->logger, $e, __METHOD__);
                $exceptions++;

                continue;
            }

            if ($indexName === self::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
                $this->logUnclassifiedDocument(
                    $this->logger,
                    $document,
                    __METHOD__,
                    $indexName
                );

                $this->logMappings($this->logger, $document);

                $documents->next();
                $skipped++;

                continue;
            }

            $this->logAddingDocumentToQueue($this->logger, $document, $indexName);
            $params = $this->extendBatch('index', $indexName, $document, $params);

            $documents->next();

            $blockSize++;

            if ($blockSize === self::INDEXING_BLOCK_SIZE) {
                $this->logBatchFlush($this->logger, __METHOD__, $params);

                $response = $this->client->bulk($params);
                $this->logErrorsFromResponse($this->logger, $document, $response);

                $count += $blockSize;
                $blockSize = 0;
                $params = [];
            }
        }

        if ($blockSize > 0) {
            $this->logBatchFlush($this->logger, __METHOD__, $params);

            $response = $this->client->bulk($params);
            $this->logErrorsFromResponse($this->logger, null, $response);

            $count += $blockSize;
        }

        $this->logCompletion($this->logger, $count, $visited, $skipped, $exceptions);

        return $count;
    }

    /**
     * @inheritDoc
     */
    public function deleteDocument($id): bool
    {
        $document = $this->searchResourceByIds([$id]);

        if ($document) {
            $deleteParams = [
                'type' => '_doc',
                'index' => $document['_index'],
                'id' => $document['_id']
            ];

            try {
                $this->getClient()->delete($deleteParams);
                $this->debug($this->logger, $document, 'Document deleted');
            } catch (Throwable $e) {
                $this->logDocumentFailure(
                    $this->logger,
                    $e,
                    __METHOD__,
                    $document,
                    $id,
                    $deleteParams
                );
            }

            return true;
        }

        $this->info($this->logger, $document, 'Document to delete not found: %s', $id);

        return false;
    }

    /**
     * @inheritDoc
     */
    public function searchResourceByIds($ids = [])
    {
        $searchParams = [
            'body' => [
                'query' => [
                    'ids' => [
                        'values' => $ids
                    ]
                ]
            ]
        ];
        $response = $this->getClient()->search($searchParams);
        $hits = $response['hits'] ?? [];

        $document = [];
        if ($hits && isset($hits['hits']) && isset($hits['total']) && $hits['total']) {
            $document = current($hits['hits']);
        }

        return $document;
    }

    private function extendBatch(string $action, string $indexName, IndexDocument $document, array $params): array
    {
        $params['body'][] = [
            $action => [
                '_index' => $indexName,
                '_id' => $document->getId()
            ]
        ];

        if ('delete' === $action) {
            return $params;
        }

        $body = array_merge(
            $document->getBody(),
            (array)$document->getDynamicProperties(),
            (array)$document->getAccessProperties()
        );

        if ($action === 'update') {
            $body = ['doc' => $body];
        }

        $params['body'][] = $body;

        return $params;
    }
}
