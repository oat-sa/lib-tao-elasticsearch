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

namespace oat\tao\elasticsearch\Watcher\Resources;

use oat\tao\model\search\index\IndexDocument;
use oat\taoQtiItem\model\qti\Service;

class ItemIndexDocumentBuilder extends AbstractIndexDocumentBuilder
{
    /**
     * {@inheritdoc}
     */
    public function createDocumentFromResource(\core_kernel_classes_Resource $resource): IndexDocument
    {
        $classProperty = $resource->getOnePropertyValue($this->getProperty(self::TYPE_PROPERTY));
        $classResource = $this->getProperty($classProperty);

        $resourceModel = $resource->getOnePropertyValue($this->getProperty('http://www.tao.lu/Ontologies/TAOItem.rdf#ItemModel'));

        $body = [
            'class' => $classResource->getLabel(),
            'content' => $this->getItemContentXML($resource),
            'label' => $resource->getLabel(),
            'model' => $resourceModel
        ];

        $resourceType = current(array_keys($resource->getTypes()));
        $body['type'] = $resourceType;

        $document = new IndexDocument(
            $resource->getUri(),
            $body
        );

        return $document;
    }
    
    /**
     * Get items' content in XML form
     * @param \core_kernel_classes_Resource $itemResource
     * @return string
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     */
    private function getItemContentXML(\core_kernel_classes_Resource $itemResource): string
    {
        $itemContent = "";

        $contentProperty = $this->getProperty('http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent');

        if ($itemResource->getOnePropertyValue($contentProperty) != "") {
            $itemService = Service::singleton();

            $itemContent = $itemService->getXmlByRdfItem($itemResource);
        }

        return $itemContent;
    }
}
