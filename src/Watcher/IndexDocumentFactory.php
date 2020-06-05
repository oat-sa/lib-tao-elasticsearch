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
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch\Watcher;

use oat\tao\model\search\index\IndexDocumentBuilderInterface;

class IndexDocumentFactory
{
    const AVAILABLE_INDEXERS = [
        'http://www.tao.lu/Ontologies/TAOItem.rdf#Item' => 'oat\\tao\\elasticsearch\\Watcher\\Resources\\ItemIndexDocumentBuilder',
        'http://www.tao.lu/Ontologies/TAOTest.rdf#Test' => 'oat\\tao\\elasticsearch\\Watcher\\Resources\\GenericIndexDocumentBuilder',
        'http://www.tao.lu/Ontologies/TAOGroup.rdf#Group' => 'oat\\tao\\elasticsearch\\Watcher\\Resources\\GenericIndexDocumentBuilder',
        'http://www.tao.lu/Ontologies/TAODelivery.rdf#Delivery' => 'oat\\tao\\elasticsearch\\Watcher\\Resources\\GenericIndexDocumentBuilder',
        'http://www.tao.lu/Ontologies/TAOSubject.rdf#Subject' => 'oat\\tao\\elasticsearch\\Watcher\\Resources\\TesttakerIndexDocumentBuilder',
        'unclassified' => 'oat\\tao\\elasticsearch\\Watcher\\Resources\\UnclassifiedIndexDocumentBuilder'
    ];

    /**
     * Get the IndexDocument builder based on resource type property
     * @param string $resourceType
     * @return IndexDocumentBuilderInterface
     */
    public function getDocumentBuilderByResourceType(string $resourceType): IndexDocumentBuilderInterface
    {
        if (array_key_exists($resourceType, self::AVAILABLE_INDEXERS)) {
            $indexer = self::AVAILABLE_INDEXERS[$resourceType];
        } else {
            $indexer = self::AVAILABLE_INDEXERS['unclassified'];
        }

        return new $indexer();
    }

}
