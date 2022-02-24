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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\tao\test\elasticsearch\Specification;

use oat\generis\model\data\permission\PermissionInterface;
use oat\generis\model\data\permission\ReverseRightLookupInterface;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\tao\elasticsearch\IndexerInterface;
use oat\tao\elasticsearch\Specification\UseAclSpecification;

interface PermissionMock extends PermissionInterface, ReverseRightLookupInterface {
}

class UseAclSpecificationTest extends TestCase
{
    /** @var UseAclSpecification */
    private $subject;

    /** @var User|MockObject */
    private $user;

    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
        $this->subject = new UseAclSpecification();
    }

    /**
     * @dataProvider isSatisfiedByProvider
     */
    public function testIsSatisfiedBy(bool $expected, array $config): void
    {
        $permission = $this->createMock($config['permissionClass']);
        $permission->method('getPermissions')
            ->willReturnCallback(
                function ($user, $permissions) use ($config) {                    
                    return [
                        $permissions[0] => $config['permissions'],
                    ];
                }
            );

        $this->assertSame(
            $expected,
            $this->subject->isSatisfiedBy(
                $config['index'],
                $permission,
                $this->user
            )
        );
    }

    public function isSatisfiedByProvider(): array
    {
        return [
            'Must apply ACL if user DOES NOT have any access' => [
                'expected' => true,
                'config' => [
                    'index' => IndexerInterface::ITEMS_INDEX,
                    'permissionClass' => PermissionMock::class,
                    'permissions' => []
                ]
            ],
            'Must apply ACL if user DOES NOT have read access' => [
                'expected' => true,
                'config' => [
                    'index' => IndexerInterface::ITEMS_INDEX,
                    'permissionClass' => PermissionMock::class,
                    'permissions' => [
                        PermissionInterface::RIGHT_WRITE,
                    ]
                ]
            ],
            'Must NOT apply ACL if permission does not implement ' . ReverseRightLookupInterface::class => [
                'expected' => false,
                'config' => [
                    'index' => IndexerInterface::ITEMS_INDEX,
                    'permissionClass' => PermissionInterface::class,
                    'permissions' => []
                ]
            ],
            'Must NOT apply ACL if index does not support ACL' => [
                'expected' => false,
                'config' => [
                    'index' => IndexerInterface::DELIVERIES_INDEX,
                    'permissionClass' => PermissionMock::class,
                    'permissions' => []
                ]
            ],
            'Must NOT apply ACL if user HAVE read access' => [
                'expected' => false,
                'config' => [
                    'index' => IndexerInterface::ITEMS_INDEX,
                    'permissionClass' => PermissionMock::class,
                    'permissions' => [
                        PermissionInterface::RIGHT_READ,
                    ]
                ]
            ],
        ];
    }
}
