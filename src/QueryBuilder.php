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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch;

class QueryBuilder
{
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
        'Checkbox',
        'RadioBox',
    ];

    public static function create(): self
    {
        return new self();
    }

    public function getSearchParams(string $queryString, string $type, int $start, int $count, string $order, string $dir): array
    {
        $qs = htmlspecialchars_decode($queryString);
        $blocks = explode('AND', $qs);
        $query = [];

        foreach ($blocks as $block) {
            preg_match('/((?P<field>.*):)?(?P<term>.*)/', $block,$matches);
            $field = strtolower(trim($matches['field']));

            $term = trim($matches['term']);
            $term = $this->updateIfUri($term);

            if (empty($field)) {
                $query[] = sprintf('(%s)', $term);
            } elseif ($this->isStandardField($field)) {
                $query[] = sprintf('(%s:%s)', $field, $term);
            } else {
                $query[] = $this->buildCustomConditions($field, $term);
            }
        }

        $query[] = 'type:' . str_replace(':', '\\:', '"' . $type . '"');

        $query = [
            'query' => [
                'query_string' =>
                    [
                        "default_operator" => "AND",
                        "query" => $queryString = implode(' AND ', $query)
                    ]
            ],
            'sort' => [$order => ['order' => $dir]]
        ];

        $params = [
            "index" => $this->getIndexByType($type),
            "size" => $count,
            "from" => $start,
            "client" => ["ignore" => 404],
            "body" => json_encode($query)
        ];

        return $params;
    }

    private function isStandardField(string $field): bool
    {
        return in_array(strtolower($field), self::STANDARD_FIELDS);
    }

    private function getIndexByType(string $type): string
    {
        return IndexerInterface::AVAILABLE_INDEXES[$type];
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
            $conditions[] = sprintf('%s_%s:%s', $customField, $fieldName, $term);
        }

        return '(' . implode(' OR ', $conditions). ')';
    }
}
