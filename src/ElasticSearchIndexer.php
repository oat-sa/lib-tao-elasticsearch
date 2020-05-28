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

use Elasticsearch\Client;
use RuntimeException;
use Iterator;
use oat\tao\model\search\index\IndexDocument;

/**
 * Class ElasticSearchIndexer
 * @package oat\tao\elasticsearch
 */
class ElasticSearchIndexer implements IndexerInterface
{
    private const INDEXING_BLOCK_SIZE = 100;

    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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

        $documentType = $documentBody['type'];

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
        $count = 0;
        $blockSize = 0;
        $params = [];

        while ($documents->valid()) {
            /** @var IndexDocument $document */
            $document = $documents->current();

            // First step we trying to create document. If is exist, then skip this step
            $indexName = $this->getIndexNameByDocument($document);

            if ($indexName === self::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
                \common_Logger::i(sprintf('There is no proper index for the document "%s"', $document->getId()));

                $documents->next();
                continue;
            }

            \common_Logger::i(sprintf('adding document "%s" to be indexed', $document->getId()));

            $params = $this->extendBatch('delete', $indexName, $document, $params);
            $params = $this->extendBatch('create', $indexName, $document, $params);
            $params = $this->extendBatch('update', $indexName, $document, $params);

            $documents->next();

            $blockSize++;

            if ($blockSize === self::INDEXING_BLOCK_SIZE) {
                $this->client->bulk($params);
                $count += $blockSize;
                $blockSize = 0;
                $params = [];
            }
        }

        if ($blockSize > 0) {
            $this->client->bulk($params);
            $count += $blockSize;
        }

        return $count;
    }

    /**
     * @param $id
     * @return bool
     */
    public function deleteDocument($id): bool
    {
        $document = $this->searchResourceByIds([$id]);

        if ($document) {
            $deleteParams = [
                'index' => $document['_index'],
                'id' => $document['_id']
            ];
            $this->getClient()->delete($deleteParams);

            return true;
        }

        return false;
    }

    /**
     * @param array $ids
     * @param string $type
     * @return array
     */
    public function searchResourceByIds($ids = [], $type = 'document')
    {
        $searchParams = [
            'body' => [
                'query' => [
                    'ids' => [
                        'type' => $type,
                        'values' => $ids
                    ]
                ]
            ]
        ];
        $response = $this->getClient()->search($searchParams);
        $hits = isset($response['hits'])
            ? $response['hits']
            : [];

        $document = [];
        if ($hits && isset($hits['hits']) && isset($hits['total']) && $hits['total']) {
            $document = current($hits['hits']);
        }

        return $document;
    }

    /**
     * @param string $indexName
     * @param IndexDocument $document
     * @param array $params
     *
     * @return array
     */
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

        $body = array_merge($document->getBody(), (array)$document->getDynamicProperties());

        if ($action === 'update') {
            $body = ['doc' => $body];
        }

        $params['body'][] = $body;

        return $params;
    }
}
