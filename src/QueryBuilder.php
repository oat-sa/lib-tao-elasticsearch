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
    public static function create(): self
    {
        return new self();
    }

    public function getSearchParams(string $queryString, string $type, int $start, int $count, string $order, string $dir): array
    {
        $parts = explode(' ', htmlspecialchars_decode($queryString));

        foreach ($parts as $key => $part) {
            $matches = [];
            $part = $this->updateIfUri($part);
            if (preg_match('/^([^a-z_]*)([a-z_]+):(.*)/', $part, $matches) === 1) {
                [$fullstring, $prefix, $fieldname, $value] = $matches;
                $value = $this->updateIfUri($value);
                if ($fieldname) {
                    $parts[$key] = $prefix . $fieldname . ':' . str_replace(':', '\\:', $value);
                }
            }
        }
        $queryString = implode(' ', $parts);
        $queryString = (strlen($queryString) == 0 ? '' : '(' . $queryString . ') AND ')
            . 'type:' . str_replace(':', '\\:', '"' . $type . '"');

        $query = [
            'query' => [
                'query_string' =>
                    [
                        "default_operator" => "AND",
                        "query" => $queryString
                    ]
            ],
            'sort' => [$order => ['order' => $dir]]
        ];

        $params = [
            "index" => implode(',', IndexerInterface::AVAILABLE_INDEXES), //TODO we need to specificy only one index during implementation of task (TAO-10248)
            "size" => $count,
            "from" => $start,
            "client" => ["ignore" => 404],
            "body" => json_encode($query)
        ];

        return $params;
    }

    protected function updateIfUri(string $query): string
    {
        if (\common_Utils::isUri($query)) {
            $query = '"' . $query . '"';
        }
        return $query;
    }
}
