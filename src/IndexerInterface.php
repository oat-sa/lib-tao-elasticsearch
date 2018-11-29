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

/**
 * Interface IndexerInterface
 */
interface IndexerInterface
{
    /**
     * Returns name of current using index
     * @return mixed
     */
    public function getIndex();

    /**
     * Returns name if current using type
     * @return mixed
     */
    public function getType();

    /**
     * Builds index for a given resources
     * @param Iterator $resources
     * @return mixed
     */
    public function buildIndex(Iterator $resources);

    /**
     * Deletes data from index for a resources with a given id
     * @param $id
     * @return mixed
     */
    public function deleteIndex($id);

    /**
     * Searches and returns resources that matches a given list of identifiers
     * @param $ids array List of identifiers
     * @param $type string Type of resources
     * @return mixed
     */
    public function searchResourceByIds($ids, $type);
}
