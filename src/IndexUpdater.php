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
 *
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use oat\generis\model\WidgetRdf;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\search\index\IndexUpdaterInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class IndexUpdater extends ConfigurableService implements IndexUpdaterInterface
{
    /** @var Client */
    private $client;

    protected function getClient(): Client
    {
        if (is_null($this->client)) {
            $this->client = ClientBuilder::create()
                ->setHosts($this->getOptions())
                ->build();
        }

        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function updateProperties(array $properties): void
    {
        $queryNewProperty = [];
        $queryRemoveOldProperty = [];
        foreach ($properties as $propertyData) {
            if (!isset($propertyData['type'], $propertyData['parentClasses'], $propertyData['oldName'], $propertyData['newName'])) {
                continue;
            }

            $type = $propertyData['type'];
            $parentClasses = $propertyData['parentClasses'];

            $queryNewProperty[] = sprintf(
                'ctx._source[\'%s\'] = ctx._source[\'%s\'];',
                $propertyData['newName'],
                $propertyData['oldName']
            );
            $queryRemoveOldProperty[] = sprintf(
                'ctx._source.remove(\'%s\');',
                $propertyData['oldName']
            );
        }

        if (!isset($type, $parentClasses)) {
            return;
        }

        $index = $this->findIndex($parentClasses, $type);

        if ($index === IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
            return;
        }

        $result = implode(' ', array_merge($queryNewProperty, $queryRemoveOldProperty));

        try {
            $this->getClient()->updateByQuery(
                [
                    'index' => $index,
                    'type' => '_doc',
                    'conflicts' => 'proceed',
                    'wait_for_completion' => true,
                    'body' => [
                        'query' => [
                            'match' => [
                                'type' => $type
                            ]
                        ],
                        'script' => [
                            'source' => $result
                        ]
                    ]
                ]
            );
        } catch (Throwable $e) {
            throw new FailToUpdatePropertiesException(
                sprintf(
                    'by script: %s AND type: %s',
                    $result,
                    $type
                ),
                $e->getCode(),
                $e
            );
        }
    }

    public function deleteProperty(array $property): void
    {
        $name = $property['name'];
        $parentClasses = $property['parentClasses'];
        $type = $property['type'];

        $index = $this->findIndex($parentClasses, $type);

        $script = sprintf(
            'ctx._source.remove(\'%s\');',
            $name
        );

        try {
            $this->getClient()->updateByQuery(
                [
                    'index' => $index,
                    'type' => '_doc',
                    'conflicts' => 'proceed',
                    'wait_for_completion' => true,
                    'body' => [
                        'query' => [
                            'match' => [
                                'type' => $type
                            ]
                        ],
                        'script' => [
                            'source' => $script
                        ]
                    ]
                ]
            );
        } catch (Throwable $e) {
            throw new FailToUpdatePropertiesException(
                sprintf(
                    'by script: %s AND type: %s',
                    $result,
                    $type
                ),
                $e->getCode(),
                $e
            );
        }
    }


    private function findIndex(array $parentClasses, string $type): string
    {
        if (isset(IndexerInterface::AVAILABLE_INDEXES[$type])) {
            return IndexerInterface::AVAILABLE_INDEXES[$type];
        }

        foreach ($parentClasses as $parentClass) {
            if (isset(IndexerInterface::AVAILABLE_INDEXES[$parentClass])) {
                return IndexerInterface::AVAILABLE_INDEXES[$parentClass];
            }
        }

        return IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX;
    }
}
