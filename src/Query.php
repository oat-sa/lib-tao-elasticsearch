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
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch;

class Query
{
    /** @var string */
    private $index;

    /** @var int */
    private $limit;

    /** @var int */
    private $offset;

    /** @var array */
    private $conditions;

    public function __construct(string $index)
    {
        $this->index = $index;
        $this->offset = 0;
        $this->limit = 1000;
        $this->conditions = [];
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function getQueryString(): string
    {
        return implode('AND ', $this->conditions);
    }

    public function addCondition(string $condition): self
    {
        $this->conditions[] = $condition;
        
        return $this;
    }
}
