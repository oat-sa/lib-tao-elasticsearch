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

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use oat\tao\model\search\document\Document;
use oat\tao\model\search\SearchTokenGenerator;

/**
 * Class ElasticSearchIndexer
 * @package oat\tao\elasticsearch
 */
class ElasticSearchIndexer
{

    /** @var Client|null  */
    private $client = null;

    /** @var null|\Traversable  */
    private $resources = null;

    /** @var array|null  */
    private $indexes = null;

    /** @var SearchTokenGenerator */
    private $tokenGenerator = null;

    /**
     * ElasticSearchIndexer constructor.
     * @param Client       $client
     * @param \Traversable $resourceTraversable
     * @param array        $indexes
     */
    public function __construct(Client $client, \Traversable $resourceTraversable = null, $indexes = [])
    {
        $this->client = $client;
        $this->resources = $resourceTraversable;
        $this->indexes = $indexes;
        $this->tokenGenerator = new SearchTokenGenerator();
    }

    /**
     * @return int
     * @throws \common_exception_InconsistentData
     */
    public function runReIndex($indexes = []) {

        $count = 0;

        /** @var Document $index */
        foreach ($indexes as $index) {
            $params = [
                'id' => $index->getIdentifier(),
                'type' => $index->getType(),
                'index' => $index->getIndex(),
                'body' => $index->getBody()
                ];
            $params['body']['provider'] = $index->getProvider();
            $params['body']['id'] = $index->getResponseIdentifier();
            $this->client->index($params);
            $count += 1;
        }
    
        return $count;
    }

    /**
     * @param Document $document
     * @return bool
     * @throws \common_exception_InconsistentData
     */
    public function addIndex(Document $document)
    {
        $client = $this->client;

        if ($document) {
            $params = [
                'id' => $document->getIdentifier(),
                'type' => $document->getType(),
                'index' => $document->getIndex(),
            ];
            $body = $document->getBody();
            $body['provider'] = $document->getProvider();
            $body['id'] = $document->getResponseIdentifier();
            try {
                $client->get($params);
                $params['body']['doc'] = $body;
                $params['refresh'] = true;
                $client->update($params);
            } catch (Missing404Exception $e) {
                $params['refresh'] = true;
                $params['body'] = $body;
                $this->client->index($params);
            }

            return true;
        }
        return false;
    }

    /**
     * @param $resourceId
     * @return bool
     */
    public function deleteIndex($resourceId)
    {
        $client = $this->client;
        $document = $this->searchIndexByIds([$resourceId]);

        if ($document) {
            $deleteParams = [
                'index' => $document['_index'],
                'type' => $document['_type'],
                'id' => $document['_id']
            ];
            $client->delete($deleteParams);
            return true;
        }
        return false;
    }

    /**
     * @param array  $ids
     * @param string $type
     * @return array
     */
    public function searchIndexByIds($ids = [], $type = 'document')
    {
        $client = $this->client;
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
        $response = $client->search($searchParams);
        $hits = isset($response['hits']) ? $response['hits'] : [];
        $document = [];
        if ($hits && isset($hits['total']) && $hits['total'] && isset($hits['hits'])) {
            $document = current($hits['hits']);
        }
        return $document;
    }
}
