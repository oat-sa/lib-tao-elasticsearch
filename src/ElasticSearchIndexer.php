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

namespace oat\tao\elasticsearch;

use Iterator;
use Elasticsearch\Client;
use oat\tao\model\search\index\IndexDocument;

/**
 * Class ElasticSearchIndexer
 * @package oat\tao\elasticsearch
 */
class ElasticSearchIndexer implements IndexerInterface
{
    const INDEXING_BLOCK_SIZE = 100;

    /** @var Client|null  */
    private $client = null;

    /** @var string */
    private $index;

    /** @var string */
    private $type;

    /**
     * ElasticSearchIndexer constructor.
     * @param Client $client
     * @param string $index
     * @param string $type
     */
    public function __construct(Client $client, $index = 'documents', $type = 'document')
    {
        $this->client = $client;
        $this->index = $index;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Client|null
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * @param Iterator $documents
     * @return int
     */
    public function buildIndex(Iterator $documents)
    {
        $count = 0;
        while ($documents->valid()) {
            $blockSize = 0;
            $params = ['body' => []];

            while ($documents->valid() && $blockSize < self::INDEXING_BLOCK_SIZE) {
                /** @var IndexDocument $document */
                $document = $documents->current();

                // First step we trying to create document. If is exist, then skip this step
                $params['body'][] = [
                    'create' => [
                        '_index' => $this->getIndex(),
                        '_type' => $this->getType(),
                        '_id' => $document->getId()
                    ]
                ];

                $params['body'][] = $document->getBody();

                // Trying to update document
                $params['body'][] = [
                    'update' => [
                        '_index' => $this->getIndex(),
                        '_type' => $this->getType(),
                        '_id' => $document->getId()
                    ]
                ];

                $params['body'][]['doc'] = $document->getBody();
                $documents->next();
                $blockSize++;
            }
            if ($blockSize > 0) {
                $responses = $this->client->bulk($params);
                $count += $blockSize;
                unset($responses);
            }
        }

        return $count;
    }

    /**
     * @param $id
     * @return bool
     */
    public function deleteIndex($id)
    {
        $document = $this->searchResourceByIds([$id]);

        if ($document) {
            $deleteParams = [
                'index' => $document['_index'],
                'type' => $document['_type'],
                'id' => $document['_id']
            ];
            $this->getClient()->delete($deleteParams);

            return true;
        }

        return false;
    }

    /**
     * @param array  $ids
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
}
