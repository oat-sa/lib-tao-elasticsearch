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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch\internal;

use oat\tao\model\search\index\IndexDocument;

/**
 * @internal To be used only by lib-tao-elasticsearch
 */
trait BatchLog
{
    private function logCompletion(int $count, int $visited, int $skipped, int $exceptions): void
    {
        if (($count != $visited) || ($exceptions > 0) || ($skipped > 0)) {
            $this->logger->warning(
                sprintf(
                    "%d / %d items were processed (%d skipped, %d exceptions)",
                    $count,
                    $visited,
                    $skipped,
                    $exceptions
                )
            );
        } else {
            $this->logger->debug(
                sprintf(
                    "Processed %d items (no exceptions, no skipped items)",
                    $count,
                    $skipped
                )
            );
        }
    }

    private function logMappings(IndexDocument $document): void
    {
        foreach (self::AVAILABLE_INDEXES as $documentType => $indexName) {
            $this->logger->warning(
                sprintf(
                    'documentId: "%s" Index mappings: type="%s" index="%s"',
                    $document->getId(),
                    $documentType,
                    $indexName)
            );
        }
    }

    private function getTypesString(IndexDocument $document): ?string
    {
        return var_export($document->getBody()['type'] ?? null, true);
    }

    private function debug(?IndexDocument $document, string $message, ...$args): void
    {
        $this->logger->debug(
            ($document ? sprintf('[documentId: "%s"] ', $document->getId()) : '').
            vsprintf($message, $args)
        );
    }

    private function info(?IndexDocument $document, string $message, ...$args): void
    {
        $this->logger->info(
            ($document ? sprintf('[documentId: "%s"] ', $document->getId()) : '').
            vsprintf($message, $args)
        );
    }

    private function warn(?IndexDocument $document, string $message, ...$args): void
    {
        $this->logger->warning(
            ($document ? sprintf('[documentId: "%s"] ', $document->getId()) : '').
            vsprintf($message, $args)
        );
    }

    private function logErrorsFromResponse(?IndexDocument $document, $clientResponse): void
    {
        if (!isset($clientResponse['errors']) || $clientResponse['errors']) {
            $this->logger->warning(
                $document,
                'Unexpected error response from client: %s',
                json_encode($clientResponse)
            );
        }
    }
}
