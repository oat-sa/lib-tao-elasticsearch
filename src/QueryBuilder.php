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

namespace oat\tao\elasticsearch;

use oat\generis\model\data\permission\PermissionInterface;
use oat\generis\model\data\permission\ReverseRightLookupInterface;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use tao_helpers_Slug;

class QueryBuilder extends ConfigurableService
{
    private const READ_ACCESS_FIELD = 'read_access';

    private const STANDARD_FIELDS = [
        'class',
        'content',
        'label',
        'model',
        'login',
    ];

    private const CUSTOM_FIELDS = [
        'HTMLArea',
        'TextArea',
        'TextBox',
        'ComboBox',
        'CheckBox',
        'RadioBox',
    ];

    public static function create(): self
    {
        return new self();
    }

    public function getSearchParams(string $queryString, string $type, int $start, int $count, string $order, string $dir): array
    {
        $decoded_query_string = htmlspecialchars_decode($queryString);
        $blocks = preg_split( '/( AND )/i', $decoded_query_string);
        $query = [];

        foreach ($blocks as $block) {
            preg_match('/((?P<field>.*):)?(?P<term>.*)/', $block,$matches);
            $field = tao_helpers_Slug::create(trim($matches['field']));
            $term = $this->updateIfUri(trim($matches['term']));

            if (empty($field)) {
                $query[] = sprintf('("%s")', $term);
            } elseif ($this->isStandardField($field)) {
                $query[] = sprintf('(%s:"%s")', $field, $term);
            } else {
                $query[] = $this->buildCustomConditions($field, $term);
            }
        }

        if ($this->includeAccessData()) {
            $query[] = $this->buildAccessConditions();
        }

        $query = [
            'query' => [
                'query_string' =>
                    [
                        'default_operator' => 'AND',
                        'query' => implode(' AND ', $query)
                    ]
            ],
            'sort' => [$order => ['order' => $dir]]
        ];

        $params = [
            'index' => $this->getIndexByType($type),
            'size' => $count,
            'from' => $start,
            'client' => ['ignore' => 404],
            'body' => json_encode($query)
        ];

        return $params;
    }

    private function isStandardField(string $field): bool
    {
        return in_array(strtolower($field), self::STANDARD_FIELDS);
    }

    private function getIndexByType(string $type): string
    {
        return IndexerInterface::AVAILABLE_INDEXES[$type] ?? IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX;
    }

    private function updateIfUri(string $query): string
    {
        if (\common_Utils::isUri($query)) {
            $query = '"' . $query . '"';
        }
        return $query;
    }

    private function buildCustomConditions(string $fieldName, string $term): string
    {
        $conditions = [];

        foreach (self::CUSTOM_FIELDS as $customField) {
            $conditions[] = sprintf('%s_%s:"%s"', $customField, $fieldName, $term);
        }

        return '(' . implode(' OR ', $conditions). ')';
    }

    private function buildAccessConditions(): string
    {
        $conditions = [];

        /** @var User $current_user */
        $current_user = $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentUser();

        foreach ($current_user->getRoles() as $role) {
            $conditions[] = sprintf('%s:"%s"', self::READ_ACCESS_FIELD, $role);
        }

        return '(' . implode(' OR ', $conditions). ')';
    }

    private function includeAccessData(): bool {
        $permissionProvider = $this->getServiceLocator()->get(PermissionInterface::SERVICE_ID);

        return $permissionProvider instanceof ReverseRightLookupInterface;
    }
}
