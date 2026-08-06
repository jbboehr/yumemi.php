<?php

/**
 * Authored Yumemi additions to the generated UDUNITS2 catalog.
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception
 *
 * @see https://www.w3.org/TR/css-values-4/#absolute-lengths
 * @see https://learn.microsoft.com/en-us/openspecs/office_standards/ms-odrawxml/f1ca887b-11d5-4cf6-acb1-acc0b4fb5dca
 */

return [
    'pixel' => [
        'type' => 'base',
        'name' => 'pixel',
        'definition' => 'one addressable sample in a raster image',
        'dimension' => 'image_sample',
    ],
    'pixels' => [
        'type' => 'alias',
        'name' => 'pixels',
        'def' => 'pixel',
        'aliasKind' => 'generated_plural',
    ],
    'css_pixel' => [
        'type' => 'unit',
        'name' => 'css_pixel',
        'definition' => 'CSS reference pixel, exactly 1/96 international inch',
        'def' => 'inch / 96',
    ],
    'css_pixels' => [
        'type' => 'alias',
        'name' => 'css_pixels',
        'def' => 'css_pixel',
        'aliasKind' => 'generated_plural',
    ],
    'typographic_point' => [
        'type' => 'unit',
        'name' => 'typographic_point',
        'definition' => 'modern desktop-publishing point, exactly 1/72 international inch',
        'def' => 'big_point',
    ],
    'typographic_points' => [
        'type' => 'alias',
        'name' => 'typographic_points',
        'def' => 'typographic_point',
        'aliasKind' => 'generated_plural',
    ],
    'twip' => [
        'type' => 'unit',
        'name' => 'twip',
        'definition' => 'twentieth of a modern typographic point',
        'def' => 'typographic_point / 20',
    ],
    'twips' => [
        'type' => 'alias',
        'name' => 'twips',
        'def' => 'twip',
        'aliasKind' => 'generated_plural',
    ],
    'english_metric_unit' => [
        'type' => 'unit',
        'name' => 'english_metric_unit',
        'definition' => 'Office Open XML English Metric Unit, exactly 1/914400 international inch',
        'def' => 'inch / 914400',
    ],
    'english_metric_units' => [
        'type' => 'alias',
        'name' => 'english_metric_units',
        'def' => 'english_metric_unit',
        'aliasKind' => 'generated_plural',
    ],
    'EMU' => [
        'type' => 'alias',
        'name' => 'EMU',
        'def' => 'english_metric_unit',
        'aliasKind' => 'symbol',
    ],
];
