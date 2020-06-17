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

namespace oat\tao\test\elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\BadMethodCallException;
use oat\tao\elasticsearch\FailToUpdatePropertiesException;
use oat\generis\test\TestCase;
use oat\tao\elasticsearch\IndexUpdater;
use oat\tao\model\TaoOntology;
use ReflectionObject;
use ReflectionProperty;

class IndexUpdaterTest extends TestCase
{
    /** @var IndexUpdater */
    private $sut;

    /** @var ReflectionProperty */
    private $clientProperty;

    /** @var Client */
    private $client;

    protected function setUp(): void
    {
        $this->sut = new IndexUpdater();
        $this->client = $this->createMock(Client::class);

        $reflection = new ReflectionObject($this->sut);
        $this->clientProperty = $reflection->getProperty('client');
        $this->clientProperty->setAccessible(true);
        $this->clientProperty->setValue(
            $this->sut,
            $this->client
        );
    }

    /**
     * @dataProvider provideValidProperties
     */
    public function testUpdatePropertiesSuccessfull(array $properties, string $source): void
    {
        $this->client->expects($this->once())
            ->method('updateByQuery')
            ->with(
                [
                    'index' => 'items',
                    'type' => '_doc',
                    'conflicts' => 'proceed',
                    'wait_for_completion' => true,
                    'body' => [
                        'query' => [
                            'match' => [
                                'type' => TaoOntology::CLASS_URI_ITEM
                            ]
                        ],
                        'script' => [
                            'source' => $source
                        ]
                    ]
                ]
            );
        $this->sut->updateProperties(
            $properties
        );
    }

    public function testClientException(): void {
        $this->expectException(FailToUpdatePropertiesException::class);

        $this->client->expects($this->once())
            ->method('updateByQuery')
            ->willThrowException(new BadMethodCallException());

        $this->sut->updateProperties(
            [
                [
                    'newName' => 'test',
                    'oldName' => 'devel',
                    'parentClasses' => [],
                    'type' => TaoOntology::CLASS_URI_ITEM
                ]
            ]
        );
    }

    /**
     * @dataProvider provideInvalidProperties
     */
    public function testDoNotUpdateProperties(array $properties): void
    {
        $this->client->expects($this->never())
            ->method('updateByQuery');

        $this->sut->updateProperties(
            $properties
        );
    }

    public function provideValidProperties(): array
    {
        return [
            'Single' => [
                'properties' =>
                    [
                        [
                            'newName' => 'test',
                            'oldName' => 'devel',
                            'parentClasses' => [],
                            'type' => TaoOntology::CLASS_URI_ITEM
                        ]
                    ],
                'source' => 'ctx._source[\'test\'] = ctx._source[\'devel\']; ctx._source.remove(\'devel\');'
            ],
            'Multiple' => [
                'properties' =>
                    [
                        [
                            'newName' => 'test',
                            'oldName' => 'devel',
                            'parentClasses' => [],
                            'type' => TaoOntology::CLASS_URI_ITEM
                        ],
                        [
                            'newName' => 'CheckBox_property-6',
                            'oldName' => 'TextArea_devel',
                            'parentClasses' => [],
                            'type' => TaoOntology::CLASS_URI_ITEM
                        ],
                    ],
                'source' => 'ctx._source[\'test\'] = ctx._source[\'devel\']; ctx._source[\'CheckBox_property-6\'] = ctx._source[\'TextArea_devel\']; ctx._source.remove(\'devel\'); ctx._source.remove(\'TextArea_devel\');'
            ],
        ];
    }

    public function provideInvalidProperties(): array
    {
        return [
            'With No OldName' => [
                'properties' =>
                    [
                        [
                            'newName' => 'test',
                            'parentClasses' => [],
                            'type' => TaoOntology::CLASS_URI_ITEM
                        ]
                    ]
            ],
            'With No NewName' => [
                'properties' =>
                    [
                        [
                            'oldName' => 'devel',
                            'parentClasses' => [],
                            'type' => TaoOntology::CLASS_URI_ITEM
                        ]
                    ]
            ],
            'With No Type' => [
                'properties' =>
                    [
                        [
                            'newName' => 'test',
                            'oldName' => 'devel',
                            'parentClasses' => [],
                        ]
                    ]
            ],
            'With No parentClass' => [
                'properties' =>
                    [
                        [
                            'newName' => 'test',
                            'oldName' => 'devel',
                            'type' => TaoOntology::CLASS_URI_ITEM
                        ]
                    ]
            ],
            'With invalidType' => [
                'properties' =>
                    [
                        [
                            'newName' => 'test',
                            'oldName' => 'devel',
                            'type' => 'invalidType',
                            'parentClasses' => [],
                        ]
                    ]
            ],
        ];
    }
}
