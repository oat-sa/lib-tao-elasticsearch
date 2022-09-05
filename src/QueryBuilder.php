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

use oat\generis\model\data\permission\PermissionInterface;
use oat\generis\model\data\permission\ReverseRightLookupInterface;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\tao\elasticsearch\Specification\UseAclSpecification;
use tao_helpers_Uri;
use common_Utils;

class QueryBuilder extends ConfigurableService
{
    private const READ_ACCESS_FIELD = 'read_access';

    public const STRUCTURE_TO_INDEX_MAP = [
        'results' => IndexerInterface::DELIVERY_RESULTS_INDEX,
        'delivery' => IndexerInterface::DELIVERIES_INDEX,
        'groups' => IndexerInterface::GROUPS_INDEX,
        'items' => IndexerInterface::ITEMS_INDEX,
        'tests' => IndexerInterface::TESTS_INDEX,
        'TestTaker' => IndexerInterface::TEST_TAKERS_INDEX,
        'taoMediaManager' => IndexerInterface::ASSETS_INDEX,
        'property-list' => IndexerInterface::PROPERTY_LIST,
    ];

    private const STANDARD_FIELDS = [
        'class',
        'parent_classes',
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
        'SearchDropdown',
    ];

    /** @var UseAclSpecification */
    private $useAclSpecification;

    public function withUseAclSpecification(UseAclSpecification $useAclSpecification): self
    {
        $this->useAclSpecification = $useAclSpecification;

        return $this;
    }

    private const QUERY_STRING_REPLACEMENTS = [
        '"' => '',
        '\'' => '',
        '\\' => '\\\\'
    ];

    public static function create(): self
    {
        return new self();
    }

    public function getSearchParams(string $queryString, string $type, int $start, int $count, string $order, string $dir): array
    {
        $queryString = str_replace(
            array_keys(self::QUERY_STRING_REPLACEMENTS),
            array_values(self::QUERY_STRING_REPLACEMENTS),
            $queryString
        );

        $queryString = htmlspecialchars_decode($queryString);
        $blocks = preg_split( '/( AND )/i', $queryString);
        $index = $this->getIndexByType($type);
        $conditions = $this->buildConditions($index, $blocks);

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
            'index' => $index,
            'size' => $count,
            'from' => $start,
            'client' => ['ignore' => 404],
            'body' => json_encode($query)
        ];

        $this->getLogger()->debug('Input Query: ' . $queryString);
        $this->getLogger()->debug('Elastic Query: ' . json_encode($params));

        return $params;
    }

    private function buildConditions(string $index, array $blocks): array
    {
        $conditions = $this->buildConditionsByType($index, $blocks);

        if ($this->includeAccessData($index)) {
            $conditions[] = $this->buildAccessConditions();
        }

        return $conditions;
    }

    /**
     * Use only simple input
     * @param string[] $blocks
     * @return string[]
     */
    private function getResultsConditions(array $blocks): array
    {
        $conditions = [];

        foreach ($blocks as $block) {
            $block = $this->parseBlock($block);
            if ($block->getField() === 'parent_classes') {
                continue;
            }

            $conditions[] = sprintf('("%s")', $block->getTerm());
        }

        return $conditions;
    }

    private function buildConditionsByType(string $type, array $blocks): array
    {
        if ($type === IndexerInterface::DELIVERY_RESULTS_INDEX) {
            return $this->getResultsConditions($blocks);
        }

        return $this->getResourceConditions($blocks);
    }

    private function getResourceConditions(array $blocks): array
    {
        $conditions = [];
        foreach ($blocks as $block) {
            $queryBlock = $this->parseBlock($block);

            if (empty($queryBlock->getField())) {
                $conditions[] = sprintf('("%s")', $queryBlock->getTerm());
            } elseif ($this->isStandardField($queryBlock->getField())) {
                $conditions[] = sprintf('(%s:"%s")', $queryBlock->getField(), $queryBlock->getTerm());
            } else {
                $conditions[] = $this->buildCustomConditions($queryBlock);
            }
        }

        return $conditions;
    }

    private function isStandardField(string $field): bool
    {
        return in_array(strtolower($field), self::STANDARD_FIELDS);
    }

    private function getIndexByType(string $type): string
    {
        return self::STRUCTURE_TO_INDEX_MAP[$type] ?? IndexerInterface::UNCLASSIFIEDS_DOCUMENTS_INDEX;
    }

    private function buildCustomConditions(QueryBlock $queryBlock): string
    {
        $conditions = [];

        foreach (self::CUSTOM_FIELDS as $customField) {
            $conditions[] = sprintf('%s_%s:"%s"', $customField, $queryBlock->getField(), $queryBlock->getTerm());
        }

        return '(' . implode(' OR ', $conditions). ')';
    }

    private function buildAccessConditions(): string
    {
        $conditions = [];

        $currentUser = $this->getSessionService()->getCurrentUser();

        $conditions[] = $currentUser->getIdentifier();
        foreach ($currentUser->getRoles() as $role) {
            $conditions[] = $role;
        }

        return sprintf(
            '(%s:("%s"))',
            self::READ_ACCESS_FIELD,
            implode('" OR "', $conditions)
        );
    }

    private function includeAccessData(string $index): bool
    {
        return $this->getUseAclSpecification()->isSatisfiedBy(
            $index,
            $this->getPermissionProvider(),
            $this->getSessionService()->getCurrentUser()
        );
    }

    private function parseBlock(string $block): QueryBlock
    {
        if (common_Utils::isUri($block)) {
            return new QueryBlock(null, $block);
        }

        preg_match('/((?P<field>[^:]*):)?(?P<term>.*)/', $block,$matches);

        $field = trim($matches['field']);

        if (!$this->isUri($field)) {
            $field = strtolower($field);
        }

        return new QueryBlock($field, trim($matches['term']));
    }

    private function getUseAclSpecification(): UseAclSpecification
    {
        if (!isset($this->useAclSpecification)) {
            $this->useAclSpecification = new UseAclSpecification();
        }

        return $this->useAclSpecification;
    }

    private function isUri(string $term): bool
    {
        return common_Utils::isUri(tao_helpers_Uri::decode($term));
    }

    private function getPermissionProvider(): PermissionInterface
    {
        return $this->getServiceManager()->getContainer()->get(PermissionInterface::SERVICE_ID);
    }

    private function getSessionService(): SessionService
    {
        return $this->getServiceManager()->getContainer()->get(SessionService::SERVICE_ID);
    }
}
