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

namespace oat\tao\elasticsearch\Specification;

use oat\generis\model\data\permission\PermissionInterface;
use oat\generis\model\data\permission\ReverseRightLookupInterface;
use oat\oatbox\user\User;
use oat\tao\elasticsearch\IndexerInterface;

class UseAclSpecification
{
    public function isSatisfiedBy(string $index, PermissionInterface $permission, User $user): bool
    {
        return in_array($index, IndexerInterface::INDEXES_WITH_ACCESS_CONTROL, true)
            && $permission instanceof ReverseRightLookupInterface
            && !$this->hasReadAccess($permission, $user);
    }

    private function hasReadAccess(PermissionInterface $permission, User $user): bool
    {
        $nonExistingId = uniqid();
        $permissions = $permission->getPermissions($user, [$nonExistingId])[$nonExistingId] ?? [];

        return in_array(PermissionInterface::RIGHT_READ, $permissions, true);
    }
}
