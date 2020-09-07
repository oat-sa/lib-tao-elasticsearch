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
use oat\tao\elasticsearch\Exception\FailToRemovePropertyException;
use oat\tao\elasticsearch\Exception\FailToUpdatePropertiesException;
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
    public function updatePropertiesName(array $properties): void
    {
        $queryNewProperty = [];
        $queryRemoveOldProperty = [];
        foreach ($properties as $propertyData) {
            if (!isset($propertyData['type'], $propertyData['parentClasses'], $propertyData['oldName'], $propertyData['newName'])) {
                continue;
            }

            $typeOrId = $propertyData['type'];
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

        if (!isset($typeOrId, $parentClasses)) {
            return;
        }

        $index = $this->findIndex($parentClasses, $typeOrId);

        if ($index === IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
            return;
        }

        $script = implode(' ', array_merge($queryNewProperty, $queryRemoveOldProperty));

        try {
            $this->executeUpdateQuery($index, $typeOrId, $script);
        } catch (Throwable $e) {
            throw new FailToUpdatePropertiesException(
                sprintf(
                    'by script: %s AND type: %s',
                    $script,
                    $typeOrId
                ),
                $e->getCode(),
                $e
            );
        }
    }

    public function updatePropertyValue(string $typeOrId, array $parentClasses, string $propertyName, array $value): void
    {
        $index = $this->findIndex($parentClasses, $typeOrId);

        if ($index === IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
            return;
        }

        $script = sprintf(
            'ctx._source[\'%s\'] = [\'%s\'];',
            $propertyName,
            implode('\', \'', $value)
        );

        try {
            $this->executeUpdateQuery($index, $typeOrId, $script);
        } catch (Throwable $e) {
            throw new FailToUpdatePropertiesException(
                sprintf(
                    'by script: %s AND type: %s',
                    $script,
                    $typeOrId
                ),
                $e->getCode(),
                $e
            );
        }
    }

    public function deleteProperty(array $property): void
    {
        $name = $property['name'];
        $typeOrId = $property['type'];
        $parentClasses = $property['parentClasses'];

        if (empty($name) || empty($typeOrId)) {
            return;
        }

        $index = $this->findIndex($parentClasses, $typeOrId);

        if ($index === IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
            return;
        }

        $script = sprintf(
            'ctx._source.remove(\'%s\');',
            $name
        );

        try {
            $this->executeUpdateQuery($index, $typeOrId, $script);
        } catch (Throwable $e) {
            throw new FailToRemovePropertyException(
                sprintf(
                    'by script: %s AND type: %s',
                    $script,
                    $typeOrId
                ),
                $e->getCode(),
                $e
            );
        }
    }

    public function hasClassSupport(string $class): bool
    {
        return isset(IndexerInterface::AVAILABLE_INDEXES[$class]);
    }

    private function findIndex(array $parentClasses, string $typeOrId): string
    {
        if (isset(IndexerInterface::AVAILABLE_INDEXES[$typeOrId])) {
            return IndexerInterface::AVAILABLE_INDEXES[$typeOrId];
        }

        foreach ($parentClasses as $parentClass) {
            if (isset(IndexerInterface::AVAILABLE_INDEXES[$parentClass])) {
                return IndexerInterface::AVAILABLE_INDEXES[$parentClass];
            }
        }

        return IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX;
    }

    private function executeUpdateQuery(string $index, string $typeOrId, string $script): void
    {
        $this->getClient()->updateByQuery(
            [
                'index' => $index,
                'type' => '_doc',
                'conflicts' => 'proceed',
                'wait_for_completion' => true,
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'query' => $typeOrId,
                            'fields' => ['type', '_id'],
                        ]
                    ],
                    'script' => [
                        'source' => $script
                    ]
                ]
            ]
        );
    }
}
