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

use Iterator;
use oat\tao\model\search\index\IndexDocument;
use oat\tao\model\TaoOntology;

/**
 * Interface IndexerInterface
 */
interface IndexerInterface
{
    public const ITEMS_INDEX = 'items';
    public const TESTS_INDEX = 'tests';
    public const TEST_TAKERS_INDEX = 'test-takers';
    public const DELIVERIES_INDEX = 'deliveries';
    public const GROUPS_INDEX = 'groups';
    public const UNCLASSIFIEDS_DOCUMENTS_INDEX = 'unclassifieds';
    public const AVAILABLE_INDEXES = [
        TaoOntology::CLASS_URI_ITEM => self::ITEMS_INDEX,
        TaoOntology::CLASS_URI_TEST => self::TESTS_INDEX,
        TaoOntology::CLASS_URI_SUBJECT => self::TEST_TAKERS_INDEX,
        TaoOntology::CLASS_URI_DELIVERY => self::DELIVERIES_INDEX,
        TaoOntology::CLASS_URI_GROUP => self::GROUPS_INDEX
    ];

    /**
     * Returns name of current using index
     *
     * @throws \Throwable
     *
     * @return string
     */
    public function getIndexNameByDocument(IndexDocument $document): string;

    /**
     * Builds index for a given resources
     *
     * @param Iterator $resources
     *
     * @return int
     */
    public function buildIndex(Iterator $resources): int;

    /**
     * Deletes data from index for a resources with a given id
     *
     * @param $id
     *
     * @return bool
     */
    public function deleteDocument($id): bool;

    /**
     * Searches and returns resources that matches a given list of identifiers
     *
     * @param $ids array List of identifiers
     *
     * @return mixed
     */
    public function searchResourceByIds($ids);
}
