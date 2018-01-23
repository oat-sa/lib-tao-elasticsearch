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
use oat\tao\model\search\index\IndexDocument;
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
     * @param IndexDocument $document
     * @return bool
     * @throws \common_exception_InconsistentData
     */
    public function addIndex(IndexDocument $document)
    {
        $client = $this->client;

        if ($document) {
            $type = strtolower(\tao_helpers_Uri::encode($document->getType()));
            $params = [
                'id' => $document->getId(),
                'type' => 'document',
                'index' => strtolower('documents-'.$type),
            ];

            $body = $document->getBody();
            $body['response_id'] = $document->getResponseId();
            $body['type'] = $type;
            try {
                $client->get($params);
                $params['body']['doc'] = $body;
                $params['refresh'] = true;
                $client->update($params);
            } catch (Missing404Exception $e) {
                $params['refresh'] = true;
                $params['body'] = $body;
                \common_Logger::i(json_encode($params));
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
