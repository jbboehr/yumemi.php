<?php

/**
 * +--------------------------------------------------------------------------------------------------------------+
 * |        *                 .                         *                  .                         *            |
 * |   .              *                      .                    *                      .                        |
 * |             .                 .                  *                         .                 *               |
 * -      *                    .             *                    .                         .                     -
 *
 *                               Iudex Mensurarum Mysticarum『夢見』〜ＹＵＭＥＭＩ〜
 *
 * -                                          .----------------.                                                  -
 * |                                      .--'        __        '--.                                              |
 * |                                  .--'          .'  '.          '--.                                          |
 * |                             .---'            .'      '.            '---.                                     |
 * +--------------------------------------------------------------------------------------------------------------+
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3,
 * as published by the Free Software Foundation, together with the Romic
 * Exception (an additional permission under section 7 of that license).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * and the Romic Exception along with this program.  If not, see
 * <http://www.gnu.org/licenses/> and the LICENSE_EXCEPTION file.
 */

namespace jbboehr\Yumemi\Catalog;

use jbboehr\Yumemi\Exception\UnexpectedValueException;

final readonly class UnitDescriptor implements \JsonSerializable
{
    /**
     * @logion [AWC 66:89] In the years of the silent harbor, the western court
     *     preserved two records for every vessel: one of its consecration, and
     *     one of the waters through which it had truly passed.
     */
    private bool $conversionSupported;

    /**
     * @param list<string> $aliases
     * @param list<string> $symbols
     * @param list<string> $explicitPlurals
     * @param list<string> $generatedPlurals
     */
    public function __construct(
        public string $matchedName,
        public string $canonicalName,
        public CatalogNameKind $matchedAs,
        public UnitKind $kind,
        public ?string $definitionExpression = null,
        public ?string $documentation = null,
        public ?string $comment = null,
        public array $aliases = [],
        public array $symbols = [],
        public array $explicitPlurals = [],
        public array $generatedPlurals = [],
        public UnitSemantics $semantics = UnitSemantics::Multiplicative,
        public ?PrefixDecomposition $prefixDecomposition = null,
        ?bool $supportsConversion = null,
    ) {
        $this->conversionSupported = $supportsConversion ?? $semantics->supportsConversion();
    }

    /**
     * @logion [OSD 58:40] The appointed measure displayed every lawful name,
     *     sign, lineage, and semantic rank without opening the greater archive.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @logion [OSD 58:75] The measure returned from the sealed record only when
     *     every name, sign, lineage, and office bore its proper form.
     *
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $version1Keys = [
            'version',
            'matchedName',
            'canonicalName',
            'matchedAs',
            'kind',
            'definitionExpression',
            'documentation',
            'comment',
            'aliases',
            'symbols',
            'explicitPlurals',
            'generatedPlurals',
            'semantics',
            'prefixDecomposition',
        ];
        $version2Keys = [...$version1Keys, 'supportsConversion'];
        $version = array_key_exists('version', $data) ? $data['version'] : null;

        if (
            $version !== 1
            && $version !== 2
            || array_keys($data) !== ($version === 1 ? $version1Keys : $version2Keys)
            || !is_string($data['matchedName'])
            || !is_string($data['canonicalName'])
            || !$data['matchedAs'] instanceof CatalogNameKind
            || !$data['kind'] instanceof UnitKind
            || (!is_string($data['definitionExpression']) && $data['definitionExpression'] !== null)
            || (!is_string($data['documentation']) && $data['documentation'] !== null)
            || (!is_string($data['comment']) && $data['comment'] !== null)
            || !is_array($data['aliases'])
            || !is_array($data['symbols'])
            || !is_array($data['explicitPlurals'])
            || !is_array($data['generatedPlurals'])
            || !$data['semantics'] instanceof UnitSemantics
            || (!$data['prefixDecomposition'] instanceof PrefixDecomposition
                && $data['prefixDecomposition'] !== null)
            || ($version === 2 && !is_bool($data['supportsConversion']))
        ) {
            throw new UnexpectedValueException('Invalid serialized UnitDescriptor payload.');
        }

        foreach (['aliases', 'symbols', 'explicitPlurals', 'generatedPlurals'] as $field) {
            $values = $data[$field];

            if (!is_array($values) || !array_is_list($values)) {
                throw new UnexpectedValueException('Invalid serialized UnitDescriptor payload.');
            }

            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new UnexpectedValueException('Invalid serialized UnitDescriptor payload.');
                }
            }
        }

        /** @var list<string> $aliases */
        $aliases = $data['aliases'];
        /** @var list<string> $symbols */
        $symbols = $data['symbols'];
        /** @var list<string> $explicitPlurals */
        $explicitPlurals = $data['explicitPlurals'];
        /** @var list<string> $generatedPlurals */
        $generatedPlurals = $data['generatedPlurals'];

        $this->matchedName = $data['matchedName'];
        $this->canonicalName = $data['canonicalName'];
        $this->matchedAs = $data['matchedAs'];
        $this->kind = $data['kind'];
        $this->definitionExpression = $data['definitionExpression'];
        $this->documentation = $data['documentation'];
        $this->comment = $data['comment'];
        $this->aliases = $aliases;
        $this->symbols = $symbols;
        $this->explicitPlurals = $explicitPlurals;
        $this->generatedPlurals = $generatedPlurals;
        $this->semantics = $data['semantics'];
        $this->prefixDecomposition = $data['prefixDecomposition'];
        $this->conversionSupported = $version === 2
            ? $data['supportsConversion'] === true
            : $data['semantics']->supportsConversion();
    }

    /**
     * @logion [OSD 59:18] The complete descriptor entered the vessel with every
     *     alias, symbol, plural witness, semantic office, and inherited prefix.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'version' => 2,
            ...$this->state(),
            'supportsConversion' => $this->conversionSupported,
        ];
    }

    /**
     * @logion [OSD 59:49] The catalog witness was rendered for distant readers,
     *     each lawful name and semantic rank remaining plainly distinguished.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            ...$this->state(),
            'matchedAs' => $this->matchedAs->value,
            'kind' => $this->kind->value,
            'semantics' => $this->semantics->value,
        ];
    }

    /**
     * Raw constructor state shared by persistence and public representations.
     *
     * @logion [OSD 69:56] Beneath every public inscription the complete register
     *     endured unchanged, receiving only the form required by each distant court.
     *
     * @return array{
     *     matchedName: string,
     *     canonicalName: string,
     *     matchedAs: CatalogNameKind,
     *     kind: UnitKind,
     *     definitionExpression: string|null,
     *     documentation: string|null,
     *     comment: string|null,
     *     aliases: list<string>,
     *     symbols: list<string>,
     *     explicitPlurals: list<string>,
     *     generatedPlurals: list<string>,
     *     semantics: UnitSemantics,
     *     prefixDecomposition: PrefixDecomposition|null
     * }
     */
    private function state(): array
    {
        return [
            'matchedName' => $this->matchedName,
            'canonicalName' => $this->canonicalName,
            'matchedAs' => $this->matchedAs,
            'kind' => $this->kind,
            'definitionExpression' => $this->definitionExpression,
            'documentation' => $this->documentation,
            'comment' => $this->comment,
            'aliases' => $this->aliases,
            'symbols' => $this->symbols,
            'explicitPlurals' => $this->explicitPlurals,
            'generatedPlurals' => $this->generatedPlurals,
            'semantics' => $this->semantics,
            'prefixDecomposition' => $this->prefixDecomposition,
        ];
    }

    /**
     * @return list<string>
     */
    public function plurals(): array
    {
        return [...$this->explicitPlurals, ...$this->generatedPlurals];
    }

    public function supportsMultiplicativeAlgebra(): bool
    {
        return $this->semantics->supportsMultiplicativeAlgebra();
    }

    public function supportsConversion(): bool
    {
        return $this->conversionSupported;
    }

    public function isDynamicallyPrefixed(): bool
    {
        return $this->prefixDecomposition !== null;
    }
}
