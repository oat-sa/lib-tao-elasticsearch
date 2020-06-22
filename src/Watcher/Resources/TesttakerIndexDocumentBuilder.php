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

use oat\generis\model\GenerisRdf;
use oat\tao\model\search\index\IndexDocument;
use oat\tao\model\search\index\DocumentBuilder\AbstractIndexDocumentBuilder;

class TesttakerIndexDocumentBuilder extends AbstractIndexDocumentBuilder
{
    /**
     * {@inheritdoc}
     */
    public function createDocumentFromResource(\core_kernel_classes_Resource $resource, string $rootResourceType = ""): IndexDocument
    {
        $classProperty = $resource->getOnePropertyValue($this->getProperty(self::TYPE_PROPERTY));
        $classResource = $this->getProperty($classProperty);

        $loginProperty = $this->getProperty(GenerisRdf::PROPERTY_USER_LOGIN);
        $login = $resource->getOnePropertyValue($loginProperty);

        $body = [
            'class' => $classResource->getLabel(),
            'label' => $resource->getLabel(),
            'login' => (string)$login
        ];

        if ($rootResourceType) {
            $body['type'] = $rootResourceType;
        } else {
            $body['type'] = $resource->getTypes();
        }
    
        $dynamicProperties = $this->getDynamicProperties($resource->getTypes(), $resource);
    
        if (!is_array($body['type'])) {
            $body['type'] = [$body['type']];
        }
    
        return new IndexDocument(
            $resource->getUri(),
            $body,
            [],
            $dynamicProperties
        );
    }
}
