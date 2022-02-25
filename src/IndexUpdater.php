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
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use oat\oatbox\service\ConfigurableService;
use oat\tao\elasticsearch\Exception\FailToRemovePropertyException;
use oat\tao\elasticsearch\Exception\FailToUpdatePropertiesException;
use oat\tao\model\search\index\IndexUpdaterInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class IndexUpdater extends ConfigurableService implements IndexUpdaterInterface
{
    use LogIndexOperationsTrait;

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
            $this->logSkippedUpdate(
                $this->getLogger(),
                'type or parentClasses not set',
                null,
                __METHOD__,
                '',
                $type ?? null,
                $parentClasses ?? null
            );

            return;
        }

        $index = $this->findIndex($parentClasses, $type);

        if ($index === IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
            $this->logUnclassifiedDocument(
                $this->getLogger(),
                null,
                __METHOD__,
                $index,
                $type,
                $parentClasses
            );

            return;
        }

        $script = implode(' ', array_merge($queryNewProperty, $queryRemoveOldProperty));

        $query = $this->getUpdateQuery($index, $type, $script);

        try {
            $this->getClient()->updateByQuery($query);
        } catch (Throwable $e) {
            $this->logIndexFailure($this->getLogger(), $e, __METHOD__, $script, $type, $query);

            throw new FailToUpdatePropertiesException(
                sprintf(
                    'by script: %s AND type: %s',
                    $script,
                    $type
                ),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws FailToUpdatePropertiesException
     */
    public function updatePropertyValue(string $typeOrId, array $parentClasses, string $propertyName, array $value): void
    {
        $index = $this->findIndex($parentClasses, $typeOrId);

        if ($index === IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
            $this->logUnclassifiedDocument(
                $this->getLogger(),
                null,
                __METHOD__,
                $index,
                null,
                $parentClasses
            );

            return;
        }

        $script = sprintf(
            'ctx._source[\'%s\'] = [\'%s\'];',
            $propertyName,
            implode('\', \'', $value)
        );

        $query = $this->getUpdateQuery($index, $typeOrId, $script);

        try {
            $this->getClient()->updateByQuery($query);
        } catch (Throwable $e) {
            $this->logIndexFailure($this->getLogger(), $e, __METHOD__, $script, $typeOrId, $query);

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

    /**
     * @throws FailToRemovePropertyException
     */
    public function deleteProperty(array $property): void
    {
        $name = $property['name'];
        $type = $property['type'];
        $parentClasses = $property['parentClasses'];

        if (empty($name) || empty($type)) {
            return;
        }

        $index = $this->findIndex($parentClasses, $type);

        if ($index === IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX) {
            return;
        }

        $script = sprintf(
            'ctx._source.remove(\'%s\');',
            $name
        );

        $query = $this->getUpdateQuery($index, $type, $script);

        try {
            $this->getClient()->updateByQuery($query);
        } catch (Throwable $e) {
            $this->logIndexFailure($this->getLogger(), $e, __METHOD__, $script, $type, $query);

            throw new FailToRemovePropertyException(
                sprintf(
                    'by script: %s AND type: %s',
                    $script,
                    $type
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

    private function getUpdateQuery(string $index, string $typeOrId, string $script): array
    {
        return [
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
        ];
    }
}
