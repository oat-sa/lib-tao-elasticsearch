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
        'delivery',
        'test_taker',
        'test_taker_name',
        'delivery_execution',
        'custom_tag',
        'context_id',
        'context_label',
        'resource_link_id'
    ];

    private const CUSTOM_FIELDS = [
        'HTMLArea',
        'TextArea',
        'TextBox',
        'ComboBox',
        'CheckBox',
        'RadioBox',
        'SearchTextBox',
    ];

    public static function create(): self
    {
        return new self();
    }

    public function getSearchParams(string $queryString, string $type, int $start, int $count, string $order, string $dir): array
    {
        $queryString = str_replace(['"', '\''], '', $queryString);
        $queryString = htmlspecialchars_decode($queryString);
        $blocks = preg_split( '/( AND )/i', $queryString);
        $conditions = [];

        foreach ($blocks as $block) {
            /** @var QueryBlock $queryBlock */
            $queryBlock = $this->parseBlock($block);

            if (empty($queryBlock->getField())) {
                $conditions[] = sprintf('("%s")', $queryBlock->getTerm());
            } elseif ($this->isStandardField($queryBlock->getField())) {
                $conditions[] = sprintf('(%s:"%s")', $queryBlock->getField(), $queryBlock->getTerm());
            } else {
                $conditions[] = $this->buildCustomConditions($queryBlock);
            }
        }

        if ($this->includeAccessData()) {
            $conditions[] = $this->buildAccessConditions();
        }

        $query = [
            'query' => [
                'query_string' =>
                    [
                        'default_operator' => 'AND',
                        'query' => implode(' AND ', $conditions)
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

    private function buildCustomConditions(QueryBlock $queryBlock): string
    {
        $conditions = [];

        $field_slug = tao_helpers_Slug::create($queryBlock->getField());

        foreach (self::CUSTOM_FIELDS as $customField) {
            $conditions[] = sprintf('%s_%s:"%s"', $customField, $field_slug, $queryBlock->getTerm());
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

    private function includeAccessData(): bool
    {
        $permissionProvider = $this->getServiceLocator()->get(PermissionInterface::SERVICE_ID);

        return $permissionProvider instanceof ReverseRightLookupInterface;
    }

    private function parseBlock(string $block): QueryBlock
    {
        if (\common_Utils::isUri($block)) {
            return new QueryBlock(null, $block);
        }

        preg_match('/((?P<field>[^:]*):)?(?P<term>.*)/', $block,$matches);
        $field = strtolower(trim($matches['field']));
        $term = trim($matches['term']);

        return new QueryBlock($field, $term);
    }
}
