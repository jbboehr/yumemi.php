<?php

/**
 * Copyright 2008, 2009 University Corporation for Atmospheric Research
 *
 * This file is part of the UDUNITS-2 package. See the file COPYRIGHT in the top-level source-directory of the
 * package for copying and redistribution conditions.
 */

return [
    'units' => [
        'm' => [
            'type' => 'alias',
            'name' => 'm',
            'def' => 'meter',
            'aliasKind' => 'symbol'
        ],
        'metre' => [
            'type' => 'alias',
            'name' => 'metre',
            'def' => 'meter',
            'aliasKind' => 'alias'
        ],
        'meter' => [
            'type' => 'base',
            'name' => 'meter',
            'definition' => 'The meter is the length of the path travelled by light in vacuum during a time interval of 1/299 792 458 of a second.'
        ],
        'kg' => [
            'type' => 'alias',
            'name' => 'kg',
            'def' => 'kilogram',
            'aliasKind' => 'symbol'
        ],
        'kilogram' => [
            'type' => 'base',
            'name' => 'kilogram',
            'definition' => 'The kilogram is the unit of mass; it is equal to the mass of the international prototype of the kilogram.'
        ],
        's' => [
            'type' => 'alias',
            'name' => 's',
            'def' => 'second',
            'aliasKind' => 'symbol'
        ],
        'second' => [
            'type' => 'base',
            'name' => 'second',
            'definition' => 'The second is the duration of 9 192 631 770 periods of the radiation corresponding to the transition between the two hyperfine levels of the ground state of the cesium 133 atom.'
        ],
        'A' => [
            'type' => 'alias',
            'name' => 'A',
            'def' => 'ampere',
            'aliasKind' => 'symbol'
        ],
        'ampere' => [
            'type' => 'base',
            'name' => 'ampere',
            'definition' => 'The ampere is that constant current which, if maintained in two straight parallel conductors of infinite length, of negligible circular cross-section, and placed 1 meter apart in vacuum, would produce between these conductors a force equal to 2e-7 newton per meter of length.'
        ],
        'K' => [
            'type' => 'alias',
            'name' => 'K',
            'def' => 'kelvin',
            'aliasKind' => 'symbol'
        ],
        'kelvin' => [
            'type' => 'base',
            'name' => 'kelvin',
            'definition' => 'The kelvin, unit of thermodynamic temperature, is the fraction 1/273.16 of the thermodynamic temperature of the triple point of water.'
        ],
        'mol' => [
            'type' => 'alias',
            'name' => 'mol',
            'def' => 'mole',
            'aliasKind' => 'symbol'
        ],
        'mole' => [
            'type' => 'base',
            'name' => 'mole',
            'definition' => 'The mole is the amount of substance of a system which contains as many elementary entities as there are atoms in 0.012 kilogram of carbon 12. When the mole is used, the elementary entities must be specified and may be atoms, molecules, ions, electrons, other particles, or specified groups of such particles.'
        ],
        'cd' => [
            'type' => 'alias',
            'name' => 'cd',
            'def' => 'candela',
            'aliasKind' => 'symbol'
        ],
        'candela' => [
            'type' => 'base',
            'name' => 'candela',
            'definition' => 'The candela is the luminous intensity, in a given direction, of a source that emits monochromatic radiation of frequency 540e12 hertz and that has a radiant intensity in that direction of 1/683 watt per steradian.'
        ],
        'rad' => [
            'type' => 'alias',
            'name' => 'rad',
            'def' => 'radian',
            'aliasKind' => 'symbol'
        ],
        'radian' => [
            'type' => 'dimensionless',
            'name' => 'radian',
            'definition' => 'standard unit of angular measure, an angle that creates an arc in a circle equal in length to that circle\'s radius (roughly 57.3 degrees); as a ratio of two lengths, it has no dimension',
            'comment' => 'SI derived unit'
        ],
        'sr' => [
            'type' => 'alias',
            'name' => 'sr',
            'def' => 'steradian',
            'aliasKind' => 'symbol'
        ],
        'steradian' => [
            'type' => 'unit',
            'name' => 'steradian',
            'definition' => 'standard unit of solid angle measure, it is the solid angle which cuts out an area on a sphere that is the square of the sphere\'s radius; as a ratio of two areas, it has no dimension',
            'def' => 'rad^2',
            'comment' => 'SI derived unit'
        ],
        'Hz' => [
            'type' => 'alias',
            'name' => 'Hz',
            'def' => 'hertz',
            'aliasKind' => 'symbol'
        ],
        'hertz' => [
            'type' => 'unit',
            'name' => 'hertz',
            'definition' => 'unit of frequency meaning one cycle per second',
            'def' => '1/s',
            'comment' => 'SI derived unit'
        ],
        'g' => [
            'type' => 'alias',
            'name' => 'g',
            'def' => 'gram',
            'aliasKind' => 'symbol'
        ],
        'gram' => [
            'type' => 'unit',
            'name' => 'gram',
            'definition' => 'unit of mass equal to one thousandth of a kilogram',
            'def' => '1e-3 kg',
            'comment' => 'SI derived unit'
        ],
        'N' => [
            'type' => 'alias',
            'name' => 'N',
            'def' => 'newton',
            'aliasKind' => 'symbol'
        ],
        'newton' => [
            'type' => 'unit',
            'name' => 'newton',
            'definition' => 'unit of force; the amount needed to accelerate 1 kilogram of mass at the rate of 1 metre per second squared',
            'def' => 'm.kg/s^2',
            'comment' => 'SI derived unit'
        ],
        'Pa' => [
            'type' => 'alias',
            'name' => 'Pa',
            'def' => 'pascal',
            'aliasKind' => 'symbol'
        ],
        'pascal' => [
            'type' => 'unit',
            'name' => 'pascal',
            'definition' => 'unit of pressure (force per unit area) equal to one newton per square meter',
            'def' => 'N/m^2',
            'comment' => 'SI derived unit'
        ],
        'J' => [
            'type' => 'alias',
            'name' => 'J',
            'def' => 'joule',
            'aliasKind' => 'symbol'
        ],
        'joule' => [
            'type' => 'unit',
            'name' => 'joule',
            'definition' => 'standard unit of work or energy, equal to the work done by a force of one newton acting along a distance of one meter',
            'def' => 'N.m',
            'comment' => 'SI derived unit'
        ],
        'W' => [
            'type' => 'alias',
            'name' => 'W',
            'def' => 'watt',
            'aliasKind' => 'symbol'
        ],
        'watt' => [
            'type' => 'unit',
            'name' => 'watt',
            'definition' => 'standard unit of power, equivalent to one joule per second, and equal to one ampere flowing across a potential difference of one volt',
            'def' => 'J/s',
            'comment' => 'SI derived unit'
        ],
        'C' => [
            'type' => 'alias',
            'name' => 'C',
            'def' => 'coulomb',
            'aliasKind' => 'symbol'
        ],
        'coulomb' => [
            'type' => 'unit',
            'name' => 'coulomb',
            'definition' => 'standard unit of electric charge, the quantity of electricity transported in one second by a current of one ampere',
            'def' => 's.A',
            'comment' => 'SI derived unit'
        ],
        'V' => [
            'type' => 'alias',
            'name' => 'V',
            'def' => 'volt',
            'aliasKind' => 'symbol'
        ],
        'volt' => [
            'type' => 'unit',
            'name' => 'volt',
            'definition' => 'standard unit of electric potential difference (and electromotive force); the difference of potential between two points of a conductor carrying a constant current of one ampere and dissipating one watt of power',
            'def' => 'W/A',
            'comment' => 'SI derived unit'
        ],
        'F' => [
            'type' => 'alias',
            'name' => 'F',
            'def' => 'farad',
            'aliasKind' => 'symbol'
        ],
        'farad' => [
            'type' => 'unit',
            'name' => 'farad',
            'definition' => 'standard unit of capacitance; the amount which, when a capacitor is charged to a potential difference of one volt, carries a charge of one coulomb',
            'def' => 'C/V',
            'comment' => 'SI derived unit'
        ],
        'Ω' => [
            'type' => 'alias',
            'name' => 'Ω',
            'def' => 'ohm',
            'aliasKind' => 'symbol'
        ],
        'Ω' => [
            'type' => 'alias',
            'name' => 'Ω',
            'def' => 'ohm',
            'aliasKind' => 'symbol'
        ],
        'ohm' => [
            'type' => 'unit',
            'name' => 'ohm',
            'definition' => 'standard unit of electrical resistance; the resistance between two points of a conductor when a constant potential difference of 1.0 volt, applied to these points, produces in the conductor a current of 1.0 ampere',
            'def' => 'V/A',
            'comment' => 'SI derived unit'
        ],
        'S' => [
            'type' => 'alias',
            'name' => 'S',
            'def' => 'siemens',
            'aliasKind' => 'symbol'
        ],
        'siemens' => [
            'type' => 'unit',
            'name' => 'siemens',
            'definition' => 'unit of electric conductance; the reciprocal of one ohm',
            'def' => 'A/V',
            'comment' => 'SI derived unit'
        ],
        'Wb' => [
            'type' => 'alias',
            'name' => 'Wb',
            'def' => 'weber',
            'aliasKind' => 'symbol'
        ],
        'weber' => [
            'type' => 'unit',
            'name' => 'weber',
            'definition' => 'unit of magnetic flux (product of the average magnetic field times the perpendicular area that it penetrates), expressed as volt-seconds',
            'def' => 'V.s',
            'comment' => 'SI derived unit'
        ],
        'T' => [
            'type' => 'alias',
            'name' => 'T',
            'def' => 'tesla',
            'aliasKind' => 'symbol'
        ],
        'tesla' => [
            'type' => 'unit',
            'name' => 'tesla',
            'definition' => 'unit of flux density, equal to one weber per square meter',
            'def' => 'Wb/m^2',
            'comment' => 'SI derived unit'
        ],
        'H' => [
            'type' => 'alias',
            'name' => 'H',
            'def' => 'henry',
            'aliasKind' => 'symbol'
        ],
        'henry' => [
            'type' => 'unit',
            'name' => 'henry',
            'definition' => 'unit of inductance; where a circuit\'s current changes at a constant rate of 1 ampere per second, results in a generation of 1 V of potential difference',
            'def' => 'Wb/A',
            'comment' => 'SI derived unit'
        ],
        '°C' => [
            'type' => 'alias',
            'name' => '°C',
            'def' => 'degree_Celsius',
            'aliasKind' => 'symbol'
        ],
        'degree_Celsius' => [
            'type' => 'unit',
            'name' => 'degree_Celsius',
            'definition' => 'unit (and scale) of temperature, with same magnitude as the kelvin and a zero-point offset of 273.15',
            'plural' => 'degrees_Celsius',
            'def' => 'K @ 273.15',
            'comment' => 'SI derived unit'
        ],
        'degrees_Celsius' => [
            'type' => 'alias',
            'name' => 'degrees_Celsius',
            'def' => 'degree_Celsius',
            'aliasKind' => 'explicit_plural'
        ],
        'lm' => [
            'type' => 'alias',
            'name' => 'lm',
            'def' => 'lumen',
            'aliasKind' => 'symbol'
        ],
        'lumen' => [
            'type' => 'unit',
            'name' => 'lumen',
            'definition' => 'unit of luminous flux, a measure of the total "amount" of visible light emitted by a source; one candela-steradian',
            'def' => 'cd.sr',
            'comment' => 'SI derived unit'
        ],
        'lx' => [
            'type' => 'alias',
            'name' => 'lx',
            'def' => 'lux',
            'aliasKind' => 'symbol'
        ],
        'lux' => [
            'type' => 'unit',
            'name' => 'lux',
            'definition' => 'unit of illuminance and luminous emittance, measuring luminous flux per unit area, and used as a measure of intensity of light; equal to one lumen per square meter',
            'def' => 'lm/m^2',
            'comment' => 'SI derived unit'
        ],
        'kat' => [
            'type' => 'alias',
            'name' => 'kat',
            'def' => 'katal',
            'aliasKind' => 'symbol'
        ],
        'katal' => [
            'type' => 'unit',
            'name' => 'katal',
            'definition' => 'unit of catalytic activity (property of a catalyst, such as an enzyme); expresses the ability to break 1 mole of bonds per second under specified conditions',
            'def' => 'mol/s',
            'comment' => 'SI derived unit'
        ],
        'Bq' => [
            'type' => 'alias',
            'name' => 'Bq',
            'def' => 'becquerel',
            'aliasKind' => 'symbol'
        ],
        'becquerel' => [
            'type' => 'unit',
            'name' => 'becquerel',
            'definition' => 'unit of radioactivity; the activity of a quantity of material in which one nucleus decays per second (hence, equivalent to one unit per second)',
            'def' => '1/s',
            'comment' => 'SI derived unit with special names/symbols admitted for reasons of safeguarding human health'
        ],
        'Gy' => [
            'type' => 'alias',
            'name' => 'Gy',
            'def' => 'gray',
            'aliasKind' => 'symbol'
        ],
        'gray' => [
            'type' => 'unit',
            'name' => 'gray',
            'definition' => 'unit of ionizing radiation, a measure of the absorbed dose of radiation; absorption of one joule of radiation energy by one kilogram of matter',
            'def' => 'J/kg',
            'comment' => 'SI derived unit with special names/symbols admitted for reasons of safeguarding human health'
        ],
        'Sv' => [
            'type' => 'alias',
            'name' => 'Sv',
            'def' => 'sievert',
            'aliasKind' => 'symbol'
        ],
        'sievert' => [
            'type' => 'unit',
            'name' => 'sievert',
            'definition' => 'unit of ionizing radiation dose, measuring the health effect of low levels of ionizing radiation on the human body',
            'def' => 'J/kg',
            'comment' => 'SI derived unit with special names/symbols admitted for reasons of safeguarding human health'
        ],
        'min' => [
            'type' => 'alias',
            'name' => 'min',
            'def' => 'minute',
            'aliasKind' => 'symbol'
        ],
        'minute' => [
            'type' => 'unit',
            'name' => 'minute',
            'definition' => 'period of time equal to 60 seconds',
            'def' => '60 s'
        ],
        'h' => [
            'type' => 'alias',
            'name' => 'h',
            'def' => 'hour',
            'aliasKind' => 'symbol'
        ],
        'hr' => [
            'type' => 'alias',
            'name' => 'hr',
            'def' => 'hour',
            'aliasKind' => 'symbol'
        ],
        'hour' => [
            'type' => 'unit',
            'name' => 'hour',
            'definition' => 'period of time equal to 60 minutes',
            'def' => '60 min'
        ],
        'd' => [
            'type' => 'alias',
            'name' => 'd',
            'def' => 'day',
            'aliasKind' => 'symbol'
        ],
        'day' => [
            'type' => 'unit',
            'name' => 'day',
            'definition' => 'period of time equal to 24 hours',
            'def' => '24 h'
        ],
        'π' => [
            'type' => 'alias',
            'name' => 'π',
            'def' => 'pi',
            'aliasKind' => 'symbol'
        ],
        'pi' => [
            'type' => 'unit',
            'name' => 'pi',
            'definition' => 'mathematical constant equal to the ratio of a circle\'s circumference to its diameter',
            'def' => '3.141592653589793238462643383279',
            'comment' => 'This "unit" is useful in the definition of subsequent units.'
        ],
        '°' => [
            'type' => 'alias',
            'name' => '°',
            'def' => 'arc_degree',
            'aliasKind' => 'symbol'
        ],
        'angular_degree' => [
            'type' => 'alias',
            'name' => 'angular_degree',
            'def' => 'arc_degree',
            'aliasKind' => 'alias'
        ],
        'degree' => [
            'type' => 'alias',
            'name' => 'degree',
            'def' => 'arc_degree',
            'aliasKind' => 'alias'
        ],
        'arcdeg' => [
            'type' => 'alias',
            'name' => 'arcdeg',
            'def' => 'arc_degree',
            'aliasKind' => 'alias'
        ],
        'arc_degree' => [
            'type' => 'unit',
            'name' => 'arc_degree',
            'definition' => 'measurement of a plane angle representing 1/360 of a full rotation',
            'def' => '(pi/180) rad'
        ],
        '\'' => [
            'type' => 'alias',
            'name' => '\'',
            'def' => 'arc_minute',
            'aliasKind' => 'symbol'
        ],
        '′' => [
            'type' => 'alias',
            'name' => '′',
            'def' => 'arc_minute',
            'aliasKind' => 'symbol'
        ],
        'angular_minute' => [
            'type' => 'alias',
            'name' => 'angular_minute',
            'def' => 'arc_minute',
            'aliasKind' => 'alias'
        ],
        'arcminute' => [
            'type' => 'alias',
            'name' => 'arcminute',
            'def' => 'arc_minute',
            'aliasKind' => 'alias'
        ],
        'arcmin' => [
            'type' => 'alias',
            'name' => 'arcmin',
            'def' => 'arc_minute',
            'aliasKind' => 'alias'
        ],
        'arc_minute' => [
            'type' => 'unit',
            'name' => 'arc_minute',
            'definition' => 'measurement of a plane angle equal to 1/60 arc degree',
            'def' => '°/60'
        ],
        '"' => [
            'type' => 'alias',
            'name' => '"',
            'def' => 'arc_second',
            'aliasKind' => 'symbol'
        ],
        '″' => [
            'type' => 'alias',
            'name' => '″',
            'def' => 'arc_second',
            'aliasKind' => 'symbol'
        ],
        'angular_second' => [
            'type' => 'alias',
            'name' => 'angular_second',
            'def' => 'arc_second',
            'aliasKind' => 'alias'
        ],
        'arcsecond' => [
            'type' => 'alias',
            'name' => 'arcsecond',
            'def' => 'arc_second',
            'aliasKind' => 'alias'
        ],
        'arcsec' => [
            'type' => 'alias',
            'name' => 'arcsec',
            'def' => 'arc_second',
            'aliasKind' => 'alias'
        ],
        'arc_second' => [
            'type' => 'unit',
            'name' => 'arc_second',
            'definition' => 'measurement of a plane angle equal to 1/60 arc minute',
            'def' => '\'/60'
        ],
        'L' => [
            'type' => 'alias',
            'name' => 'L',
            'def' => 'liter',
            'aliasKind' => 'symbol'
        ],
        'l' => [
            'type' => 'alias',
            'name' => 'l',
            'def' => 'liter',
            'aliasKind' => 'symbol'
        ],
        'litre' => [
            'type' => 'alias',
            'name' => 'litre',
            'def' => 'liter',
            'aliasKind' => 'alias'
        ],
        'liter' => [
            'type' => 'unit',
            'name' => 'liter',
            'definition' => 'unit of capacity equal to 1000 cubic centimeters',
            'def' => 'dm^3',
            'comment' => 'The definition is exact.  From 1901 to 1964, however, 1 liter was 1.000028 dm^3 (volume of 1 kg of water under standard conditions).'
        ],
        't' => [
            'type' => 'alias',
            'name' => 't',
            'def' => 'metric_ton',
            'aliasKind' => 'symbol'
        ],
        'tonne' => [
            'type' => 'alias',
            'name' => 'tonne',
            'def' => 'metric_ton',
            'aliasKind' => 'alias'
        ],
        'metric_ton' => [
            'type' => 'unit',
            'name' => 'metric_ton',
            'definition' => 'unit of weight equal to 1000 kilograms',
            'def' => '1000 kg'
        ],
        'eV' => [
            'type' => 'alias',
            'name' => 'eV',
            'def' => 'electronvolt',
            'aliasKind' => 'symbol'
        ],
        'electron_volt' => [
            'type' => 'alias',
            'name' => 'electron_volt',
            'def' => 'electronvolt',
            'aliasKind' => 'alias'
        ],
        'electronvolt' => [
            'type' => 'unit',
            'name' => 'electronvolt',
            'definition' => 'unit of energy equal to the work accelerating an electron through a potential difference of one volt',
            'def' => '1.60217733e-19 J',
            'comment' => 'Unit\'s value is obtained experimentally'
        ],
        'u' => [
            'type' => 'alias',
            'name' => 'u',
            'def' => 'unified_atomic_mass_unit',
            'aliasKind' => 'symbol'
        ],
        'atomic_mass_unit' => [
            'type' => 'alias',
            'name' => 'atomic_mass_unit',
            'def' => 'unified_atomic_mass_unit',
            'aliasKind' => 'alias'
        ],
        'atomicmassunit' => [
            'type' => 'alias',
            'name' => 'atomicmassunit',
            'def' => 'unified_atomic_mass_unit',
            'aliasKind' => 'alias'
        ],
        'amu' => [
            'type' => 'alias',
            'name' => 'amu',
            'def' => 'unified_atomic_mass_unit',
            'aliasKind' => 'alias'
        ],
        'unified_atomic_mass_unit' => [
            'type' => 'unit',
            'name' => 'unified_atomic_mass_unit',
            'definition' => 'standard unit for indicating mass on an atomic or molecular scale; is approximately the mass of one nucleon (either a single proton or neutron), and equivalent to 1 g/mol',
            'def' => '1.6605402e-27 kg',
            'comment' => 'Unit\'s value is obtained experimentally'
        ],
        'au' => [
            'type' => 'alias',
            'name' => 'au',
            'def' => 'astronomical_unit',
            'aliasKind' => 'symbol'
        ],
        'astronomical_unit' => [
            'type' => 'unit',
            'name' => 'astronomical_unit',
            'definition' => 'Exact definition according to 2012 resolution by the International Astronomical Union (IAU). Ostensibly equal to the mean distance from the center of the earth to the center of the sun.',
            'def' => '1.49597870700e11 m',
            'comment' => 'According to resolution by the International Astronomical Union (IAU) in 2012'
        ],
        'ua' => [
            'type' => 'alias',
            'name' => 'ua',
            'def' => 'astronomical_unit_BIPM_2006',
            'aliasKind' => 'symbol'
        ],
        'astronomical_unit_BIPM_2006' => [
            'type' => 'unit',
            'name' => 'astronomical_unit_BIPM_2006',
            'definition' => 'unit of measurement equal to 149.6 million kilometers, the mean distance from the center of the earth to the center of the sun according to the International Bureau of Weights and Measures (BIPM) in 2006',
            'def' => '1.495979e11 m',
            'comment' => 'Pre-2012 resolution by the IAU'
        ],
        'nautical_mile' => [
            'type' => 'unit',
            'name' => 'nautical_mile',
            'definition' => 'unit of distance at sea, set at 1852 meters (approximately one minute of arc measured along any meridian)',
            'def' => '1852 m',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'knot_international' => [
            'type' => 'alias',
            'name' => 'knot_international',
            'def' => 'international_knot',
            'aliasKind' => 'alias'
        ],
        'knot' => [
            'type' => 'alias',
            'name' => 'knot',
            'def' => 'international_knot',
            'aliasKind' => 'alias'
        ],
        'international_knot' => [
            'type' => 'unit',
            'name' => 'international_knot',
            'definition' => 'derived unit of speed at sea',
            'def' => 'nautical_mile/hour',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'Å' => [
            'type' => 'alias',
            'name' => 'Å',
            'def' => 'angstrom',
            'aliasKind' => 'symbol'
        ],
        'Å' => [
            'type' => 'alias',
            'name' => 'Å',
            'def' => 'angstrom',
            'aliasKind' => 'symbol'
        ],
        'ångström' => [
            'type' => 'alias',
            'name' => 'ångström',
            'def' => 'angstrom',
            'aliasKind' => 'alias'
        ],
        'angstrom' => [
            'type' => 'unit',
            'name' => 'angstrom',
            'definition' => 'unit of length equal to one hundred-millionth of a centimeter (1 meter/10**10)',
            'def' => '1e-10 m',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'a' => [
            'type' => 'alias',
            'name' => 'a',
            'def' => 'are',
            'aliasKind' => 'symbol'
        ],
        'are' => [
            'type' => 'unit',
            'name' => 'are',
            'definition' => 'unit of area equal to 100 square meters',
            'def' => 'dam^2',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'hectare' => [
            'type' => 'unit',
            'name' => 'hectare',
            'definition' => 'unit of area equal to 100 are (\'hectoare\'), or 10000 square meters',
            'def' => '100 are',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'b' => [
            'type' => 'alias',
            'name' => 'b',
            'def' => 'barn',
            'aliasKind' => 'symbol'
        ],
        'barn' => [
            'type' => 'unit',
            'name' => 'barn',
            'definition' => 'unit of area, approximately the cross-sectional area of a uranium nucleus (10e-28 square meters)',
            'def' => '100 fm^2',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'bar' => [
            'type' => 'unit',
            'name' => 'bar',
            'definition' => 'unit of pressure equal to 100000 Pascals, or about 0.987 atmospheres of pressure',
            'def' => '1000 hPa',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'gal' => [
            'type' => 'unit',
            'name' => 'gal',
            'definition' => 'unit of acceleration equal to 1 cm per second squared',
            'def' => 'cm/s^2',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'Ci' => [
            'type' => 'alias',
            'name' => 'Ci',
            'def' => 'curie',
            'aliasKind' => 'symbol'
        ],
        'curie' => [
            'type' => 'unit',
            'name' => 'curie',
            'definition' => 'unit of radioactivity corresponding to 3.7 * 10e10 disintegrations per second',
            'def' => '3.7e10 Bq',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'R' => [
            'type' => 'alias',
            'name' => 'R',
            'def' => 'roentgen',
            'aliasKind' => 'symbol'
        ],
        'roentgen' => [
            'type' => 'unit',
            'name' => 'roentgen',
            'definition' => 'unit of ionizing radiation, the amount producing one electrostatic unit of positive or negative ionic charge in one cubic centimeter of air under standard conditions; rarely used',
            'def' => '2.58e-4 C/kg',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'rem' => [
            'type' => 'unit',
            'name' => 'rem',
            'definition' => 'unit of radiation dosage applied to humans; equal to one hundredth of a sievert',
            'def' => 'cSv',
            'comment' => 'Unit is temporarily accepted for use with the SI.'
        ],
        'sec' => [
            'type' => 'unit',
            'name' => 'sec',
            'definition' => 'unit of time, synonym for second',
            'def' => 's',
            'comment' => 'Synonym for SI unit'
        ],
        'amp' => [
            'type' => 'unit',
            'name' => 'amp',
            'definition' => 'unit of electric current, synonym for ampere',
            'def' => 'A',
            'comment' => 'Synonym for SI unit'
        ],
        '°K' => [
            'type' => 'alias',
            'name' => '°K',
            'def' => 'degree_kelvin',
            'aliasKind' => 'symbol'
        ],
        'degree_K' => [
            'type' => 'alias',
            'name' => 'degree_K',
            'def' => 'degree_kelvin',
            'aliasKind' => 'alias'
        ],
        'degrees_K' => [
            'type' => 'alias',
            'name' => 'degrees_K',
            'def' => 'degree_kelvin',
            'aliasKind' => 'explicit_plural'
        ],
        'degreeK' => [
            'type' => 'alias',
            'name' => 'degreeK',
            'def' => 'degree_kelvin',
            'aliasKind' => 'alias'
        ],
        'degreesK' => [
            'type' => 'alias',
            'name' => 'degreesK',
            'def' => 'degree_kelvin',
            'aliasKind' => 'explicit_plural'
        ],
        'deg_K' => [
            'type' => 'alias',
            'name' => 'deg_K',
            'def' => 'degree_kelvin',
            'aliasKind' => 'alias'
        ],
        'degs_K' => [
            'type' => 'alias',
            'name' => 'degs_K',
            'def' => 'degree_kelvin',
            'aliasKind' => 'explicit_plural'
        ],
        'degK' => [
            'type' => 'alias',
            'name' => 'degK',
            'def' => 'degree_kelvin',
            'aliasKind' => 'alias'
        ],
        'degsK' => [
            'type' => 'alias',
            'name' => 'degsK',
            'def' => 'degree_kelvin',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_kelvin' => [
            'type' => 'unit',
            'name' => 'degree_kelvin',
            'definition' => 'unit of temperature, synonym for kelvin',
            'plural' => 'degrees_kelvin',
            'def' => 'K',
            'comment' => 'Synonym for SI unit'
        ],
        'degrees_kelvin' => [
            'type' => 'alias',
            'name' => 'degrees_kelvin',
            'def' => 'degree_kelvin',
            'aliasKind' => 'explicit_plural'
        ],
        'candle' => [
            'type' => 'unit',
            'name' => 'candle',
            'definition' => 'unit of luminous intensity, synonym for candela',
            'def' => 'cd',
            'comment' => 'Synonym for SI unit'
        ],
        'einstein' => [
            'type' => 'unit',
            'name' => 'einstein',
            'definition' => 'unit of chemical mass, synonym for mole',
            'def' => 'mole',
            'comment' => 'Synonym for SI unit'
        ],
        'Bd' => [
            'type' => 'alias',
            'name' => 'Bd',
            'def' => 'baud',
            'aliasKind' => 'symbol'
        ],
        'bps' => [
            'type' => 'alias',
            'name' => 'bps',
            'def' => 'baud',
            'aliasKind' => 'symbol'
        ],
        'baud' => [
            'type' => 'unit',
            'name' => 'baud',
            'definition' => 'unit of frequency, synonym for hertz',
            'def' => 'Hz',
            'comment' => 'Synonym for SI unit'
        ],
        '℃' => [
            'type' => 'alias',
            'name' => '℃',
            'def' => 'celsius',
            'aliasKind' => 'symbol'
        ],
        'degree_C' => [
            'type' => 'alias',
            'name' => 'degree_C',
            'def' => 'celsius',
            'aliasKind' => 'alias'
        ],
        'degrees_C' => [
            'type' => 'alias',
            'name' => 'degrees_C',
            'def' => 'celsius',
            'aliasKind' => 'explicit_plural'
        ],
        'degreeC' => [
            'type' => 'alias',
            'name' => 'degreeC',
            'def' => 'celsius',
            'aliasKind' => 'alias'
        ],
        'degreesC' => [
            'type' => 'alias',
            'name' => 'degreesC',
            'def' => 'celsius',
            'aliasKind' => 'explicit_plural'
        ],
        'deg_C' => [
            'type' => 'alias',
            'name' => 'deg_C',
            'def' => 'celsius',
            'aliasKind' => 'alias'
        ],
        'degs_C' => [
            'type' => 'alias',
            'name' => 'degs_C',
            'def' => 'celsius',
            'aliasKind' => 'explicit_plural'
        ],
        'degC' => [
            'type' => 'alias',
            'name' => 'degC',
            'def' => 'celsius',
            'aliasKind' => 'alias'
        ],
        'degsC' => [
            'type' => 'alias',
            'name' => 'degsC',
            'def' => 'celsius',
            'aliasKind' => 'explicit_plural'
        ],
        'celsius' => [
            'type' => 'unit',
            'name' => 'celsius',
            'definition' => 'unit of temperature, synonym for \'K @ 273.15\' (degree_Celsius)',
            'def' => 'degree_Celsius',
            'comment' => 'Synonym for SI unit'
        ],
        'kts' => [
            'type' => 'alias',
            'name' => 'kts',
            'def' => 'kt',
            'aliasKind' => 'symbol'
        ],
        'kt' => [
            'type' => 'unit',
            'name' => 'kt',
            'definition' => 'unit of speed, synonym for nautical_mile/hour',
            'def' => 'knot',
            'comment' => 'Synonym for SI unit'
        ],
        'avogadro_constant' => [
            'type' => 'unit',
            'name' => 'avogadro_constant',
            'definition' => 'number of constituent particles (usually atoms or molecules) per mole of a given substance',
            'def' => '6.02214179e23/mol',
            'comment' => 'Constant; value is +-30e15'
        ],
        '%' => [
            'type' => 'alias',
            'name' => '%',
            'def' => 'percent',
            'aliasKind' => 'symbol'
        ],
        'percent' => [
            'type' => 'unit',
            'name' => 'percent',
            'definition' => 'number of parts per hundred',
            'def' => '0.01',
            'comment' => 'Constant'
        ],
        'ppv' => [
            'type' => 'unit',
            'name' => 'ppv',
            'definition' => 'parts per volume',
            'def' => '1',
            'comment' => 'Constant'
        ],
        'ppmv' => [
            'type' => 'alias',
            'name' => 'ppmv',
            'def' => 'ppm',
            'aliasKind' => 'symbol'
        ],
        'ppm' => [
            'type' => 'unit',
            'name' => 'ppm',
            'definition' => 'parts per million',
            'def' => '1e-6',
            'comment' => 'Constant'
        ],
        'ppbv' => [
            'type' => 'alias',
            'name' => 'ppbv',
            'def' => 'ppb',
            'aliasKind' => 'symbol'
        ],
        'ppb' => [
            'type' => 'unit',
            'name' => 'ppb',
            'definition' => 'parts per billion',
            'def' => '1e-9',
            'comment' => 'Constant'
        ],
        'pptv' => [
            'type' => 'alias',
            'name' => 'pptv',
            'def' => 'ppt',
            'aliasKind' => 'symbol'
        ],
        'ppt' => [
            'type' => 'unit',
            'name' => 'ppt',
            'definition' => 'parts per trillion',
            'def' => '1e-12',
            'comment' => 'Constant'
        ],
        'ppqv' => [
            'type' => 'alias',
            'name' => 'ppqv',
            'def' => 'ppq',
            'aliasKind' => 'symbol'
        ],
        'ppq' => [
            'type' => 'unit',
            'name' => 'ppq',
            'definition' => 'parts per quadrillion',
            'def' => '1e-15',
            'comment' => 'Constant'
        ],
        'grade' => [
            'type' => 'unit',
            'name' => 'grade',
            'definition' => '1/100 of a right angle (90 degrees)',
            'def' => '0.9 arc_degree'
        ],
        'cycle' => [
            'type' => 'alias',
            'name' => 'cycle',
            'def' => 'circle',
            'aliasKind' => 'alias'
        ],
        'turn' => [
            'type' => 'alias',
            'name' => 'turn',
            'def' => 'circle',
            'aliasKind' => 'alias'
        ],
        'revolution' => [
            'type' => 'alias',
            'name' => 'revolution',
            'def' => 'circle',
            'aliasKind' => 'alias'
        ],
        'rotation' => [
            'type' => 'alias',
            'name' => 'rotation',
            'def' => 'circle',
            'aliasKind' => 'alias'
        ],
        'circle' => [
            'type' => 'unit',
            'name' => 'circle',
            'definition' => 'unit of angle in a plane signifying a full 360-degree circle',
            'def' => '2 pi rad'
        ],
        'degree_N' => [
            'type' => 'alias',
            'name' => 'degree_N',
            'def' => 'degree_north',
            'aliasKind' => 'alias'
        ],
        'degrees_N' => [
            'type' => 'alias',
            'name' => 'degrees_N',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degreeN' => [
            'type' => 'alias',
            'name' => 'degreeN',
            'def' => 'degree_north',
            'aliasKind' => 'alias'
        ],
        'degreesN' => [
            'type' => 'alias',
            'name' => 'degreesN',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_east' => [
            'type' => 'alias',
            'name' => 'degree_east',
            'def' => 'degree_north',
            'aliasKind' => 'alias'
        ],
        'degrees_east' => [
            'type' => 'alias',
            'name' => 'degrees_east',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_E' => [
            'type' => 'alias',
            'name' => 'degree_E',
            'def' => 'degree_north',
            'aliasKind' => 'alias'
        ],
        'degrees_E' => [
            'type' => 'alias',
            'name' => 'degrees_E',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degreeE' => [
            'type' => 'alias',
            'name' => 'degreeE',
            'def' => 'degree_north',
            'aliasKind' => 'alias'
        ],
        'degreesE' => [
            'type' => 'alias',
            'name' => 'degreesE',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_true' => [
            'type' => 'alias',
            'name' => 'degree_true',
            'def' => 'degree_north',
            'aliasKind' => 'alias'
        ],
        'degrees_true' => [
            'type' => 'alias',
            'name' => 'degrees_true',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_T' => [
            'type' => 'alias',
            'name' => 'degree_T',
            'def' => 'degree_north',
            'aliasKind' => 'alias'
        ],
        'degrees_T' => [
            'type' => 'alias',
            'name' => 'degrees_T',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degreeT' => [
            'type' => 'alias',
            'name' => 'degreeT',
            'def' => 'degree_north',
            'aliasKind' => 'alias'
        ],
        'degreesT' => [
            'type' => 'alias',
            'name' => 'degreesT',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_north' => [
            'type' => 'unit',
            'name' => 'degree_north',
            'definition' => 'unit of angle on a sphere',
            'plural' => 'degrees_north',
            'def' => 'arc_degree'
        ],
        'degrees_north' => [
            'type' => 'alias',
            'name' => 'degrees_north',
            'def' => 'degree_north',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_W' => [
            'type' => 'alias',
            'name' => 'degree_W',
            'def' => 'degree_west',
            'aliasKind' => 'alias'
        ],
        'degrees_W' => [
            'type' => 'alias',
            'name' => 'degrees_W',
            'def' => 'degree_west',
            'aliasKind' => 'explicit_plural'
        ],
        'degreeW' => [
            'type' => 'alias',
            'name' => 'degreeW',
            'def' => 'degree_west',
            'aliasKind' => 'alias'
        ],
        'degreesW' => [
            'type' => 'alias',
            'name' => 'degreesW',
            'def' => 'degree_west',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_west' => [
            'type' => 'unit',
            'name' => 'degree_west',
            'definition' => 'unit of angle on a sphere (units for negative direction)',
            'plural' => 'degrees_west',
            'def' => '-1 degree_east'
        ],
        'degrees_west' => [
            'type' => 'alias',
            'name' => 'degrees_west',
            'def' => 'degree_west',
            'aliasKind' => 'explicit_plural'
        ],
        'assay_ton' => [
            'type' => 'unit',
            'name' => 'assay_ton',
            'definition' => 'reference unit of mass for a body of ore; roughly equal to 29167 milligrams',
            'def' => '2.916667e-2 kg'
        ],
        'avoirdupois_ounce' => [
            'type' => 'unit',
            'name' => 'avoirdupois_ounce',
            'definition' => 'unit of mass equal to 1/16 avoirdupois pound, commonly used in the United States (16 oz = 1 pound = 7000 grains)',
            'def' => '2.834952e-2 kg'
        ],
        'lb' => [
            'type' => 'alias',
            'name' => 'lb',
            'def' => 'avoirdupois_pound',
            'aliasKind' => 'symbol'
        ],
        'pound' => [
            'type' => 'alias',
            'name' => 'pound',
            'def' => 'avoirdupois_pound',
            'aliasKind' => 'alias'
        ],
        'avoirdupois_pound' => [
            'type' => 'unit',
            'name' => 'avoirdupois_pound',
            'definition' => 'unit of mass in avoirdupois system of weights (a system commonly used in United States)',
            'def' => '4.5359237e-1 kg'
        ],
        'carat' => [
            'type' => 'unit',
            'name' => 'carat',
            'definition' => 'unit of mass equal to 0.2 gram (defined 1907)',
            'def' => '2e-4 kg'
        ],
        'gr' => [
            'type' => 'alias',
            'name' => 'gr',
            'def' => 'grain',
            'aliasKind' => 'symbol'
        ],
        'grain' => [
            'type' => 'unit',
            'name' => 'grain',
            'definition' => 'unit of mass equal to 1/7000 pound',
            'def' => '6.479891e-5 kg'
        ],
        'long_hundredweight' => [
            'type' => 'unit',
            'name' => 'long_hundredweight',
            'definition' => 'unit of mass; a British hundredweight, which is 8 stone * 14 pounds/stone',
            'def' => '5.080235e1 kg'
        ],
        'pennyweight' => [
            'type' => 'unit',
            'name' => 'pennyweight',
            'definition' => 'unit of mass; based on historical US troy weight system (is 1/20 troy ounce)',
            'def' => '1.555174e-3 kg'
        ],
        'short_hundredweight' => [
            'type' => 'unit',
            'name' => 'short_hundredweight',
            'definition' => 'unit of mass, a US hundredweight, which is 100 pounds',
            'def' => '4.535924e1 kg'
        ],
        'slug' => [
            'type' => 'unit',
            'name' => 'slug',
            'definition' => 'unit of mass associated with Imperial units; a mass that accelerates by 1 ft/s2 when a force of one pound-force (lbF) is exerted on it',
            'def' => '14.59390 kg'
        ],
        'apothecary_ounce' => [
            'type' => 'alias',
            'name' => 'apothecary_ounce',
            'def' => 'troy_ounce',
            'aliasKind' => 'alias'
        ],
        'troy_ounce' => [
            'type' => 'unit',
            'name' => 'troy_ounce',
            'definition' => 'unit of mass; based on historical US troy weight system (is 1/12 troy pound)',
            'def' => '3.110348e-2 kg'
        ],
        'apothecary_pound' => [
            'type' => 'alias',
            'name' => 'apothecary_pound',
            'def' => 'troy_pound',
            'aliasKind' => 'alias'
        ],
        'troy_pound' => [
            'type' => 'unit',
            'name' => 'troy_pound',
            'definition' => 'unit of mass; based on historical US troy weight system (is 5760 grain)',
            'def' => '3.732417e-1 kg'
        ],
        'scruple' => [
            'type' => 'unit',
            'name' => 'scruple',
            'definition' => 'unit of mass in apothecaries\' weight system (is 1/3 apdram)',
            'def' => '20 grain'
        ],
        'apdram' => [
            'type' => 'unit',
            'name' => 'apdram',
            'definition' => 'unit of mass in apothecaries\' weight system (is 1/8 apounce)',
            'def' => '60 grain'
        ],
        'dr' => [
            'type' => 'alias',
            'name' => 'dr',
            'def' => 'dram',
            'aliasKind' => 'symbol'
        ],
        'dram' => [
            'type' => 'unit',
            'name' => 'dram',
            'definition' => 'unit of mass in the avoirdupois system (the system commonly used in the United States)',
            'def' => 'avoirdupois_ounce/16',
            'comment' => 'exact'
        ],
        'apounce' => [
            'type' => 'unit',
            'name' => 'apounce',
            'definition' => 'unit of mass in apothecaries\' weight system (is 1/16 appound)',
            'def' => '480 grain'
        ],
        'appound' => [
            'type' => 'unit',
            'name' => 'appound',
            'definition' => 'unit of mass in apothecaries\' weight system (is same as a troy pound)',
            'def' => '5760 grain'
        ],
        'bag' => [
            'type' => 'unit',
            'name' => 'bag',
            'definition' => 'unit of mass, for a traditional bag of portland cement',
            'def' => '94 pound'
        ],
        'ton' => [
            'type' => 'alias',
            'name' => 'ton',
            'def' => 'short_ton',
            'aliasKind' => 'alias'
        ],
        'short_ton' => [
            'type' => 'unit',
            'name' => 'short_ton',
            'definition' => 'unit of mass based on US weight system',
            'def' => '2000 pound'
        ],
        'long_ton' => [
            'type' => 'unit',
            'name' => 'long_ton',
            'definition' => 'unit of mass based on British imperial weight system',
            'def' => '2240 pound'
        ],
        'fermi' => [
            'type' => 'unit',
            'name' => 'fermi',
            'definition' => 'unit of length equal to 10e-15 meters, a typical length-scale of nuclear physics',
            'def' => '1e-15 m'
        ],
        'light_year' => [
            'type' => 'unit',
            'name' => 'light_year',
            'definition' => 'unit of length equal to the distance traversed by light in one mean solar year (365.2422 days), a typical length-scale of astronomy',
            'def' => '9.46073e15 m'
        ],
        'micron' => [
            'type' => 'unit',
            'name' => 'micron',
            'definition' => 'unit of length, a typical length-scale of technology and science fields',
            'def' => '1e-6 m'
        ],
        'mil' => [
            'type' => 'unit',
            'name' => 'mil',
            'definition' => 'unit of length equal to 0.001 inch, a typical length-scale for measuring wire diameters',
            'def' => '2.54e-5 m'
        ],
        'parsec' => [
            'type' => 'unit',
            'name' => 'parsec',
            'definition' => 'unit of length corresponding to the distance at which the mean radius of the earth\'s orbit subtends an angle of one second of arc, a typical length-scale of astronomy',
            'def' => '3.085678e16 m'
        ],
        'printers_point' => [
            'type' => 'unit',
            'name' => 'printers_point',
            'definition' => 'unit of length equal to 1/72.27 inch, the original (standardized 1886) unit for measuring font size and other small items on a printed page (see also big_point)',
            'def' => '3.514598e-4 m'
        ],
        'chain' => [
            'type' => 'unit',
            'name' => 'chain',
            'definition' => 'unit of length equal to 66 feet (4 poles), or 1/10 furlong, a typical (historical) scale of land surveying',
            'def' => '2.011684e1 m'
        ],
        'pica' => [
            'type' => 'alias',
            'name' => 'pica',
            'def' => 'printers_pica',
            'aliasKind' => 'alias'
        ],
        'printers_pica' => [
            'type' => 'unit',
            'name' => 'printers_pica',
            'definition' => 'unit of length equal to 1/6 inch (12 printers points)',
            'def' => '12 printers_point'
        ],
        'nmile' => [
            'type' => 'unit',
            'name' => 'nmile',
            'definition' => 'unit of length in the US Customary System, equal to 6,076 feet; typically used for air and sea navigation',
            'def' => 'nautical_mile'
        ],
        'US_survey_foot' => [
            'type' => 'unit',
            'name' => 'US_survey_foot',
            'definition' => 'unit of length used for earlier survey data in some countries, slightly different than the current international foot',
            'plural' => 'US_survey_feet',
            'def' => '(1200/3937) m'
        ],
        'US_survey_feet' => [
            'type' => 'alias',
            'name' => 'US_survey_feet',
            'def' => 'US_survey_foot',
            'aliasKind' => 'explicit_plural'
        ],
        'US_survey_yard' => [
            'type' => 'unit',
            'name' => 'US_survey_yard',
            'definition' => 'unit of length used in earlier survey data in some countries, slightly different than the current international yard',
            'def' => '3 US_survey_feet'
        ],
        'US_statute_mile' => [
            'type' => 'alias',
            'name' => 'US_statute_mile',
            'def' => 'US_survey_mile',
            'aliasKind' => 'alias'
        ],
        'US_survey_mile' => [
            'type' => 'unit',
            'name' => 'US_survey_mile',
            'definition' => 'unit of length used for earlier survey data in some countries, slightly slightly different than the current international mile',
            'def' => '5280 US_survey_feet'
        ],
        'pole' => [
            'type' => 'alias',
            'name' => 'pole',
            'def' => 'rod',
            'aliasKind' => 'alias'
        ],
        'perch' => [
            'type' => 'alias',
            'name' => 'perch',
            'def' => 'rod',
            'aliasKind' => 'alias'
        ],
        'rod' => [
            'type' => 'unit',
            'name' => 'rod',
            'definition' => 'unit of length equal to one-fourth of a surveyor\'s chain',
            'def' => '16.5 US_survey_feet'
        ],
        'furlong' => [
            'type' => 'unit',
            'name' => 'furlong',
            'definition' => 'unit of length equal to 1/8 mile or 10 chains',
            'def' => '660 US_survey_feet'
        ],
        'fathom' => [
            'type' => 'unit',
            'name' => 'fathom',
            'definition' => 'unit of length equal to 6 feet in the imperial and US customary systems, typically used for measuring depth of water',
            'def' => '6 US_survey_feet'
        ],
        'in' => [
            'type' => 'alias',
            'name' => 'in',
            'def' => 'international_inch',
            'aliasKind' => 'symbol'
        ],
        'inch' => [
            'type' => 'alias',
            'name' => 'inch',
            'def' => 'international_inch',
            'aliasKind' => 'alias'
        ],
        'international_inch' => [
            'type' => 'unit',
            'name' => 'international_inch',
            'definition' => 'unit of length equal to 25.4 mm by definition, used in imperial and US customary systems',
            'def' => '2.54 cm'
        ],
        'ft' => [
            'type' => 'alias',
            'name' => 'ft',
            'def' => 'international_foot',
            'aliasKind' => 'symbol'
        ],
        'foot' => [
            'type' => 'alias',
            'name' => 'foot',
            'def' => 'international_foot',
            'aliasKind' => 'alias'
        ],
        'feet' => [
            'type' => 'alias',
            'name' => 'feet',
            'def' => 'international_foot',
            'aliasKind' => 'explicit_plural'
        ],
        'international_foot' => [
            'type' => 'unit',
            'name' => 'international_foot',
            'definition' => 'unit of length equal to 12 international inches, in the imperial and US customary systems; primarily used in the United States',
            'plural' => 'international_feet',
            'def' => '12 international_inches'
        ],
        'international_feet' => [
            'type' => 'alias',
            'name' => 'international_feet',
            'def' => 'international_foot',
            'aliasKind' => 'explicit_plural'
        ],
        'yd' => [
            'type' => 'alias',
            'name' => 'yd',
            'def' => 'international_yard',
            'aliasKind' => 'symbol'
        ],
        'yard' => [
            'type' => 'alias',
            'name' => 'yard',
            'def' => 'international_yard',
            'aliasKind' => 'alias'
        ],
        'international_yard' => [
            'type' => 'unit',
            'name' => 'international_yard',
            'definition' => 'unit of length equal to 3 international feet, in the imperial and US customary systems; primarily used in the United States',
            'def' => '3 international_feet'
        ],
        'mi' => [
            'type' => 'alias',
            'name' => 'mi',
            'def' => 'international_mile',
            'aliasKind' => 'symbol'
        ],
        'mile' => [
            'type' => 'alias',
            'name' => 'mile',
            'def' => 'international_mile',
            'aliasKind' => 'alias'
        ],
        'international_mile' => [
            'type' => 'unit',
            'name' => 'international_mile',
            'definition' => 'unit of length equal to 5280 feet, equal to 12 international inches, in the imperial and US customary systems; primarily used in the United States and other smaller countries with ties to the US or United Kingdom',
            'def' => '5280 international_feet'
        ],
        'big_point' => [
            'type' => 'unit',
            'name' => 'big_point',
            'definition' => 'unit of length equal to 1/72 inch, standardized unit in modern computer-based publishing for measuring font size and other small items on a printed page (contrast to printers_point)',
            'def' => 'inch/72'
        ],
        'barleycorn' => [
            'type' => 'unit',
            'name' => 'barleycorn',
            'definition' => 'unit of length based in medieval laws of England and Wales, defining an inch as being 3 barleycorns long (length of a corn of barley); still the basis for current shoe sizes in Great Britain and Ireland',
            'def' => 'inch/3'
        ],
        'arpentlin' => [
            'type' => 'unit',
            'name' => 'arpentlin',
            'definition' => 'unit of length in French regions; a linear arpent is of length 10 perch (10 rod)',
            'def' => '191.835 foot'
        ],
        'rps' => [
            'type' => 'alias',
            'name' => 'rps',
            'def' => 'rotation_per_second',
            'aliasKind' => 'symbol'
        ],
        'cps' => [
            'type' => 'alias',
            'name' => 'cps',
            'def' => 'rotation_per_second',
            'aliasKind' => 'symbol'
        ],
        'rotation_per_second' => [
            'type' => 'unit',
            'name' => 'rotation_per_second',
            'definition' => 'unit of angular velocity',
            'plural' => 'rotations_per_second',
            'def' => 'rotation/second',
            'comment' => 'exact'
        ],
        'rotations_per_second' => [
            'type' => 'alias',
            'name' => 'rotations_per_second',
            'def' => 'rotation_per_second',
            'aliasKind' => 'explicit_plural'
        ],
        'rpm' => [
            'type' => 'unit',
            'name' => 'rpm',
            'definition' => 'unit of angular velocity measuring the angular distance covered by a rotating object, divided by the amount of time used to cover that distance; measured perpendicular to the plane of rotation, with direction usually indicated by the right-hand rule',
            'def' => 'rotation/minute',
            'comment' => 'exact'
        ],
        'denier' => [
            'type' => 'unit',
            'name' => 'denier',
            'definition' => 'unit of lineic mass density for fibers, equal to the mass in grams per 9000 meters (more common in United States and United Kingdom); a single strand of silk is approximately one denier',
            'def' => '1.111111e-7 kg/m'
        ],
        'tex' => [
            'type' => 'unit',
            'name' => 'tex',
            'definition' => 'unit of lineic mass density for fibers, defined as mass in grams per 1000 meters (more common in Canada and Continental Europe)',
            'def' => '1e-6 kg/m',
            'comment' => 'exact'
        ],
        'perm_0C' => [
            'type' => 'unit',
            'name' => 'perm_0C',
            'definition' => 'unit of mass per unit time (includes flow) for how fast water vapor flows through substance, or permeance; equals 1 gram of water vapor per hour, per square meter, per millimeter of mercury at 0 degrees C',
            'plural' => 'perms_0C',
            'def' => '5.72135e-11 kg/(Pa.s.m^2)'
        ],
        'perms_0C' => [
            'type' => 'alias',
            'name' => 'perms_0C',
            'def' => 'perm_0C',
            'aliasKind' => 'explicit_plural'
        ],
        'perm_23C' => [
            'type' => 'unit',
            'name' => 'perm_23C',
            'definition' => 'unit of mass per unit time for how fast water vapor flows through substance, or permeance; equals 1 gram of water vapor per hour, per square meter, per millimeter of mercury at 23 degrees C',
            'plural' => 'perms_23C',
            'def' => '5.74525e-11 kg/(Pa.s.m^2)'
        ],
        'perms_23C' => [
            'type' => 'alias',
            'name' => 'perms_23C',
            'def' => 'perm_23C',
            'aliasKind' => 'explicit_plural'
        ],
        'circular_mil' => [
            'type' => 'unit',
            'name' => 'circular_mil',
            'definition' => 'unit of area equal to the area of a one-mil diameter circle',
            'def' => '5.067075e-10 m^2'
        ],
        'darcy' => [
            'type' => 'unit',
            'name' => 'darcy',
            'definition' => 'unit of area for measuring permeability to fluid, equal to 1 cubic centimeter of fluid with 1 centipoise viscosity in 1 second through a 1-square-centimeter cross section of porous medium 1 centimeter long at 1 atmosphere',
            'def' => '9.869233e-13 m^2',
            'comment' => 'porous solid permeability'
        ],
        'acre' => [
            'type' => 'unit',
            'name' => 'acre',
            'definition' => 'unit of area in the US Customary System, used in land and sea floor measurement, equal to 43560 square feet',
            'def' => '160 rod^2',
            'comment' => 'exact'
        ],
        'acre_foot' => [
            'type' => 'unit',
            'name' => 'acre_foot',
            'definition' => 'unit of volume used to describe large-scale water resources in the United State; equal to the volume of one acre of surface area with one foot of depth depth',
            'plural' => 'acre_feet',
            'def' => '1.233489e3 m^3',
            'comment' => 'An "acre.foot", however, is 1233.4867714897 m^3.  Odd.'
        ],
        'acre_feet' => [
            'type' => 'alias',
            'name' => 'acre_feet',
            'def' => 'acre_foot',
            'aliasKind' => 'explicit_plural'
        ],
        'board_foot' => [
            'type' => 'unit',
            'name' => 'board_foot',
            'definition' => 'unit of volume equal to the cubic contents of a piece of lumber one foot square and one inch thick, used in measuring logs and lumber in the United States and Canada',
            'plural' => 'board_feet',
            'def' => '2.359737e-3 m^3'
        ],
        'board_feet' => [
            'type' => 'alias',
            'name' => 'board_feet',
            'def' => 'board_foot',
            'aliasKind' => 'explicit_plural'
        ],
        'bu' => [
            'type' => 'alias',
            'name' => 'bu',
            'def' => 'bushel',
            'aliasKind' => 'symbol'
        ],
        'bushel' => [
            'type' => 'unit',
            'name' => 'bushel',
            'definition' => 'unit of volume defined as 2150.42 cubic inches or 4 pecks in the US Customary system (and formerly in England), where it is used as a dry measure',
            'def' => '3.523907e-2 m^3'
        ],
        'pk' => [
            'type' => 'alias',
            'name' => 'pk',
            'def' => 'peck',
            'aliasKind' => 'symbol'
        ],
        'peck' => [
            'type' => 'unit',
            'name' => 'peck',
            'definition' => 'unit of volume defined as 537.6 cubic inches  in the US Customary system (and formerly in England), where it is used as a dry measure',
            'def' => 'bushel/4',
            'comment' => 'exact'
        ],
        'Canadian_liquid_gallon' => [
            'type' => 'unit',
            'name' => 'Canadian_liquid_gallon',
            'definition' => 'unit of volume for liquids in the Imperial system',
            'def' => '4.546090e-3 m^3',
            'comment' => 'exact'
        ],
        'US_dry_gallon' => [
            'type' => 'unit',
            'name' => 'US_dry_gallon',
            'definition' => 'unit of volume for dry measure in the US Customary system, defined as 1/2 peck or 1/8 bushel',
            'def' => '4.404884e-3 m^3'
        ],
        'cc' => [
            'type' => 'unit',
            'name' => 'cc',
            'definition' => 'unit of volume equal to the volume of a cube 1 centimeter on each side',
            'def' => 'cm^3',
            'comment' => 'exact'
        ],
        'stere' => [
            'type' => 'unit',
            'name' => 'stere',
            'definition' => 'unit of volume equal to a cubic meter, originally defined primarily as a measure for firewood',
            'def' => '1 m^3',
            'comment' => 'exact'
        ],
        'register_ton' => [
            'type' => 'unit',
            'name' => 'register_ton',
            'definition' => 'unit of volume used for internal capacity of ships, equal to 100 cubic feet',
            'def' => '2.831685 m^3'
        ],
        'dry_quart' => [
            'type' => 'alias',
            'name' => 'dry_quart',
            'def' => 'US_dry_quart',
            'aliasKind' => 'alias'
        ],
        'US_dry_quart' => [
            'type' => 'unit',
            'name' => 'US_dry_quart',
            'definition' => 'unit of volume for dry measure in the US Customary system, equal to 1/32 US bushel',
            'def' => 'US_dry_gallon/4',
            'comment' => 'exact'
        ],
        'dry_pint' => [
            'type' => 'alias',
            'name' => 'dry_pint',
            'def' => 'US_dry_pint',
            'aliasKind' => 'alias'
        ],
        'US_dry_pint' => [
            'type' => 'unit',
            'name' => 'US_dry_pint',
            'definition' => 'unit of volume for dry measure in the US Customary system, equal to 1/2 US dry quart',
            'def' => 'US_dry_gallon/8',
            'comment' => 'exact'
        ],
        'liquid_gallon' => [
            'type' => 'alias',
            'name' => 'liquid_gallon',
            'def' => 'US_liquid_gallon',
            'aliasKind' => 'alias'
        ],
        'gallon' => [
            'type' => 'alias',
            'name' => 'gallon',
            'def' => 'US_liquid_gallon',
            'aliasKind' => 'alias'
        ],
        'US_liquid_gallon' => [
            'type' => 'unit',
            'name' => 'US_liquid_gallon',
            'definition' => 'unit of volume for liquid measure in the US Customary system, defined as 3.785412 liters',
            'def' => '3.785412e-3 m^3'
        ],
        'bbl' => [
            'type' => 'alias',
            'name' => 'bbl',
            'def' => 'barrel',
            'aliasKind' => 'symbol'
        ],
        'barrel' => [
            'type' => 'unit',
            'name' => 'barrel',
            'definition' => 'unit of volume used by US and Canadian petroleum organizations',
            'def' => '42 US_liquid_gallon',
            'comment' => 'The following is the definition of the petroleum industry'
        ],
        'firkin' => [
            'type' => 'unit',
            'name' => 'firkin',
            'definition' => 'unit of volume whose exact quantity depends on the type of barrel on which it is defined; in this table it is defined based on the oil barrel used by the petroleum industry',
            'def' => 'barrel/4',
            'comment' => 'The following is exact regardless of the definition of "barrel"'
        ],
        'liquid_quart' => [
            'type' => 'alias',
            'name' => 'liquid_quart',
            'def' => 'US_liquid_quart',
            'aliasKind' => 'alias'
        ],
        'quart' => [
            'type' => 'alias',
            'name' => 'quart',
            'def' => 'US_liquid_quart',
            'aliasKind' => 'alias'
        ],
        'US_liquid_quart' => [
            'type' => 'unit',
            'name' => 'US_liquid_quart',
            'definition' => 'unit of volume for liquid measure in the US Customary system, equal to 1/4 liquid gallon',
            'def' => 'US_liquid_gallon/4',
            'comment' => 'exact'
        ],
        'pt' => [
            'type' => 'alias',
            'name' => 'pt',
            'def' => 'US_liquid_pint',
            'aliasKind' => 'symbol'
        ],
        'liquid_pint' => [
            'type' => 'alias',
            'name' => 'liquid_pint',
            'def' => 'US_liquid_pint',
            'aliasKind' => 'alias'
        ],
        'pint' => [
            'type' => 'alias',
            'name' => 'pint',
            'def' => 'US_liquid_pint',
            'aliasKind' => 'alias'
        ],
        'US_liquid_pint' => [
            'type' => 'unit',
            'name' => 'US_liquid_pint',
            'definition' => 'unit of volume for liquid measure in the US Customary system, equal to 1/8 liquid gallon',
            'def' => 'US_liquid_gallon/8',
            'comment' => 'exact'
        ],
        'liquid_cup' => [
            'type' => 'alias',
            'name' => 'liquid_cup',
            'def' => 'US_liquid_cup',
            'aliasKind' => 'alias'
        ],
        'cup' => [
            'type' => 'alias',
            'name' => 'cup',
            'def' => 'US_liquid_cup',
            'aliasKind' => 'alias'
        ],
        'US_liquid_cup' => [
            'type' => 'unit',
            'name' => 'US_liquid_cup',
            'definition' => 'unit of volume for liquid measure in the US Customary system, equal to 1/16 liquid gallon',
            'def' => 'US_liquid_gallon/16',
            'comment' => 'exact'
        ],
        'liquid_gill' => [
            'type' => 'alias',
            'name' => 'liquid_gill',
            'def' => 'US_liquid_gill',
            'aliasKind' => 'alias'
        ],
        'gill' => [
            'type' => 'alias',
            'name' => 'gill',
            'def' => 'US_liquid_gill',
            'aliasKind' => 'alias'
        ],
        'US_liquid_gill' => [
            'type' => 'unit',
            'name' => 'US_liquid_gill',
            'definition' => 'unit of volume for liquid measure in the US Customary system, equal to 1/32 liquid gallon',
            'def' => 'US_liquid_gallon/32',
            'comment' => 'exact'
        ],
        'oz' => [
            'type' => 'alias',
            'name' => 'oz',
            'def' => 'US_fluid_ounce',
            'aliasKind' => 'symbol'
        ],
        'floz' => [
            'type' => 'alias',
            'name' => 'floz',
            'def' => 'US_fluid_ounce',
            'aliasKind' => 'symbol'
        ],
        'US_liquid_ounce' => [
            'type' => 'alias',
            'name' => 'US_liquid_ounce',
            'def' => 'US_fluid_ounce',
            'aliasKind' => 'alias'
        ],
        'fluid_ounce' => [
            'type' => 'alias',
            'name' => 'fluid_ounce',
            'def' => 'US_fluid_ounce',
            'aliasKind' => 'alias'
        ],
        'liquid_ounce' => [
            'type' => 'alias',
            'name' => 'liquid_ounce',
            'def' => 'US_fluid_ounce',
            'aliasKind' => 'alias'
        ],
        'US_fluid_ounce' => [
            'type' => 'unit',
            'name' => 'US_fluid_ounce',
            'definition' => 'unit of volume for liquid measure in the US Customary system, equal to 1/128 liquid gallon',
            'def' => 'US_liquid_gallon/128',
            'comment' => 'exact'
        ],
        'Tbl' => [
            'type' => 'alias',
            'name' => 'Tbl',
            'def' => 'tablespoon',
            'aliasKind' => 'symbol'
        ],
        'Tbsp' => [
            'type' => 'alias',
            'name' => 'Tbsp',
            'def' => 'tablespoon',
            'aliasKind' => 'symbol'
        ],
        'tbsp' => [
            'type' => 'alias',
            'name' => 'tbsp',
            'def' => 'tablespoon',
            'aliasKind' => 'symbol'
        ],
        'Tblsp' => [
            'type' => 'alias',
            'name' => 'Tblsp',
            'def' => 'tablespoon',
            'aliasKind' => 'symbol'
        ],
        'tblsp' => [
            'type' => 'alias',
            'name' => 'tblsp',
            'def' => 'tablespoon',
            'aliasKind' => 'symbol'
        ],
        'tablespoon' => [
            'type' => 'unit',
            'name' => 'tablespoon',
            'definition' => 'unit of volume for liquid measure in the US Customary system, equal to 1/2 liquid ounce',
            'def' => 'US_fluid_ounce/2',
            'comment' => 'exact'
        ],
        'fldr' => [
            'type' => 'alias',
            'name' => 'fldr',
            'def' => 'fluid_dram',
            'aliasKind' => 'symbol'
        ],
        'fluid_dram' => [
            'type' => 'unit',
            'name' => 'fluid_dram',
            'definition' => 'unit of volume for liquid measure in the apothecary system, equal to 1/8 liquid ounce',
            'def' => 'US_fluid_ounce/8',
            'comment' => 'exact'
        ],
        'tsp' => [
            'type' => 'alias',
            'name' => 'tsp',
            'def' => 'teaspoon',
            'aliasKind' => 'symbol'
        ],
        'teaspoon' => [
            'type' => 'unit',
            'name' => 'teaspoon',
            'definition' => 'unit of volume defined as 1/3 tablespoon, the actual volume of which can vary depending on the measurement system (but is based on the US Customary system in this database)',
            'def' => 'tablespoon/3',
            'comment' => 'exact'
        ],
        'UK_liquid_gallon' => [
            'type' => 'unit',
            'name' => 'UK_liquid_gallon',
            'definition' => 'unit of volume for liquid measure in the Imperial system',
            'def' => '4.546090e-3 m^3',
            'comment' => 'exact'
        ],
        'UK_liquid_quart' => [
            'type' => 'unit',
            'name' => 'UK_liquid_quart',
            'definition' => 'unit of volume for liquid measure in the Imperial system, equal to 1/4 liquid gallon',
            'def' => 'UK_liquid_gallon/4',
            'comment' => 'exact'
        ],
        'UK_liquid_pint' => [
            'type' => 'unit',
            'name' => 'UK_liquid_pint',
            'definition' => 'unit of volume for liquid measure in the Imperial system, equal to 1/8 liquid gallon',
            'def' => 'UK_liquid_gallon/8',
            'comment' => 'exact'
        ],
        'UK_liquid_cup' => [
            'type' => 'unit',
            'name' => 'UK_liquid_cup',
            'definition' => 'unit of volume for liquid measure in the Imperial system, equal to 1/16 liquid gallon',
            'def' => 'UK_liquid_gallon/16',
            'comment' => 'exact'
        ],
        'UK_liquid_gill' => [
            'type' => 'unit',
            'name' => 'UK_liquid_gill',
            'definition' => 'unit of volume for liquid measure in the Imperial system, equal to 1/32 liquid gallon',
            'def' => 'UK_liquid_gallon/32',
            'comment' => 'exact'
        ],
        'UK_liquid_ounce' => [
            'type' => 'alias',
            'name' => 'UK_liquid_ounce',
            'def' => 'UK_fluid_ounce',
            'aliasKind' => 'alias'
        ],
        'UK_fluid_ounce' => [
            'type' => 'unit',
            'name' => 'UK_fluid_ounce',
            'definition' => 'unit of volume for liquid measure in the Imperial system, equal to 1/160 liquid gallon',
            'def' => 'UK_liquid_gallon/160',
            'comment' => 'exact'
        ],
        'shake' => [
            'type' => 'unit',
            'name' => 'shake',
            'definition' => 'unit of time approximating the lifetime of an individual neutron, useful for describing very brief durations, e.g., in nuclear physics',
            'def' => '1e-8 s'
        ],
        'sidereal_day' => [
            'type' => 'unit',
            'name' => 'sidereal_day',
            'definition' => 'unit of time that it takes the earth to complete one revolution with respect to a star, roughly 23 hours, 56 minutes, 4 seconds',
            'def' => '8.616409e4 s'
        ],
        'sidereal_hour' => [
            'type' => 'unit',
            'name' => 'sidereal_hour',
            'definition' => 'unit of time equal to 1/24 sidereal day',
            'def' => '3.590170e3 s'
        ],
        'sidereal_minute' => [
            'type' => 'unit',
            'name' => 'sidereal_minute',
            'definition' => 'unit of time equal to 1/60 sidereal hour',
            'def' => '5.983617e1 s'
        ],
        'sidereal_second' => [
            'type' => 'unit',
            'name' => 'sidereal_second',
            'definition' => 'unit of time equal to 1/60 sidereal minute',
            'def' => '0.9972696 s'
        ],
        'sidereal_year' => [
            'type' => 'unit',
            'name' => 'sidereal_year',
            'definition' => 'unit of time for the earth to make one complete revolution around the sun, relative to the fixed stars',
            'def' => '3.155815e7 s'
        ],
        'yr' => [
            'type' => 'alias',
            'name' => 'yr',
            'def' => 'tropical_year',
            'aliasKind' => 'symbol'
        ],
        'year' => [
            'type' => 'alias',
            'name' => 'year',
            'def' => 'tropical_year',
            'aliasKind' => 'alias'
        ],
        'tropical_year' => [
            'type' => 'unit',
            'name' => 'tropical_year',
            'definition' => 'unit of time; Interval between 2 successive passages of sun through vernal equinox (365.242198781 days). See http://www.ast.cam.ac.uk/pubinfo/leaflets/, http://aa.usno.navy.mil/AA/, and http://adswww.colorado.edu/adswww/astro_coord.html',
            'def' => '3.15569259747e7 s'
        ],
        'lunar_month' => [
            'type' => 'unit',
            'name' => 'lunar_month',
            'definition' => 'unit of time equal to the average time between successive new or full moons; equal to approximately 29 days, 12 hours, 44 minutes',
            'def' => '29.530589 day'
        ],
        'common_year' => [
            'type' => 'unit',
            'name' => 'common_year',
            'definition' => 'unit of time corresponding to a \'normal\' calendar year, that is, one without insertion of a leap day',
            'def' => '365 day'
        ],
        'leap_year' => [
            'type' => 'unit',
            'name' => 'leap_year',
            'definition' => 'unit of time corresponding to a calendar year with a leap day inserted',
            'def' => '366 day'
        ],
        'Julian_year' => [
            'type' => 'unit',
            'name' => 'Julian_year',
            'definition' => 'unit of time recognized by the International Astronomical Union for use in astronomy, defined as 365.25 days of 86400 seconds',
            'def' => '365.25 day'
        ],
        'Gregorian_year' => [
            'type' => 'unit',
            'name' => 'Gregorian_year',
            'definition' => 'unit of time based on the Gregorian Calendar, the one commonly used today; approximates the tropical year as 365 + 97/400 days',
            'def' => '365.2425 day'
        ],
        'sidereal_month' => [
            'type' => 'unit',
            'name' => 'sidereal_month',
            'definition' => 'unit of time based on 1/12 of the sidereal year',
            'def' => '27.321661 day'
        ],
        'tropical_month' => [
            'type' => 'unit',
            'name' => 'tropical_month',
            'definition' => 'unit of time based on 1/12 of the tropical year',
            'def' => '27.321582 day'
        ],
        'fortnight' => [
            'type' => 'unit',
            'name' => 'fortnight',
            'definition' => 'unit of time commonly defined as 14 days',
            'def' => '14 day'
        ],
        'week' => [
            'type' => 'unit',
            'name' => 'week',
            'definition' => 'unit of time commonly defined as 7 days',
            'def' => '7 day'
        ],
        'jiffy' => [
            'type' => 'unit',
            'name' => 'jiffy',
            'definition' => 'unit of time used in computer animation as a method of defining playback rate',
            'def' => '0.01 s',
            'comment' => 'multiple values have been proposed for the amount of time represented by a \'jiffy\''
        ],
        'eon' => [
            'type' => 'unit',
            'name' => 'eon',
            'definition' => 'unit of time defined in astronomy as 1 billion years',
            'def' => '1e9 year'
        ],
        'month' => [
            'type' => 'unit',
            'name' => 'month',
            'definition' => 'unit of time defined as the average length of time for a calendar month',
            'def' => 'year/12'
        ],
        'sverdrup' => [
            'type' => 'unit',
            'name' => 'sverdrup',
            'definition' => 'unit of volume transport, used almost exclusively to measure the volumetric rate of ocean currents',
            'def' => '1e6 m^3/s',
            'comment' => 'exact'
        ],
        'standard_free_fall' => [
            'type' => 'unit',
            'name' => 'standard_free_fall',
            'definition' => 'unit of acceleration corresponding to the nominal gravitational acceleration of an object in a vacuum near the surface of earth',
            'def' => '9.806650 m/s^2',
            'comment' => 'exact'
        ],
        'gravity' => [
            'type' => 'unit',
            'name' => 'gravity',
            'definition' => 'unit of acceleration synonymous with standard rate of free fall (in earth\'s gravity)',
            'def' => 'standard_free_fall',
            'comment' => 'should be local'
        ],
        'H2O' => [
            'type' => 'alias',
            'name' => 'H2O',
            'def' => 'conventional_water',
            'aliasKind' => 'symbol'
        ],
        'h2o' => [
            'type' => 'alias',
            'name' => 'h2o',
            'def' => 'conventional_water',
            'aliasKind' => 'symbol'
        ],
        'water' => [
            'type' => 'alias',
            'name' => 'water',
            'def' => 'conventional_water',
            'aliasKind' => 'alias'
        ],
        'conventional_water' => [
            'type' => 'unit',
            'name' => 'conventional_water',
            'definition' => 'specifies the acceleration at the earth\'s surface of a substance with the density of water',
            'def' => 'gravity 1000 kg/m^3',
            'comment' => 'exact'
        ],
        'water_39F' => [
            'type' => 'alias',
            'name' => 'water_39F',
            'def' => 'water_4C',
            'aliasKind' => 'alias'
        ],
        'waters_39F' => [
            'type' => 'alias',
            'name' => 'waters_39F',
            'def' => 'water_4C',
            'aliasKind' => 'explicit_plural'
        ],
        'water_4C' => [
            'type' => 'unit',
            'name' => 'water_4C',
            'definition' => 'specifies the acceleration at the earth\'s surface of a substance with the density of water at 4 degrees C',
            'plural' => 'waters_4C',
            'def' => 'gravity 999.972 kg/m^3'
        ],
        'waters_4C' => [
            'type' => 'alias',
            'name' => 'waters_4C',
            'def' => 'water_4C',
            'aliasKind' => 'explicit_plural'
        ],
        'water_60F' => [
            'type' => 'unit',
            'name' => 'water_60F',
            'definition' => 'specifies the acceleration at the earth\'s surface of a substance with the density of water at 60 degrees F',
            'plural' => 'waters_60F',
            'def' => 'gravity 999.001 kg/m^3'
        ],
        'waters_60F' => [
            'type' => 'alias',
            'name' => 'waters_60F',
            'def' => 'water_60F',
            'aliasKind' => 'explicit_plural'
        ],
        'Hg' => [
            'type' => 'alias',
            'name' => 'Hg',
            'def' => 'mercury_0C',
            'aliasKind' => 'symbol'
        ],
        'mercury_32F' => [
            'type' => 'alias',
            'name' => 'mercury_32F',
            'def' => 'mercury_0C',
            'aliasKind' => 'alias'
        ],
        'mercuries_32F' => [
            'type' => 'alias',
            'name' => 'mercuries_32F',
            'def' => 'mercury_0C',
            'aliasKind' => 'explicit_plural'
        ],
        'conventional_mercury' => [
            'type' => 'alias',
            'name' => 'conventional_mercury',
            'def' => 'mercury_0C',
            'aliasKind' => 'alias'
        ],
        'conventional_mercuries' => [
            'type' => 'alias',
            'name' => 'conventional_mercuries',
            'def' => 'mercury_0C',
            'aliasKind' => 'explicit_plural'
        ],
        'mercury_0C' => [
            'type' => 'unit',
            'name' => 'mercury_0C',
            'definition' => 'specifies the acceleration at the earth\'s surface of a substance with the density of mercury at 0 degrees C',
            'plural' => 'mercuries_0C',
            'def' => 'gravity 13595.10 kg/m^3'
        ],
        'mercuries_0C' => [
            'type' => 'alias',
            'name' => 'mercuries_0C',
            'def' => 'mercury_0C',
            'aliasKind' => 'explicit_plural'
        ],
        'mercury_60F' => [
            'type' => 'unit',
            'name' => 'mercury_60F',
            'definition' => 'specifies the acceleration at the earth\'s surface of a substance with the density of mercury at 60 degrees F',
            'plural' => 'mercuries_60F',
            'def' => 'gravity 13556.8 kg/m^3'
        ],
        'mercuries_60F' => [
            'type' => 'alias',
            'name' => 'mercuries_60F',
            'def' => 'mercury_60F',
            'aliasKind' => 'explicit_plural'
        ],
        'force' => [
            'type' => 'unit',
            'name' => 'force',
            'definition' => 'unit of force equivalent to the force generated by the effect of gravity',
            'def' => 'standard_free_fall'
        ],
        'dyne' => [
            'type' => 'unit',
            'name' => 'dyne',
            'definition' => 'unit of force, equal to the force that produces an acceleration of one centimeter per second per second on a mass of one gram (the standard centimeter-gram-second unit of force)',
            'def' => '1e-5 N',
            'comment' => 'exact'
        ],
        'pond' => [
            'type' => 'unit',
            'name' => 'pond',
            'definition' => 'unit of force, equal to the magnitude of the force exerted by one gram of mass in a 9.80665 m/s2 gravitational field',
            'def' => '9.806650e-3 N',
            'comment' => 'exact'
        ],
        'kgf' => [
            'type' => 'alias',
            'name' => 'kgf',
            'def' => 'force_kilogram',
            'aliasKind' => 'symbol'
        ],
        'kilogram_force' => [
            'type' => 'alias',
            'name' => 'kilogram_force',
            'def' => 'force_kilogram',
            'aliasKind' => 'alias'
        ],
        'kilograms_force' => [
            'type' => 'alias',
            'name' => 'kilograms_force',
            'def' => 'force_kilogram',
            'aliasKind' => 'explicit_plural'
        ],
        'force_kilogram' => [
            'type' => 'unit',
            'name' => 'force_kilogram',
            'definition' => 'unit of force, equal to the magnitude of the force exerted by one kilogram of mass in a 9.80665 m/s2 gravitational field',
            'def' => '9.806650 N',
            'comment' => 'exact'
        ],
        'ozf' => [
            'type' => 'alias',
            'name' => 'ozf',
            'def' => 'force_ounce',
            'aliasKind' => 'symbol'
        ],
        'ounce_force' => [
            'type' => 'alias',
            'name' => 'ounce_force',
            'def' => 'force_ounce',
            'aliasKind' => 'alias'
        ],
        'ounces_force' => [
            'type' => 'alias',
            'name' => 'ounces_force',
            'def' => 'force_ounce',
            'aliasKind' => 'explicit_plural'
        ],
        'force_ounce' => [
            'type' => 'unit',
            'name' => 'force_ounce',
            'definition' => 'unit of force, equal to the magnitude of the force exerted by one ounce of mass in a 9.80665 m/s2 gravitational field',
            'def' => '2.780139e-1 N',
            'comment' => 'exact'
        ],
        'lbf' => [
            'type' => 'alias',
            'name' => 'lbf',
            'def' => 'force_pound',
            'aliasKind' => 'symbol'
        ],
        'pound_force' => [
            'type' => 'alias',
            'name' => 'pound_force',
            'def' => 'force_pound',
            'aliasKind' => 'alias'
        ],
        'pounds_force' => [
            'type' => 'alias',
            'name' => 'pounds_force',
            'def' => 'force_pound',
            'aliasKind' => 'explicit_plural'
        ],
        'force_pound' => [
            'type' => 'unit',
            'name' => 'force_pound',
            'definition' => 'unit of force, equal to the magnitude of the force exerted by one pound of mass in a 9.80665 m/s2 gravitational field',
            'def' => '4.4482216152605 N',
            'comment' => 'exact'
        ],
        'poundal' => [
            'type' => 'unit',
            'name' => 'poundal',
            'definition' => 'unit of force, that which is necessary to accelerate 1 pound-mass to 1 foot per second per second',
            'def' => '1.382550e-1 N',
            'comment' => 'exact'
        ],
        'gf' => [
            'type' => 'alias',
            'name' => 'gf',
            'def' => 'gram_force',
            'aliasKind' => 'symbol'
        ],
        'force_gram' => [
            'type' => 'alias',
            'name' => 'force_gram',
            'def' => 'gram_force',
            'aliasKind' => 'alias'
        ],
        'gram_force' => [
            'type' => 'unit',
            'name' => 'gram_force',
            'definition' => 'unit of force, equal to the magnitude of the force exerted by one gram of mass in a 9.80665 m/s2 gravitational field',
            'plural' => 'grams_force',
            'def' => 'gram force',
            'comment' => 'exact'
        ],
        'grams_force' => [
            'type' => 'alias',
            'name' => 'grams_force',
            'def' => 'gram_force',
            'aliasKind' => 'explicit_plural'
        ],
        'ton_force' => [
            'type' => 'alias',
            'name' => 'ton_force',
            'def' => 'force_ton',
            'aliasKind' => 'alias'
        ],
        'tons_force' => [
            'type' => 'alias',
            'name' => 'tons_force',
            'def' => 'force_ton',
            'aliasKind' => 'explicit_plural'
        ],
        'force_ton' => [
            'type' => 'unit',
            'name' => 'force_ton',
            'definition' => 'unit of force, equal to the magnitude of the force exerted by one ton of mass in a 9.80665 m/s2 gravitational field (specifically a short ton of mass)',
            'def' => '2000 force_pound',
            'comment' => 'exact'
        ],
        'kip' => [
            'type' => 'unit',
            'name' => 'kip',
            'definition' => 'unit of force, equal to the magnitude of the force exerted by one thousand pounds of mass in a 9.80665 m/s2 gravitational field',
            'def' => '1000 lbf',
            'comment' => 'exact'
        ],
        'atm' => [
            'type' => 'alias',
            'name' => 'atm',
            'def' => 'standard_atmosphere',
            'aliasKind' => 'symbol'
        ],
        'atmosphere' => [
            'type' => 'alias',
            'name' => 'atmosphere',
            'def' => 'standard_atmosphere',
            'aliasKind' => 'alias'
        ],
        'standard_atmosphere' => [
            'type' => 'unit',
            'name' => 'standard_atmosphere',
            'definition' => 'unit of pressure, an international reference pressure intended to represent the mean atmospheric pressure at mean sea level at the latitude of Paris, France',
            'def' => '1.01325e5 Pa',
            'comment' => 'exact'
        ],
        'at' => [
            'type' => 'alias',
            'name' => 'at',
            'def' => 'technical_atmosphere',
            'aliasKind' => 'symbol'
        ],
        'technical_atmosphere' => [
            'type' => 'unit',
            'name' => 'technical_atmosphere',
            'definition' => 'unit of pressure equal to one kilogram force per square centimeter',
            'def' => '1 kg gravity/cm ^ 2',
            'comment' => 'exact; note that the symbol \'at\' clashes with that of the katal (\'kat\'), the SI unit of catalytic activity'
        ],
        'cmH2O' => [
            'type' => 'alias',
            'name' => 'cmH2O',
            'def' => 'cm_H2O',
            'aliasKind' => 'symbol'
        ],
        'cm_H2O' => [
            'type' => 'unit',
            'name' => 'cm_H2O',
            'definition' => 'unit of pressure derived from pressure head calculations using metrology; represents the pressure exerted by a column of water of 1 cm height at 4 degrees C',
            'def' => 'cm H2O'
        ],
        'inch_H2O_39F' => [
            'type' => 'unit',
            'name' => 'inch_H2O_39F',
            'definition' => 'unit of pressure representing the pressure exerted by a column of water of 1 inch height at 39 degrees F',
            'plural' => 'inches_H2O_39F',
            'def' => 'inch water_39F',
            'comment' => 'exact'
        ],
        'inches_H2O_39F' => [
            'type' => 'alias',
            'name' => 'inches_H2O_39F',
            'def' => 'inch_H2O_39F',
            'aliasKind' => 'explicit_plural'
        ],
        'inch_H2O_60F' => [
            'type' => 'unit',
            'name' => 'inch_H2O_60F',
            'definition' => 'unit of pressure representing the pressure exerted by a column of water of 1 inch height at 60 degrees F',
            'plural' => 'inches_H2O_60F',
            'def' => 'inch water_60F',
            'comment' => 'exact'
        ],
        'inches_H2O_60F' => [
            'type' => 'alias',
            'name' => 'inches_H2O_60F',
            'def' => 'inch_H2O_60F',
            'aliasKind' => 'explicit_plural'
        ],
        'ftH2O' => [
            'type' => 'alias',
            'name' => 'ftH2O',
            'def' => 'foot_water',
            'aliasKind' => 'symbol'
        ],
        'fth2o' => [
            'type' => 'alias',
            'name' => 'fth2o',
            'def' => 'foot_water',
            'aliasKind' => 'symbol'
        ],
        'foot_H2O' => [
            'type' => 'alias',
            'name' => 'foot_H2O',
            'def' => 'foot_water',
            'aliasKind' => 'alias'
        ],
        'feet_H2O' => [
            'type' => 'alias',
            'name' => 'feet_H2O',
            'def' => 'foot_water',
            'aliasKind' => 'explicit_plural'
        ],
        'footH2O' => [
            'type' => 'alias',
            'name' => 'footH2O',
            'def' => 'foot_water',
            'aliasKind' => 'alias'
        ],
        'feetH2O' => [
            'type' => 'alias',
            'name' => 'feetH2O',
            'def' => 'foot_water',
            'aliasKind' => 'explicit_plural'
        ],
        'foot_water' => [
            'type' => 'unit',
            'name' => 'foot_water',
            'definition' => 'unit of pressure representing the pressure exerted by a column of water of 1 foot height at 4 degrees C',
            'plural' => 'feet_water',
            'def' => 'foot water'
        ],
        'feet_water' => [
            'type' => 'alias',
            'name' => 'feet_water',
            'def' => 'foot_water',
            'aliasKind' => 'explicit_plural'
        ],
        'cmHg' => [
            'type' => 'alias',
            'name' => 'cmHg',
            'def' => 'cm_Hg',
            'aliasKind' => 'symbol'
        ],
        'cm_Hg' => [
            'type' => 'unit',
            'name' => 'cm_Hg',
            'definition' => 'unit of pressure representing the pressure exerted by a column of mercury of 1 cm height at 0 degrees C',
            'def' => 'cm Hg'
        ],
        'millimeter_Hg_0C' => [
            'type' => 'unit',
            'name' => 'millimeter_Hg_0C',
            'definition' => 'unit of pressure representing the pressure exerted by a column of mercury of 1 mm height at 0 degrees C',
            'plural' => 'millimeters_Hg_0C',
            'def' => 'mm mercury_0C',
            'comment' => 'exact'
        ],
        'millimeters_Hg_0C' => [
            'type' => 'alias',
            'name' => 'millimeters_Hg_0C',
            'def' => 'millimeter_Hg_0C',
            'aliasKind' => 'explicit_plural'
        ],
        'inch_Hg_32F' => [
            'type' => 'unit',
            'name' => 'inch_Hg_32F',
            'definition' => 'unit of pressure representing the pressure exerted by a column of mercury of 1 inch height at 32 degrees F',
            'plural' => 'inches_Hg_32F',
            'def' => 'inch mercury_32F',
            'comment' => 'exact'
        ],
        'inches_Hg_32F' => [
            'type' => 'alias',
            'name' => 'inches_Hg_32F',
            'def' => 'inch_Hg_32F',
            'aliasKind' => 'explicit_plural'
        ],
        'inch_Hg_60F' => [
            'type' => 'unit',
            'name' => 'inch_Hg_60F',
            'definition' => 'unit of pressure representing the pressure exerted by a column of mercury of 1 inch height at 60 degrees F',
            'plural' => 'inches_Hg_60F',
            'def' => 'inch mercury_60F',
            'comment' => 'exact'
        ],
        'inches_Hg_60F' => [
            'type' => 'alias',
            'name' => 'inches_Hg_60F',
            'def' => 'inch_Hg_60F',
            'aliasKind' => 'explicit_plural'
        ],
        'mm_Hg' => [
            'type' => 'alias',
            'name' => 'mm_Hg',
            'def' => 'millimeter_Hg',
            'aliasKind' => 'symbol'
        ],
        'mm_hg' => [
            'type' => 'alias',
            'name' => 'mm_hg',
            'def' => 'millimeter_Hg',
            'aliasKind' => 'symbol'
        ],
        'mmHg' => [
            'type' => 'alias',
            'name' => 'mmHg',
            'def' => 'millimeter_Hg',
            'aliasKind' => 'symbol'
        ],
        'mmhg' => [
            'type' => 'alias',
            'name' => 'mmhg',
            'def' => 'millimeter_Hg',
            'aliasKind' => 'symbol'
        ],
        'torr' => [
            'type' => 'alias',
            'name' => 'torr',
            'def' => 'millimeter_Hg',
            'aliasKind' => 'alias'
        ],
        'millimeter_Hg' => [
            'type' => 'unit',
            'name' => 'millimeter_Hg',
            'definition' => 'unit of pressure representing the pressure exerted by a column of mercury of 1 mm height at 0 degrees C; approximately (within 0.000015%, generally below measurement error) 1 Torr, which is 1/760 standard atmospheric pressure',
            'plural' => 'millimeters_Hg',
            'def' => 'mm Hg'
        ],
        'millimeters_Hg' => [
            'type' => 'alias',
            'name' => 'millimeters_Hg',
            'def' => 'millimeter_Hg',
            'aliasKind' => 'explicit_plural'
        ],
        'in_Hg' => [
            'type' => 'alias',
            'name' => 'in_Hg',
            'def' => 'inch_Hg',
            'aliasKind' => 'symbol'
        ],
        'inHg' => [
            'type' => 'alias',
            'name' => 'inHg',
            'def' => 'inch_Hg',
            'aliasKind' => 'symbol'
        ],
        'inch_Hg' => [
            'type' => 'unit',
            'name' => 'inch_Hg',
            'definition' => 'unit of pressure representing the pressure exerted by a column of mercury of 1 inch height at 0 degrees C',
            'plural' => 'inches_Hg',
            'def' => 'inch Hg'
        ],
        'inches_Hg' => [
            'type' => 'alias',
            'name' => 'inches_Hg',
            'def' => 'inch_Hg',
            'aliasKind' => 'explicit_plural'
        ],
        'psi' => [
            'type' => 'unit',
            'name' => 'psi',
            'definition' => 'unit of pressure representing the pressure exerted, due to gravity, by a one-pound mass, of area one square inch; commonly referred to as "pounds per square inch"',
            'def' => '1 pound gravity/in^2',
            'comment' => 'exact'
        ],
        'ksi' => [
            'type' => 'unit',
            'name' => 'ksi',
            'definition' => 'unit of pressure representing the pressure exerted, due to gravity, by a 1000-pound mass, of area one square inch',
            'def' => 'kip/in^2',
            'comment' => 'exact'
        ],
        'barye' => [
            'type' => 'alias',
            'name' => 'barye',
            'def' => 'barie',
            'aliasKind' => 'alias'
        ],
        'barie' => [
            'type' => 'unit',
            'name' => 'barie',
            'definition' => 'unit of pressure equal to one dyne per square centimeter',
            'def' => '0.1 N/m^2',
            'comment' => 'exact'
        ],
        'poise' => [
            'type' => 'unit',
            'name' => 'poise',
            'definition' => 'unit of dynamic viscosity, corresponding to 0.1 pascal-second (pascal-second: a fluid placed between two plates, when one plate is pushed sideways with a shear stress of one pascal, moves a distance equal to the thickness of the layer between the plates in one second)',
            'def' => '1e-1 Pa.s',
            'comment' => 'exact'
        ],
        'St' => [
            'type' => 'alias',
            'name' => 'St',
            'def' => 'stokes',
            'aliasKind' => 'symbol'
        ],
        'stokes' => [
            'type' => 'unit',
            'name' => 'stokes',
            'definition' => 'unit of kinematic viscosity, measuring the ratio of the dynamic viscosity to the density of the fluid; water at 20 degrees C has a kinematic viscosity about 100 stokes, or more cmomonly, 1 cSt',
            'def' => '1e-4 m^2/s',
            'comment' => 'exact'
        ],
        'rhe' => [
            'type' => 'unit',
            'name' => 'rhe',
            'definition' => 'unit of fluidity (reciprocal of velocity), measured in reciprocal poise',
            'def' => '10/(Pa.s)',
            'comment' => 'exact'
        ],
        'erg' => [
            'type' => 'unit',
            'name' => 'erg',
            'definition' => 'unit of work, equal to the amount of work done by a force of one dyne exerted for a distance of one centimeter (in CGS base units, one gram centimeter-squared per second-squared)',
            'def' => '1e-7 J',
            'comment' => 'exact'
        ],
        'Btu' => [
            'type' => 'alias',
            'name' => 'Btu',
            'def' => 'IT_Btu',
            'aliasKind' => 'alias'
        ],
        'Btus' => [
            'type' => 'alias',
            'name' => 'Btus',
            'def' => 'IT_Btu',
            'aliasKind' => 'explicit_plural'
        ],
        'IT_Btu' => [
            'type' => 'unit',
            'name' => 'IT_Btu',
            'definition' => 'unit of energy, equal to the energy needed to cool or heat one pound of water by one degree F; this uses the International Steam Table (IT) calorie , defined by the Fifth International Conference on the properties of Steam (1956)',
            'plural' => 'IT_Btus',
            'def' => '1.05505585262e3 J',
            'comment' => 'exact'
        ],
        'IT_Btus' => [
            'type' => 'alias',
            'name' => 'IT_Btus',
            'def' => 'IT_Btu',
            'aliasKind' => 'explicit_plural'
        ],
        'EC_therm' => [
            'type' => 'unit',
            'name' => 'EC_therm',
            'definition' => 'unit of energy legally defined by the Council Directive of 20 December 1979, Council of the European Communities (now the European Union, EU);.roughly equal to 100,000 IT_Btu',
            'def' => '1.05506e8 J',
            'comment' => 'exact (reference NIST Guide to SI Units)'
        ],
        'thermochemical_calorie' => [
            'type' => 'unit',
            'name' => 'thermochemical_calorie',
            'definition' => 'unit of heat energy defined as 4.184 Joules exactly (International Standard ISO 31-4: Quantities and units, Part 4: Heat); approximately the energy needed to increase the temperature of 1 gram of water by 1 C',
            'def' => '4.184000 J',
            'comment' => 'exact'
        ],
        'cal' => [
            'type' => 'alias',
            'name' => 'cal',
            'def' => 'IT_calorie',
            'aliasKind' => 'symbol'
        ],
        'calorie' => [
            'type' => 'alias',
            'name' => 'calorie',
            'def' => 'IT_calorie',
            'aliasKind' => 'alias'
        ],
        'IT_calorie' => [
            'type' => 'unit',
            'name' => 'IT_calorie',
            'definition' => 'unit of heat energy used in thermochemistry, the International Steam Table (IT) calorie defined by the Fifth International Conference on the properties of Steam (1956)',
            'def' => '4.1868 J',
            'comment' => 'exact'
        ],
        'TNT' => [
            'type' => 'unit',
            'name' => 'TNT',
            'definition' => 'unit of energy; approximately the energy released by the detonation of a given amount of mass of TNT',
            'def' => '4.184 MJ/kg',
            'comment' => 'by definition'
        ],
        'ton_TNT' => [
            'type' => 'unit',
            'name' => 'ton_TNT',
            'definition' => 'unit of energy; approximately the energy released by the detonation of a 1000 kilograms of TNT',
            'plural' => 'tons_TNT',
            'def' => '4.184e9 J',
            'comment' => 'by definition'
        ],
        'tons_TNT' => [
            'type' => 'alias',
            'name' => 'tons_TNT',
            'def' => 'ton_TNT',
            'aliasKind' => 'explicit_plural'
        ],
        'thm' => [
            'type' => 'alias',
            'name' => 'thm',
            'def' => 'US_therm',
            'aliasKind' => 'symbol'
        ],
        'therm' => [
            'type' => 'alias',
            'name' => 'therm',
            'def' => 'US_therm',
            'aliasKind' => 'alias'
        ],
        'US_therm' => [
            'type' => 'unit',
            'name' => 'US_therm',
            'definition' => 'unit of energy legally defined in the U.S. Federal Register of July 27, 1968, and the legal unit used by the U.S. natural gas industry',
            'def' => '1.054804e8 J',
            'comment' => 'exact'
        ],
        'watthour' => [
            'type' => 'unit',
            'name' => 'watthour',
            'definition' => 'unit of energy equal to the product of the power in watts and the time in hours (if the energy is being transmitted or used at a constant rate (power) over a period of time); one watt is equal to 1 Joule/second',
            'def' => 'watt.hour',
            'comment' => 'exact'
        ],
        'bev' => [
            'type' => 'unit',
            'name' => 'bev',
            'definition' => 'unit of energy corresponding to 1 billion electron volts (eV)',
            'def' => '1e9 eV',
            'comment' => 'exact'
        ],
        'VA' => [
            'type' => 'alias',
            'name' => 'VA',
            'def' => 'voltampere',
            'aliasKind' => 'symbol'
        ],
        'voltampere' => [
            'type' => 'unit',
            'name' => 'voltampere',
            'definition' => 'unit of electric power equal to the product of one volt and one ampere, equivalent to one watt for direct current systems and a unit of apparent power for alternating current systems',
            'def' => 'V.A',
            'comment' => 'exact'
        ],
        'boiler_horsepower' => [
            'type' => 'unit',
            'name' => 'boiler_horsepower',
            'definition' => 'unit of power equal to the power required to evaporate 34.5 lb of fresh water at 212 degrees F in one hour; describes a boiler\'s capacity to deliver steam to a steam engine',
            'def' => '9.80950e3 W'
        ],
        'hp' => [
            'type' => 'alias',
            'name' => 'hp',
            'def' => 'shaft_horsepower',
            'aliasKind' => 'symbol'
        ],
        'horsepower' => [
            'type' => 'alias',
            'name' => 'horsepower',
            'def' => 'shaft_horsepower',
            'aliasKind' => 'alias'
        ],
        'shaft_horsepower' => [
            'type' => 'unit',
            'name' => 'shaft_horsepower',
            'definition' => 'unit of power originally corresponding to the estimated typical power of draft horses, calculated as lifting 33000 pounds one foot in one minute (550 foot-pounds/second)',
            'def' => '7.456999e2 W',
            'comment' => 'shaft_horsepower is a unit of power as delivered by a drive shaft at its output (e.g., of a ship, aircraft engine, or helicopter rotor; typically not automobiles due to drive train losses)'
        ],
        'metric_horsepower' => [
            'type' => 'unit',
            'name' => 'metric_horsepower',
            'definition' => 'unit of power corresponding to a calculation of 75 kilogram-meters/second',
            'def' => '7.35499e2 W'
        ],
        'electric_horsepower' => [
            'type' => 'unit',
            'name' => 'electric_horsepower',
            'definition' => 'unit of power defined in the International System of Units as exactly 746 W; generally used for power used by electrical machines',
            'def' => '7.460000e2 W',
            'comment' => 'exact'
        ],
        'water_horsepower' => [
            'type' => 'unit',
            'name' => 'water_horsepower',
            'definition' => 'unit of power used in the U.S. primarily in rating pumps; calculated as pump capacity Q (gallons per minute) times pump pressure ("head") of P (feet of head), divided by 3956 water horsepower; the calculation assumes water density is 8 1/3 pounds per U.S. gallon, which is not exact.',
            'def' => '7.46043e2 W'
        ],
        'UK_horsepower' => [
            'type' => 'unit',
            'name' => 'UK_horsepower',
            'definition' => 'unit of power originally corresponding to the estimated typical power of draft horses, calculated as lifting 33000 pounds one foot in one minute (550 foot-pounds/second), as calculated/specified in the United Kingdom',
            'def' => '7.4570e2 W'
        ],
        'ton_of_refrigeration' => [
            'type' => 'alias',
            'name' => 'ton_of_refrigeration',
            'def' => 'refrigeration_ton',
            'aliasKind' => 'alias'
        ],
        'tons_of_refrigeration' => [
            'type' => 'alias',
            'name' => 'tons_of_refrigeration',
            'def' => 'refrigeration_ton',
            'aliasKind' => 'explicit_plural'
        ],
        'refrigeration_ton' => [
            'type' => 'unit',
            'name' => 'refrigeration_ton',
            'definition' => 'unit of power describing the heat-extraction capacity of cooling equipment; defined as the heat absorbed by melting 1 short ton of pure ice at 0 degrees C in 24 hours',
            'def' => '12000 Btu/hr'
        ],
        'clo' => [
            'type' => 'unit',
            'name' => 'clo',
            'definition' => 'unit of thermal resistance used in describing the insulating value of clothing; the amount of thermal resistance needed to maintain in comfort a resting subject in a normally ventilated room (air movement 10 cm/sec) at a temperature of 20 degrees C and a humidity less than 50%',
            'def' => '1.55e-1 K.m^2/W'
        ],
        'abampere' => [
            'type' => 'unit',
            'name' => 'abampere',
            'definition' => 'basic unit of electricity in the electromagnetic CGS system of units',
            'def' => '10 A'
        ],
        'gilbert' => [
            'type' => 'unit',
            'name' => 'gilbert',
            'definition' => 'unit of electricity/magnetism',
            'def' => '7.957747e-1 A'
        ],
        'statampere' => [
            'type' => 'unit',
            'name' => 'statampere',
            'definition' => 'unit of electricity/magnetism',
            'def' => '3.335640e-10 A'
        ],
        'biot' => [
            'type' => 'unit',
            'name' => 'biot',
            'definition' => 'basic unit of electricity in the electromagnetic CGS system of units (same as abampere), named after Jean-Baptiste Biot',
            'def' => '10 A'
        ],
        'abfarad' => [
            'type' => 'unit',
            'name' => 'abfarad',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1e9 F',
            'comment' => 'exact'
        ],
        'abhenry' => [
            'type' => 'unit',
            'name' => 'abhenry',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1e-9 H',
            'comment' => 'exact'
        ],
        'abmho' => [
            'type' => 'unit',
            'name' => 'abmho',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1e9 S',
            'comment' => 'exact'
        ],
        'abohm' => [
            'type' => 'unit',
            'name' => 'abohm',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1e-9 ohm',
            'comment' => 'exact'
        ],
        'abvolt' => [
            'type' => 'unit',
            'name' => 'abvolt',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1e-8 V',
            'comment' => 'exact'
        ],
        'e' => [
            'type' => 'unit',
            'name' => 'e',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1.602176487e-19 C'
        ],
        'chemical_faraday' => [
            'type' => 'unit',
            'name' => 'chemical_faraday',
            'definition' => 'unit of electricity/magnetism',
            'def' => '9.64957e4 C'
        ],
        'physical_faraday' => [
            'type' => 'unit',
            'name' => 'physical_faraday',
            'definition' => 'unit of electricity/magnetism',
            'def' => '9.65219e4 C'
        ],
        'faraday' => [
            'type' => 'alias',
            'name' => 'faraday',
            'def' => 'C12_faraday',
            'aliasKind' => 'alias'
        ],
        'C12_faraday' => [
            'type' => 'unit',
            'name' => 'C12_faraday',
            'definition' => 'unit of electricity/magnetism',
            'def' => '9.648531e4 C'
        ],
        'gamma' => [
            'type' => 'unit',
            'name' => 'gamma',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1e-9 T',
            'comment' => 'exact'
        ],
        'gauss' => [
            'type' => 'unit',
            'name' => 'gauss',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1e-4 T',
            'comment' => 'exact'
        ],
        'maxwell' => [
            'type' => 'unit',
            'name' => 'maxwell',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1e-8 Wb',
            'comment' => 'exact'
        ],
        'Oe' => [
            'type' => 'alias',
            'name' => 'Oe',
            'def' => 'oersted',
            'aliasKind' => 'symbol'
        ],
        'oersted' => [
            'type' => 'unit',
            'name' => 'oersted',
            'definition' => 'unit of electricity/magnetism',
            'def' => '7.957747e1 A/m'
        ],
        'statcoulomb' => [
            'type' => 'unit',
            'name' => 'statcoulomb',
            'definition' => 'unit of electricity/magnetism',
            'def' => '3.335640e-10 C'
        ],
        'statfarad' => [
            'type' => 'unit',
            'name' => 'statfarad',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1.112650e-12 F'
        ],
        'stathenry' => [
            'type' => 'unit',
            'name' => 'stathenry',
            'definition' => 'unit of electricity/magnetism',
            'def' => '8.987554e11 H'
        ],
        'statmho' => [
            'type' => 'unit',
            'name' => 'statmho',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1.112650e-12 S'
        ],
        'statohm' => [
            'type' => 'unit',
            'name' => 'statohm',
            'definition' => 'unit of electricity/magnetism',
            'def' => '8.987554e11 ohm'
        ],
        'statvolt' => [
            'type' => 'unit',
            'name' => 'statvolt',
            'definition' => 'unit of electricity/magnetism',
            'def' => '2.997925e2 V'
        ],
        'unit_pole' => [
            'type' => 'unit',
            'name' => 'unit_pole',
            'definition' => 'unit of electricity/magnetism',
            'def' => '1.256637e-7 Wb'
        ],
        '°R' => [
            'type' => 'alias',
            'name' => '°R',
            'def' => 'degree_rankine',
            'aliasKind' => 'symbol'
        ],
        'degreeR' => [
            'type' => 'alias',
            'name' => 'degreeR',
            'def' => 'degree_rankine',
            'aliasKind' => 'alias'
        ],
        'degreesR' => [
            'type' => 'alias',
            'name' => 'degreesR',
            'def' => 'degree_rankine',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_R' => [
            'type' => 'alias',
            'name' => 'degree_R',
            'def' => 'degree_rankine',
            'aliasKind' => 'alias'
        ],
        'degrees_R' => [
            'type' => 'alias',
            'name' => 'degrees_R',
            'def' => 'degree_rankine',
            'aliasKind' => 'explicit_plural'
        ],
        'degR' => [
            'type' => 'alias',
            'name' => 'degR',
            'def' => 'degree_rankine',
            'aliasKind' => 'alias'
        ],
        'degsR' => [
            'type' => 'alias',
            'name' => 'degsR',
            'def' => 'degree_rankine',
            'aliasKind' => 'explicit_plural'
        ],
        'deg_R' => [
            'type' => 'alias',
            'name' => 'deg_R',
            'def' => 'degree_rankine',
            'aliasKind' => 'alias'
        ],
        'degs_R' => [
            'type' => 'alias',
            'name' => 'degs_R',
            'def' => 'degree_rankine',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_rankine' => [
            'type' => 'unit',
            'name' => 'degree_rankine',
            'definition' => 'unit of thermodynamic temperature',
            'plural' => 'degrees_rankine',
            'def' => 'K/1.8'
        ],
        'degrees_rankine' => [
            'type' => 'alias',
            'name' => 'degrees_rankine',
            'def' => 'degree_rankine',
            'aliasKind' => 'explicit_plural'
        ],
        '°F' => [
            'type' => 'alias',
            'name' => '°F',
            'def' => 'fahrenheit',
            'aliasKind' => 'symbol'
        ],
        '℉' => [
            'type' => 'alias',
            'name' => '℉',
            'def' => 'fahrenheit',
            'aliasKind' => 'symbol'
        ],
        'degree_fahrenheit' => [
            'type' => 'alias',
            'name' => 'degree_fahrenheit',
            'def' => 'fahrenheit',
            'aliasKind' => 'alias'
        ],
        'degrees_fahrenheit' => [
            'type' => 'alias',
            'name' => 'degrees_fahrenheit',
            'def' => 'fahrenheit',
            'aliasKind' => 'explicit_plural'
        ],
        'degreeF' => [
            'type' => 'alias',
            'name' => 'degreeF',
            'def' => 'fahrenheit',
            'aliasKind' => 'alias'
        ],
        'degreesF' => [
            'type' => 'alias',
            'name' => 'degreesF',
            'def' => 'fahrenheit',
            'aliasKind' => 'explicit_plural'
        ],
        'degree_F' => [
            'type' => 'alias',
            'name' => 'degree_F',
            'def' => 'fahrenheit',
            'aliasKind' => 'alias'
        ],
        'degrees_F' => [
            'type' => 'alias',
            'name' => 'degrees_F',
            'def' => 'fahrenheit',
            'aliasKind' => 'explicit_plural'
        ],
        'degF' => [
            'type' => 'alias',
            'name' => 'degF',
            'def' => 'fahrenheit',
            'aliasKind' => 'alias'
        ],
        'degsF' => [
            'type' => 'alias',
            'name' => 'degsF',
            'def' => 'fahrenheit',
            'aliasKind' => 'explicit_plural'
        ],
        'deg_F' => [
            'type' => 'alias',
            'name' => 'deg_F',
            'def' => 'fahrenheit',
            'aliasKind' => 'alias'
        ],
        'degs_F' => [
            'type' => 'alias',
            'name' => 'degs_F',
            'def' => 'fahrenheit',
            'aliasKind' => 'explicit_plural'
        ],
        'fahrenheit' => [
            'type' => 'unit',
            'name' => 'fahrenheit',
            'definition' => 'unit of thermodynamic temperature',
            'def' => '°R @ 459.67'
        ],
        'footcandle' => [
            'type' => 'unit',
            'name' => 'footcandle',
            'definition' => 'unit of illumination',
            'def' => '1.076391e-1 lx'
        ],
        'footlambert' => [
            'type' => 'unit',
            'name' => 'footlambert',
            'definition' => 'unit of illumination',
            'def' => '3.426259 cd/m^2',
            'comment' => 'exact'
        ],
        'lambert' => [
            'type' => 'unit',
            'name' => 'lambert',
            'definition' => 'unit of illumination',
            'def' => '(1e4/pi) cd/m^2',
            'comment' => 'exact'
        ],
        'sb' => [
            'type' => 'alias',
            'name' => 'sb',
            'def' => 'stilb',
            'aliasKind' => 'symbol'
        ],
        'stilb' => [
            'type' => 'unit',
            'name' => 'stilb',
            'definition' => 'unit of illumination',
            'def' => '1e4 cd/m^2',
            'comment' => 'exact'
        ],
        'ph' => [
            'type' => 'alias',
            'name' => 'ph',
            'def' => 'phot',
            'aliasKind' => 'symbol'
        ],
        'phot' => [
            'type' => 'unit',
            'name' => 'phot',
            'definition' => 'unit of illumination',
            'def' => '1e4 lm/m^2',
            'comment' => 'exact'
        ],
        'nt' => [
            'type' => 'alias',
            'name' => 'nt',
            'def' => 'nit',
            'aliasKind' => 'symbol'
        ],
        'nit' => [
            'type' => 'unit',
            'name' => 'nit',
            'definition' => 'unit of illumination',
            'def' => '1 cd/m^2',
            'comment' => 'exact'
        ],
        'langley' => [
            'type' => 'unit',
            'name' => 'langley',
            'definition' => 'unit of illumination',
            'def' => '4.184000e4 J/m^2',
            'comment' => 'exact'
        ],
        'apostilb' => [
            'type' => 'alias',
            'name' => 'apostilb',
            'def' => 'blondel',
            'aliasKind' => 'alias'
        ],
        'blondel' => [
            'type' => 'unit',
            'name' => 'blondel',
            'definition' => 'unit of illumination',
            'def' => 'cd/(pi m^2)',
            'comment' => 'exact'
        ],
        'kayser' => [
            'type' => 'unit',
            'name' => 'kayser',
            'definition' => '',
            'def' => '100/m',
            'comment' => 'exact'
        ],
        'gp' => [
            'type' => 'alias',
            'name' => 'gp',
            'def' => 'geopotential',
            'aliasKind' => 'symbol'
        ],
        'dynamic' => [
            'type' => 'alias',
            'name' => 'dynamic',
            'def' => 'geopotential',
            'aliasKind' => 'alias'
        ],
        'geopotential' => [
            'type' => 'unit',
            'name' => 'geopotential',
            'definition' => '',
            'def' => 'gravity',
            'comment' => 'exact'
        ],
        'work_year' => [
            'type' => 'unit',
            'name' => 'work_year',
            'definition' => '',
            'def' => '2056 hours',
            'comment' => 'exact'
        ],
        'work_month' => [
            'type' => 'unit',
            'name' => 'work_month',
            'definition' => '',
            'def' => 'work_year/12',
            'comment' => 'exact'
        ],
        'PVU' => [
            'type' => 'alias',
            'name' => 'PVU',
            'def' => 'potential_vorticity_unit',
            'aliasKind' => 'symbol'
        ],
        'potential_vorticity_unit' => [
            'type' => 'unit',
            'name' => 'potential_vorticity_unit',
            'definition' => '',
            'def' => '1e-6 m^2 s^-1 K kg^-1',
            'comment' => 'exact'
        ],
        'count' => [
            'type' => 'unit',
            'name' => 'count',
            'definition' => '',
            'def' => '1'
        ],
        'bit' => [
            'type' => 'unit',
            'name' => 'bit',
            'definition' => '',
            'def' => '1'
        ],
        'byte' => [
            'type' => 'alias',
            'name' => 'byte',
            'def' => 'octet',
            'aliasKind' => 'alias'
        ],
        'octet' => [
            'type' => 'unit',
            'name' => 'octet',
            'definition' => '',
            'def' => '8'
        ],
        'DU' => [
            'type' => 'alias',
            'name' => 'DU',
            'def' => 'dobson',
            'aliasKind' => 'symbol'
        ],
        'dobson' => [
            'type' => 'unit',
            'name' => 'dobson',
            'definition' => '',
            'def' => '446.2 micromoles/meter^2'
        ],
        'molec' => [
            'type' => 'alias',
            'name' => 'molec',
            'def' => 'molecule',
            'aliasKind' => 'alias'
        ],
        'nucleon' => [
            'type' => 'alias',
            'name' => 'nucleon',
            'def' => 'molecule',
            'aliasKind' => 'alias'
        ],
        'nuc' => [
            'type' => 'alias',
            'name' => 'nuc',
            'def' => 'molecule',
            'aliasKind' => 'alias'
        ],
        'molecule' => [
            'type' => 'unit',
            'name' => 'molecule',
            'definition' => '',
            'def' => '1/avogadro_constant'
        ],
        'metres' => [
            'type' => 'alias',
            'name' => 'metres',
            'def' => 'meter',
            'aliasKind' => 'generated_plural'
        ],
        'meters' => [
            'type' => 'alias',
            'name' => 'meters',
            'def' => 'meter',
            'aliasKind' => 'generated_plural'
        ],
        'kilograms' => [
            'type' => 'alias',
            'name' => 'kilograms',
            'def' => 'kilogram',
            'aliasKind' => 'generated_plural'
        ],
        'seconds' => [
            'type' => 'alias',
            'name' => 'seconds',
            'def' => 'second',
            'aliasKind' => 'generated_plural'
        ],
        'amperes' => [
            'type' => 'alias',
            'name' => 'amperes',
            'def' => 'ampere',
            'aliasKind' => 'generated_plural'
        ],
        'kelvins' => [
            'type' => 'alias',
            'name' => 'kelvins',
            'def' => 'kelvin',
            'aliasKind' => 'generated_plural'
        ],
        'moles' => [
            'type' => 'alias',
            'name' => 'moles',
            'def' => 'mole',
            'aliasKind' => 'generated_plural'
        ],
        'candelas' => [
            'type' => 'alias',
            'name' => 'candelas',
            'def' => 'candela',
            'aliasKind' => 'generated_plural'
        ],
        'radians' => [
            'type' => 'alias',
            'name' => 'radians',
            'def' => 'radian',
            'aliasKind' => 'generated_plural'
        ],
        'steradians' => [
            'type' => 'alias',
            'name' => 'steradians',
            'def' => 'steradian',
            'aliasKind' => 'generated_plural'
        ],
        'hertzes' => [
            'type' => 'alias',
            'name' => 'hertzes',
            'def' => 'hertz',
            'aliasKind' => 'generated_plural'
        ],
        'grams' => [
            'type' => 'alias',
            'name' => 'grams',
            'def' => 'gram',
            'aliasKind' => 'generated_plural'
        ],
        'newtons' => [
            'type' => 'alias',
            'name' => 'newtons',
            'def' => 'newton',
            'aliasKind' => 'generated_plural'
        ],
        'pascals' => [
            'type' => 'alias',
            'name' => 'pascals',
            'def' => 'pascal',
            'aliasKind' => 'generated_plural'
        ],
        'joules' => [
            'type' => 'alias',
            'name' => 'joules',
            'def' => 'joule',
            'aliasKind' => 'generated_plural'
        ],
        'watts' => [
            'type' => 'alias',
            'name' => 'watts',
            'def' => 'watt',
            'aliasKind' => 'generated_plural'
        ],
        'coulombs' => [
            'type' => 'alias',
            'name' => 'coulombs',
            'def' => 'coulomb',
            'aliasKind' => 'generated_plural'
        ],
        'volts' => [
            'type' => 'alias',
            'name' => 'volts',
            'def' => 'volt',
            'aliasKind' => 'generated_plural'
        ],
        'farads' => [
            'type' => 'alias',
            'name' => 'farads',
            'def' => 'farad',
            'aliasKind' => 'generated_plural'
        ],
        'ohms' => [
            'type' => 'alias',
            'name' => 'ohms',
            'def' => 'ohm',
            'aliasKind' => 'generated_plural'
        ],
        'siemenses' => [
            'type' => 'alias',
            'name' => 'siemenses',
            'def' => 'siemens',
            'aliasKind' => 'generated_plural'
        ],
        'webers' => [
            'type' => 'alias',
            'name' => 'webers',
            'def' => 'weber',
            'aliasKind' => 'generated_plural'
        ],
        'teslas' => [
            'type' => 'alias',
            'name' => 'teslas',
            'def' => 'tesla',
            'aliasKind' => 'generated_plural'
        ],
        'henries' => [
            'type' => 'alias',
            'name' => 'henries',
            'def' => 'henry',
            'aliasKind' => 'generated_plural'
        ],
        'lumens' => [
            'type' => 'alias',
            'name' => 'lumens',
            'def' => 'lumen',
            'aliasKind' => 'generated_plural'
        ],
        'luxes' => [
            'type' => 'alias',
            'name' => 'luxes',
            'def' => 'lux',
            'aliasKind' => 'generated_plural'
        ],
        'katals' => [
            'type' => 'alias',
            'name' => 'katals',
            'def' => 'katal',
            'aliasKind' => 'generated_plural'
        ],
        'becquerels' => [
            'type' => 'alias',
            'name' => 'becquerels',
            'def' => 'becquerel',
            'aliasKind' => 'generated_plural'
        ],
        'grays' => [
            'type' => 'alias',
            'name' => 'grays',
            'def' => 'gray',
            'aliasKind' => 'generated_plural'
        ],
        'sieverts' => [
            'type' => 'alias',
            'name' => 'sieverts',
            'def' => 'sievert',
            'aliasKind' => 'generated_plural'
        ],
        'minutes' => [
            'type' => 'alias',
            'name' => 'minutes',
            'def' => 'minute',
            'aliasKind' => 'generated_plural'
        ],
        'hours' => [
            'type' => 'alias',
            'name' => 'hours',
            'def' => 'hour',
            'aliasKind' => 'generated_plural'
        ],
        'days' => [
            'type' => 'alias',
            'name' => 'days',
            'def' => 'day',
            'aliasKind' => 'generated_plural'
        ],
        'angular_degrees' => [
            'type' => 'alias',
            'name' => 'angular_degrees',
            'def' => 'arc_degree',
            'aliasKind' => 'generated_plural'
        ],
        'degrees' => [
            'type' => 'alias',
            'name' => 'degrees',
            'def' => 'arc_degree',
            'aliasKind' => 'generated_plural'
        ],
        'arcdegs' => [
            'type' => 'alias',
            'name' => 'arcdegs',
            'def' => 'arc_degree',
            'aliasKind' => 'generated_plural'
        ],
        'arc_degrees' => [
            'type' => 'alias',
            'name' => 'arc_degrees',
            'def' => 'arc_degree',
            'aliasKind' => 'generated_plural'
        ],
        'angular_minutes' => [
            'type' => 'alias',
            'name' => 'angular_minutes',
            'def' => 'arc_minute',
            'aliasKind' => 'generated_plural'
        ],
        'arcminutes' => [
            'type' => 'alias',
            'name' => 'arcminutes',
            'def' => 'arc_minute',
            'aliasKind' => 'generated_plural'
        ],
        'arcmins' => [
            'type' => 'alias',
            'name' => 'arcmins',
            'def' => 'arc_minute',
            'aliasKind' => 'generated_plural'
        ],
        'arc_minutes' => [
            'type' => 'alias',
            'name' => 'arc_minutes',
            'def' => 'arc_minute',
            'aliasKind' => 'generated_plural'
        ],
        'angular_seconds' => [
            'type' => 'alias',
            'name' => 'angular_seconds',
            'def' => 'arc_second',
            'aliasKind' => 'generated_plural'
        ],
        'arcseconds' => [
            'type' => 'alias',
            'name' => 'arcseconds',
            'def' => 'arc_second',
            'aliasKind' => 'generated_plural'
        ],
        'arcsecs' => [
            'type' => 'alias',
            'name' => 'arcsecs',
            'def' => 'arc_second',
            'aliasKind' => 'generated_plural'
        ],
        'arc_seconds' => [
            'type' => 'alias',
            'name' => 'arc_seconds',
            'def' => 'arc_second',
            'aliasKind' => 'generated_plural'
        ],
        'litres' => [
            'type' => 'alias',
            'name' => 'litres',
            'def' => 'liter',
            'aliasKind' => 'generated_plural'
        ],
        'liters' => [
            'type' => 'alias',
            'name' => 'liters',
            'def' => 'liter',
            'aliasKind' => 'generated_plural'
        ],
        'tonnes' => [
            'type' => 'alias',
            'name' => 'tonnes',
            'def' => 'metric_ton',
            'aliasKind' => 'generated_plural'
        ],
        'metric_tons' => [
            'type' => 'alias',
            'name' => 'metric_tons',
            'def' => 'metric_ton',
            'aliasKind' => 'generated_plural'
        ],
        'electron_volts' => [
            'type' => 'alias',
            'name' => 'electron_volts',
            'def' => 'electronvolt',
            'aliasKind' => 'generated_plural'
        ],
        'electronvolts' => [
            'type' => 'alias',
            'name' => 'electronvolts',
            'def' => 'electronvolt',
            'aliasKind' => 'generated_plural'
        ],
        'atomic_mass_units' => [
            'type' => 'alias',
            'name' => 'atomic_mass_units',
            'def' => 'unified_atomic_mass_unit',
            'aliasKind' => 'generated_plural'
        ],
        'atomicmassunits' => [
            'type' => 'alias',
            'name' => 'atomicmassunits',
            'def' => 'unified_atomic_mass_unit',
            'aliasKind' => 'generated_plural'
        ],
        'unified_atomic_mass_units' => [
            'type' => 'alias',
            'name' => 'unified_atomic_mass_units',
            'def' => 'unified_atomic_mass_unit',
            'aliasKind' => 'generated_plural'
        ],
        'astronomical_units' => [
            'type' => 'alias',
            'name' => 'astronomical_units',
            'def' => 'astronomical_unit',
            'aliasKind' => 'generated_plural'
        ],
        'nautical_miles' => [
            'type' => 'alias',
            'name' => 'nautical_miles',
            'def' => 'nautical_mile',
            'aliasKind' => 'generated_plural'
        ],
        'knot_internationals' => [
            'type' => 'alias',
            'name' => 'knot_internationals',
            'def' => 'international_knot',
            'aliasKind' => 'generated_plural'
        ],
        'knots' => [
            'type' => 'alias',
            'name' => 'knots',
            'def' => 'international_knot',
            'aliasKind' => 'generated_plural'
        ],
        'international_knots' => [
            'type' => 'alias',
            'name' => 'international_knots',
            'def' => 'international_knot',
            'aliasKind' => 'generated_plural'
        ],
        'angstroms' => [
            'type' => 'alias',
            'name' => 'angstroms',
            'def' => 'angstrom',
            'aliasKind' => 'generated_plural'
        ],
        'ares' => [
            'type' => 'alias',
            'name' => 'ares',
            'def' => 'are',
            'aliasKind' => 'generated_plural'
        ],
        'hectares' => [
            'type' => 'alias',
            'name' => 'hectares',
            'def' => 'hectare',
            'aliasKind' => 'generated_plural'
        ],
        'barns' => [
            'type' => 'alias',
            'name' => 'barns',
            'def' => 'barn',
            'aliasKind' => 'generated_plural'
        ],
        'bars' => [
            'type' => 'alias',
            'name' => 'bars',
            'def' => 'bar',
            'aliasKind' => 'generated_plural'
        ],
        'gals' => [
            'type' => 'alias',
            'name' => 'gals',
            'def' => 'gal',
            'aliasKind' => 'generated_plural'
        ],
        'curies' => [
            'type' => 'alias',
            'name' => 'curies',
            'def' => 'curie',
            'aliasKind' => 'generated_plural'
        ],
        'roentgens' => [
            'type' => 'alias',
            'name' => 'roentgens',
            'def' => 'roentgen',
            'aliasKind' => 'generated_plural'
        ],
        'rems' => [
            'type' => 'alias',
            'name' => 'rems',
            'def' => 'rem',
            'aliasKind' => 'generated_plural'
        ],
        'secs' => [
            'type' => 'alias',
            'name' => 'secs',
            'def' => 'sec',
            'aliasKind' => 'generated_plural'
        ],
        'amps' => [
            'type' => 'alias',
            'name' => 'amps',
            'def' => 'amp',
            'aliasKind' => 'generated_plural'
        ],
        'candles' => [
            'type' => 'alias',
            'name' => 'candles',
            'def' => 'candle',
            'aliasKind' => 'generated_plural'
        ],
        'einsteins' => [
            'type' => 'alias',
            'name' => 'einsteins',
            'def' => 'einstein',
            'aliasKind' => 'generated_plural'
        ],
        'bauds' => [
            'type' => 'alias',
            'name' => 'bauds',
            'def' => 'baud',
            'aliasKind' => 'generated_plural'
        ],
        'celsiuses' => [
            'type' => 'alias',
            'name' => 'celsiuses',
            'def' => 'celsius',
            'aliasKind' => 'generated_plural'
        ],
        'grades' => [
            'type' => 'alias',
            'name' => 'grades',
            'def' => 'grade',
            'aliasKind' => 'generated_plural'
        ],
        'cycles' => [
            'type' => 'alias',
            'name' => 'cycles',
            'def' => 'circle',
            'aliasKind' => 'generated_plural'
        ],
        'turns' => [
            'type' => 'alias',
            'name' => 'turns',
            'def' => 'circle',
            'aliasKind' => 'generated_plural'
        ],
        'revolutions' => [
            'type' => 'alias',
            'name' => 'revolutions',
            'def' => 'circle',
            'aliasKind' => 'generated_plural'
        ],
        'rotations' => [
            'type' => 'alias',
            'name' => 'rotations',
            'def' => 'circle',
            'aliasKind' => 'generated_plural'
        ],
        'circles' => [
            'type' => 'alias',
            'name' => 'circles',
            'def' => 'circle',
            'aliasKind' => 'generated_plural'
        ],
        'assay_tons' => [
            'type' => 'alias',
            'name' => 'assay_tons',
            'def' => 'assay_ton',
            'aliasKind' => 'generated_plural'
        ],
        'avoirdupois_ounces' => [
            'type' => 'alias',
            'name' => 'avoirdupois_ounces',
            'def' => 'avoirdupois_ounce',
            'aliasKind' => 'generated_plural'
        ],
        'pounds' => [
            'type' => 'alias',
            'name' => 'pounds',
            'def' => 'avoirdupois_pound',
            'aliasKind' => 'generated_plural'
        ],
        'avoirdupois_pounds' => [
            'type' => 'alias',
            'name' => 'avoirdupois_pounds',
            'def' => 'avoirdupois_pound',
            'aliasKind' => 'generated_plural'
        ],
        'carats' => [
            'type' => 'alias',
            'name' => 'carats',
            'def' => 'carat',
            'aliasKind' => 'generated_plural'
        ],
        'grains' => [
            'type' => 'alias',
            'name' => 'grains',
            'def' => 'grain',
            'aliasKind' => 'generated_plural'
        ],
        'long_hundredweights' => [
            'type' => 'alias',
            'name' => 'long_hundredweights',
            'def' => 'long_hundredweight',
            'aliasKind' => 'generated_plural'
        ],
        'pennyweights' => [
            'type' => 'alias',
            'name' => 'pennyweights',
            'def' => 'pennyweight',
            'aliasKind' => 'generated_plural'
        ],
        'short_hundredweights' => [
            'type' => 'alias',
            'name' => 'short_hundredweights',
            'def' => 'short_hundredweight',
            'aliasKind' => 'generated_plural'
        ],
        'slugs' => [
            'type' => 'alias',
            'name' => 'slugs',
            'def' => 'slug',
            'aliasKind' => 'generated_plural'
        ],
        'apothecary_ounces' => [
            'type' => 'alias',
            'name' => 'apothecary_ounces',
            'def' => 'troy_ounce',
            'aliasKind' => 'generated_plural'
        ],
        'troy_ounces' => [
            'type' => 'alias',
            'name' => 'troy_ounces',
            'def' => 'troy_ounce',
            'aliasKind' => 'generated_plural'
        ],
        'apothecary_pounds' => [
            'type' => 'alias',
            'name' => 'apothecary_pounds',
            'def' => 'troy_pound',
            'aliasKind' => 'generated_plural'
        ],
        'troy_pounds' => [
            'type' => 'alias',
            'name' => 'troy_pounds',
            'def' => 'troy_pound',
            'aliasKind' => 'generated_plural'
        ],
        'scruples' => [
            'type' => 'alias',
            'name' => 'scruples',
            'def' => 'scruple',
            'aliasKind' => 'generated_plural'
        ],
        'apdrams' => [
            'type' => 'alias',
            'name' => 'apdrams',
            'def' => 'apdram',
            'aliasKind' => 'generated_plural'
        ],
        'drams' => [
            'type' => 'alias',
            'name' => 'drams',
            'def' => 'dram',
            'aliasKind' => 'generated_plural'
        ],
        'apounces' => [
            'type' => 'alias',
            'name' => 'apounces',
            'def' => 'apounce',
            'aliasKind' => 'generated_plural'
        ],
        'appounds' => [
            'type' => 'alias',
            'name' => 'appounds',
            'def' => 'appound',
            'aliasKind' => 'generated_plural'
        ],
        'bags' => [
            'type' => 'alias',
            'name' => 'bags',
            'def' => 'bag',
            'aliasKind' => 'generated_plural'
        ],
        'tons' => [
            'type' => 'alias',
            'name' => 'tons',
            'def' => 'short_ton',
            'aliasKind' => 'generated_plural'
        ],
        'short_tons' => [
            'type' => 'alias',
            'name' => 'short_tons',
            'def' => 'short_ton',
            'aliasKind' => 'generated_plural'
        ],
        'long_tons' => [
            'type' => 'alias',
            'name' => 'long_tons',
            'def' => 'long_ton',
            'aliasKind' => 'generated_plural'
        ],
        'fermis' => [
            'type' => 'alias',
            'name' => 'fermis',
            'def' => 'fermi',
            'aliasKind' => 'generated_plural'
        ],
        'light_years' => [
            'type' => 'alias',
            'name' => 'light_years',
            'def' => 'light_year',
            'aliasKind' => 'generated_plural'
        ],
        'microns' => [
            'type' => 'alias',
            'name' => 'microns',
            'def' => 'micron',
            'aliasKind' => 'generated_plural'
        ],
        'mils' => [
            'type' => 'alias',
            'name' => 'mils',
            'def' => 'mil',
            'aliasKind' => 'generated_plural'
        ],
        'parsecs' => [
            'type' => 'alias',
            'name' => 'parsecs',
            'def' => 'parsec',
            'aliasKind' => 'generated_plural'
        ],
        'printers_points' => [
            'type' => 'alias',
            'name' => 'printers_points',
            'def' => 'printers_point',
            'aliasKind' => 'generated_plural'
        ],
        'chains' => [
            'type' => 'alias',
            'name' => 'chains',
            'def' => 'chain',
            'aliasKind' => 'generated_plural'
        ],
        'picas' => [
            'type' => 'alias',
            'name' => 'picas',
            'def' => 'printers_pica',
            'aliasKind' => 'generated_plural'
        ],
        'printers_picas' => [
            'type' => 'alias',
            'name' => 'printers_picas',
            'def' => 'printers_pica',
            'aliasKind' => 'generated_plural'
        ],
        'nmiles' => [
            'type' => 'alias',
            'name' => 'nmiles',
            'def' => 'nmile',
            'aliasKind' => 'generated_plural'
        ],
        'poles' => [
            'type' => 'alias',
            'name' => 'poles',
            'def' => 'rod',
            'aliasKind' => 'generated_plural'
        ],
        'perches' => [
            'type' => 'alias',
            'name' => 'perches',
            'def' => 'rod',
            'aliasKind' => 'generated_plural'
        ],
        'rods' => [
            'type' => 'alias',
            'name' => 'rods',
            'def' => 'rod',
            'aliasKind' => 'generated_plural'
        ],
        'furlongs' => [
            'type' => 'alias',
            'name' => 'furlongs',
            'def' => 'furlong',
            'aliasKind' => 'generated_plural'
        ],
        'fathoms' => [
            'type' => 'alias',
            'name' => 'fathoms',
            'def' => 'fathom',
            'aliasKind' => 'generated_plural'
        ],
        'inches' => [
            'type' => 'alias',
            'name' => 'inches',
            'def' => 'international_inch',
            'aliasKind' => 'generated_plural'
        ],
        'international_inches' => [
            'type' => 'alias',
            'name' => 'international_inches',
            'def' => 'international_inch',
            'aliasKind' => 'generated_plural'
        ],
        'yards' => [
            'type' => 'alias',
            'name' => 'yards',
            'def' => 'international_yard',
            'aliasKind' => 'generated_plural'
        ],
        'international_yards' => [
            'type' => 'alias',
            'name' => 'international_yards',
            'def' => 'international_yard',
            'aliasKind' => 'generated_plural'
        ],
        'miles' => [
            'type' => 'alias',
            'name' => 'miles',
            'def' => 'international_mile',
            'aliasKind' => 'generated_plural'
        ],
        'international_miles' => [
            'type' => 'alias',
            'name' => 'international_miles',
            'def' => 'international_mile',
            'aliasKind' => 'generated_plural'
        ],
        'big_points' => [
            'type' => 'alias',
            'name' => 'big_points',
            'def' => 'big_point',
            'aliasKind' => 'generated_plural'
        ],
        'barleycorns' => [
            'type' => 'alias',
            'name' => 'barleycorns',
            'def' => 'barleycorn',
            'aliasKind' => 'generated_plural'
        ],
        'arpentlins' => [
            'type' => 'alias',
            'name' => 'arpentlins',
            'def' => 'arpentlin',
            'aliasKind' => 'generated_plural'
        ],
        'deniers' => [
            'type' => 'alias',
            'name' => 'deniers',
            'def' => 'denier',
            'aliasKind' => 'generated_plural'
        ],
        'texes' => [
            'type' => 'alias',
            'name' => 'texes',
            'def' => 'tex',
            'aliasKind' => 'generated_plural'
        ],
        'circular_mils' => [
            'type' => 'alias',
            'name' => 'circular_mils',
            'def' => 'circular_mil',
            'aliasKind' => 'generated_plural'
        ],
        'darcies' => [
            'type' => 'alias',
            'name' => 'darcies',
            'def' => 'darcy',
            'aliasKind' => 'generated_plural'
        ],
        'acres' => [
            'type' => 'alias',
            'name' => 'acres',
            'def' => 'acre',
            'aliasKind' => 'generated_plural'
        ],
        'bushels' => [
            'type' => 'alias',
            'name' => 'bushels',
            'def' => 'bushel',
            'aliasKind' => 'generated_plural'
        ],
        'pecks' => [
            'type' => 'alias',
            'name' => 'pecks',
            'def' => 'peck',
            'aliasKind' => 'generated_plural'
        ],
        'steres' => [
            'type' => 'alias',
            'name' => 'steres',
            'def' => 'stere',
            'aliasKind' => 'generated_plural'
        ],
        'register_tons' => [
            'type' => 'alias',
            'name' => 'register_tons',
            'def' => 'register_ton',
            'aliasKind' => 'generated_plural'
        ],
        'dry_quarts' => [
            'type' => 'alias',
            'name' => 'dry_quarts',
            'def' => 'US_dry_quart',
            'aliasKind' => 'generated_plural'
        ],
        'dry_pints' => [
            'type' => 'alias',
            'name' => 'dry_pints',
            'def' => 'US_dry_pint',
            'aliasKind' => 'generated_plural'
        ],
        'liquid_gallons' => [
            'type' => 'alias',
            'name' => 'liquid_gallons',
            'def' => 'US_liquid_gallon',
            'aliasKind' => 'generated_plural'
        ],
        'gallons' => [
            'type' => 'alias',
            'name' => 'gallons',
            'def' => 'US_liquid_gallon',
            'aliasKind' => 'generated_plural'
        ],
        'barrels' => [
            'type' => 'alias',
            'name' => 'barrels',
            'def' => 'barrel',
            'aliasKind' => 'generated_plural'
        ],
        'firkins' => [
            'type' => 'alias',
            'name' => 'firkins',
            'def' => 'firkin',
            'aliasKind' => 'generated_plural'
        ],
        'liquid_quarts' => [
            'type' => 'alias',
            'name' => 'liquid_quarts',
            'def' => 'US_liquid_quart',
            'aliasKind' => 'generated_plural'
        ],
        'quarts' => [
            'type' => 'alias',
            'name' => 'quarts',
            'def' => 'US_liquid_quart',
            'aliasKind' => 'generated_plural'
        ],
        'liquid_pints' => [
            'type' => 'alias',
            'name' => 'liquid_pints',
            'def' => 'US_liquid_pint',
            'aliasKind' => 'generated_plural'
        ],
        'pints' => [
            'type' => 'alias',
            'name' => 'pints',
            'def' => 'US_liquid_pint',
            'aliasKind' => 'generated_plural'
        ],
        'liquid_cups' => [
            'type' => 'alias',
            'name' => 'liquid_cups',
            'def' => 'US_liquid_cup',
            'aliasKind' => 'generated_plural'
        ],
        'cups' => [
            'type' => 'alias',
            'name' => 'cups',
            'def' => 'US_liquid_cup',
            'aliasKind' => 'generated_plural'
        ],
        'liquid_gills' => [
            'type' => 'alias',
            'name' => 'liquid_gills',
            'def' => 'US_liquid_gill',
            'aliasKind' => 'generated_plural'
        ],
        'gills' => [
            'type' => 'alias',
            'name' => 'gills',
            'def' => 'US_liquid_gill',
            'aliasKind' => 'generated_plural'
        ],
        'fluid_ounces' => [
            'type' => 'alias',
            'name' => 'fluid_ounces',
            'def' => 'US_fluid_ounce',
            'aliasKind' => 'generated_plural'
        ],
        'liquid_ounces' => [
            'type' => 'alias',
            'name' => 'liquid_ounces',
            'def' => 'US_fluid_ounce',
            'aliasKind' => 'generated_plural'
        ],
        'tablespoons' => [
            'type' => 'alias',
            'name' => 'tablespoons',
            'def' => 'tablespoon',
            'aliasKind' => 'generated_plural'
        ],
        'fluid_drams' => [
            'type' => 'alias',
            'name' => 'fluid_drams',
            'def' => 'fluid_dram',
            'aliasKind' => 'generated_plural'
        ],
        'teaspoons' => [
            'type' => 'alias',
            'name' => 'teaspoons',
            'def' => 'teaspoon',
            'aliasKind' => 'generated_plural'
        ],
        'shakes' => [
            'type' => 'alias',
            'name' => 'shakes',
            'def' => 'shake',
            'aliasKind' => 'generated_plural'
        ],
        'sidereal_days' => [
            'type' => 'alias',
            'name' => 'sidereal_days',
            'def' => 'sidereal_day',
            'aliasKind' => 'generated_plural'
        ],
        'sidereal_hours' => [
            'type' => 'alias',
            'name' => 'sidereal_hours',
            'def' => 'sidereal_hour',
            'aliasKind' => 'generated_plural'
        ],
        'sidereal_minutes' => [
            'type' => 'alias',
            'name' => 'sidereal_minutes',
            'def' => 'sidereal_minute',
            'aliasKind' => 'generated_plural'
        ],
        'sidereal_seconds' => [
            'type' => 'alias',
            'name' => 'sidereal_seconds',
            'def' => 'sidereal_second',
            'aliasKind' => 'generated_plural'
        ],
        'sidereal_years' => [
            'type' => 'alias',
            'name' => 'sidereal_years',
            'def' => 'sidereal_year',
            'aliasKind' => 'generated_plural'
        ],
        'years' => [
            'type' => 'alias',
            'name' => 'years',
            'def' => 'tropical_year',
            'aliasKind' => 'generated_plural'
        ],
        'tropical_years' => [
            'type' => 'alias',
            'name' => 'tropical_years',
            'def' => 'tropical_year',
            'aliasKind' => 'generated_plural'
        ],
        'lunar_months' => [
            'type' => 'alias',
            'name' => 'lunar_months',
            'def' => 'lunar_month',
            'aliasKind' => 'generated_plural'
        ],
        'common_years' => [
            'type' => 'alias',
            'name' => 'common_years',
            'def' => 'common_year',
            'aliasKind' => 'generated_plural'
        ],
        'leap_years' => [
            'type' => 'alias',
            'name' => 'leap_years',
            'def' => 'leap_year',
            'aliasKind' => 'generated_plural'
        ],
        'sidereal_months' => [
            'type' => 'alias',
            'name' => 'sidereal_months',
            'def' => 'sidereal_month',
            'aliasKind' => 'generated_plural'
        ],
        'tropical_months' => [
            'type' => 'alias',
            'name' => 'tropical_months',
            'def' => 'tropical_month',
            'aliasKind' => 'generated_plural'
        ],
        'fortnights' => [
            'type' => 'alias',
            'name' => 'fortnights',
            'def' => 'fortnight',
            'aliasKind' => 'generated_plural'
        ],
        'weeks' => [
            'type' => 'alias',
            'name' => 'weeks',
            'def' => 'week',
            'aliasKind' => 'generated_plural'
        ],
        'jiffies' => [
            'type' => 'alias',
            'name' => 'jiffies',
            'def' => 'jiffy',
            'aliasKind' => 'generated_plural'
        ],
        'eons' => [
            'type' => 'alias',
            'name' => 'eons',
            'def' => 'eon',
            'aliasKind' => 'generated_plural'
        ],
        'months' => [
            'type' => 'alias',
            'name' => 'months',
            'def' => 'month',
            'aliasKind' => 'generated_plural'
        ],
        'sverdrups' => [
            'type' => 'alias',
            'name' => 'sverdrups',
            'def' => 'sverdrup',
            'aliasKind' => 'generated_plural'
        ],
        'standard_free_falls' => [
            'type' => 'alias',
            'name' => 'standard_free_falls',
            'def' => 'standard_free_fall',
            'aliasKind' => 'generated_plural'
        ],
        'gravities' => [
            'type' => 'alias',
            'name' => 'gravities',
            'def' => 'gravity',
            'aliasKind' => 'generated_plural'
        ],
        'waters' => [
            'type' => 'alias',
            'name' => 'waters',
            'def' => 'conventional_water',
            'aliasKind' => 'generated_plural'
        ],
        'conventional_waters' => [
            'type' => 'alias',
            'name' => 'conventional_waters',
            'def' => 'conventional_water',
            'aliasKind' => 'generated_plural'
        ],
        'forces' => [
            'type' => 'alias',
            'name' => 'forces',
            'def' => 'force',
            'aliasKind' => 'generated_plural'
        ],
        'dynes' => [
            'type' => 'alias',
            'name' => 'dynes',
            'def' => 'dyne',
            'aliasKind' => 'generated_plural'
        ],
        'ponds' => [
            'type' => 'alias',
            'name' => 'ponds',
            'def' => 'pond',
            'aliasKind' => 'generated_plural'
        ],
        'force_kilograms' => [
            'type' => 'alias',
            'name' => 'force_kilograms',
            'def' => 'force_kilogram',
            'aliasKind' => 'generated_plural'
        ],
        'force_ounces' => [
            'type' => 'alias',
            'name' => 'force_ounces',
            'def' => 'force_ounce',
            'aliasKind' => 'generated_plural'
        ],
        'force_pounds' => [
            'type' => 'alias',
            'name' => 'force_pounds',
            'def' => 'force_pound',
            'aliasKind' => 'generated_plural'
        ],
        'poundals' => [
            'type' => 'alias',
            'name' => 'poundals',
            'def' => 'poundal',
            'aliasKind' => 'generated_plural'
        ],
        'force_grams' => [
            'type' => 'alias',
            'name' => 'force_grams',
            'def' => 'gram_force',
            'aliasKind' => 'generated_plural'
        ],
        'force_tons' => [
            'type' => 'alias',
            'name' => 'force_tons',
            'def' => 'force_ton',
            'aliasKind' => 'generated_plural'
        ],
        'kips' => [
            'type' => 'alias',
            'name' => 'kips',
            'def' => 'kip',
            'aliasKind' => 'generated_plural'
        ],
        'atmospheres' => [
            'type' => 'alias',
            'name' => 'atmospheres',
            'def' => 'standard_atmosphere',
            'aliasKind' => 'generated_plural'
        ],
        'standard_atmospheres' => [
            'type' => 'alias',
            'name' => 'standard_atmospheres',
            'def' => 'standard_atmosphere',
            'aliasKind' => 'generated_plural'
        ],
        'technical_atmospheres' => [
            'type' => 'alias',
            'name' => 'technical_atmospheres',
            'def' => 'technical_atmosphere',
            'aliasKind' => 'generated_plural'
        ],
        'torrs' => [
            'type' => 'alias',
            'name' => 'torrs',
            'def' => 'millimeter_Hg',
            'aliasKind' => 'generated_plural'
        ],
        'baryes' => [
            'type' => 'alias',
            'name' => 'baryes',
            'def' => 'barie',
            'aliasKind' => 'generated_plural'
        ],
        'baries' => [
            'type' => 'alias',
            'name' => 'baries',
            'def' => 'barie',
            'aliasKind' => 'generated_plural'
        ],
        'poises' => [
            'type' => 'alias',
            'name' => 'poises',
            'def' => 'poise',
            'aliasKind' => 'generated_plural'
        ],
        'stokeses' => [
            'type' => 'alias',
            'name' => 'stokeses',
            'def' => 'stokes',
            'aliasKind' => 'generated_plural'
        ],
        'rhes' => [
            'type' => 'alias',
            'name' => 'rhes',
            'def' => 'rhe',
            'aliasKind' => 'generated_plural'
        ],
        'ergs' => [
            'type' => 'alias',
            'name' => 'ergs',
            'def' => 'erg',
            'aliasKind' => 'generated_plural'
        ],
        'thermochemical_calories' => [
            'type' => 'alias',
            'name' => 'thermochemical_calories',
            'def' => 'thermochemical_calorie',
            'aliasKind' => 'generated_plural'
        ],
        'calories' => [
            'type' => 'alias',
            'name' => 'calories',
            'def' => 'IT_calorie',
            'aliasKind' => 'generated_plural'
        ],
        'therms' => [
            'type' => 'alias',
            'name' => 'therms',
            'def' => 'US_therm',
            'aliasKind' => 'generated_plural'
        ],
        'watthours' => [
            'type' => 'alias',
            'name' => 'watthours',
            'def' => 'watthour',
            'aliasKind' => 'generated_plural'
        ],
        'voltamperes' => [
            'type' => 'alias',
            'name' => 'voltamperes',
            'def' => 'voltampere',
            'aliasKind' => 'generated_plural'
        ],
        'boiler_horsepowers' => [
            'type' => 'alias',
            'name' => 'boiler_horsepowers',
            'def' => 'boiler_horsepower',
            'aliasKind' => 'generated_plural'
        ],
        'horsepowers' => [
            'type' => 'alias',
            'name' => 'horsepowers',
            'def' => 'shaft_horsepower',
            'aliasKind' => 'generated_plural'
        ],
        'shaft_horsepowers' => [
            'type' => 'alias',
            'name' => 'shaft_horsepowers',
            'def' => 'shaft_horsepower',
            'aliasKind' => 'generated_plural'
        ],
        'metric_horsepowers' => [
            'type' => 'alias',
            'name' => 'metric_horsepowers',
            'def' => 'metric_horsepower',
            'aliasKind' => 'generated_plural'
        ],
        'electric_horsepowers' => [
            'type' => 'alias',
            'name' => 'electric_horsepowers',
            'def' => 'electric_horsepower',
            'aliasKind' => 'generated_plural'
        ],
        'water_horsepowers' => [
            'type' => 'alias',
            'name' => 'water_horsepowers',
            'def' => 'water_horsepower',
            'aliasKind' => 'generated_plural'
        ],
        'refrigeration_tons' => [
            'type' => 'alias',
            'name' => 'refrigeration_tons',
            'def' => 'refrigeration_ton',
            'aliasKind' => 'generated_plural'
        ],
        'clos' => [
            'type' => 'alias',
            'name' => 'clos',
            'def' => 'clo',
            'aliasKind' => 'generated_plural'
        ],
        'abamperes' => [
            'type' => 'alias',
            'name' => 'abamperes',
            'def' => 'abampere',
            'aliasKind' => 'generated_plural'
        ],
        'gilberts' => [
            'type' => 'alias',
            'name' => 'gilberts',
            'def' => 'gilbert',
            'aliasKind' => 'generated_plural'
        ],
        'statamperes' => [
            'type' => 'alias',
            'name' => 'statamperes',
            'def' => 'statampere',
            'aliasKind' => 'generated_plural'
        ],
        'biots' => [
            'type' => 'alias',
            'name' => 'biots',
            'def' => 'biot',
            'aliasKind' => 'generated_plural'
        ],
        'abfarads' => [
            'type' => 'alias',
            'name' => 'abfarads',
            'def' => 'abfarad',
            'aliasKind' => 'generated_plural'
        ],
        'abhenries' => [
            'type' => 'alias',
            'name' => 'abhenries',
            'def' => 'abhenry',
            'aliasKind' => 'generated_plural'
        ],
        'abmhos' => [
            'type' => 'alias',
            'name' => 'abmhos',
            'def' => 'abmho',
            'aliasKind' => 'generated_plural'
        ],
        'abohms' => [
            'type' => 'alias',
            'name' => 'abohms',
            'def' => 'abohm',
            'aliasKind' => 'generated_plural'
        ],
        'abvolts' => [
            'type' => 'alias',
            'name' => 'abvolts',
            'def' => 'abvolt',
            'aliasKind' => 'generated_plural'
        ],
        'chemical_faradays' => [
            'type' => 'alias',
            'name' => 'chemical_faradays',
            'def' => 'chemical_faraday',
            'aliasKind' => 'generated_plural'
        ],
        'physical_faradays' => [
            'type' => 'alias',
            'name' => 'physical_faradays',
            'def' => 'physical_faraday',
            'aliasKind' => 'generated_plural'
        ],
        'faradays' => [
            'type' => 'alias',
            'name' => 'faradays',
            'def' => 'C12_faraday',
            'aliasKind' => 'generated_plural'
        ],
        'gammas' => [
            'type' => 'alias',
            'name' => 'gammas',
            'def' => 'gamma',
            'aliasKind' => 'generated_plural'
        ],
        'gausses' => [
            'type' => 'alias',
            'name' => 'gausses',
            'def' => 'gauss',
            'aliasKind' => 'generated_plural'
        ],
        'maxwells' => [
            'type' => 'alias',
            'name' => 'maxwells',
            'def' => 'maxwell',
            'aliasKind' => 'generated_plural'
        ],
        'oersteds' => [
            'type' => 'alias',
            'name' => 'oersteds',
            'def' => 'oersted',
            'aliasKind' => 'generated_plural'
        ],
        'statcoulombs' => [
            'type' => 'alias',
            'name' => 'statcoulombs',
            'def' => 'statcoulomb',
            'aliasKind' => 'generated_plural'
        ],
        'statfarads' => [
            'type' => 'alias',
            'name' => 'statfarads',
            'def' => 'statfarad',
            'aliasKind' => 'generated_plural'
        ],
        'stathenries' => [
            'type' => 'alias',
            'name' => 'stathenries',
            'def' => 'stathenry',
            'aliasKind' => 'generated_plural'
        ],
        'statmhos' => [
            'type' => 'alias',
            'name' => 'statmhos',
            'def' => 'statmho',
            'aliasKind' => 'generated_plural'
        ],
        'statohms' => [
            'type' => 'alias',
            'name' => 'statohms',
            'def' => 'statohm',
            'aliasKind' => 'generated_plural'
        ],
        'statvolts' => [
            'type' => 'alias',
            'name' => 'statvolts',
            'def' => 'statvolt',
            'aliasKind' => 'generated_plural'
        ],
        'unit_poles' => [
            'type' => 'alias',
            'name' => 'unit_poles',
            'def' => 'unit_pole',
            'aliasKind' => 'generated_plural'
        ],
        'fahrenheits' => [
            'type' => 'alias',
            'name' => 'fahrenheits',
            'def' => 'fahrenheit',
            'aliasKind' => 'generated_plural'
        ],
        'footcandles' => [
            'type' => 'alias',
            'name' => 'footcandles',
            'def' => 'footcandle',
            'aliasKind' => 'generated_plural'
        ],
        'footlamberts' => [
            'type' => 'alias',
            'name' => 'footlamberts',
            'def' => 'footlambert',
            'aliasKind' => 'generated_plural'
        ],
        'lamberts' => [
            'type' => 'alias',
            'name' => 'lamberts',
            'def' => 'lambert',
            'aliasKind' => 'generated_plural'
        ],
        'stilbs' => [
            'type' => 'alias',
            'name' => 'stilbs',
            'def' => 'stilb',
            'aliasKind' => 'generated_plural'
        ],
        'phots' => [
            'type' => 'alias',
            'name' => 'phots',
            'def' => 'phot',
            'aliasKind' => 'generated_plural'
        ],
        'nits' => [
            'type' => 'alias',
            'name' => 'nits',
            'def' => 'nit',
            'aliasKind' => 'generated_plural'
        ],
        'langleys' => [
            'type' => 'alias',
            'name' => 'langleys',
            'def' => 'langley',
            'aliasKind' => 'generated_plural'
        ],
        'apostilbs' => [
            'type' => 'alias',
            'name' => 'apostilbs',
            'def' => 'blondel',
            'aliasKind' => 'generated_plural'
        ],
        'blondels' => [
            'type' => 'alias',
            'name' => 'blondels',
            'def' => 'blondel',
            'aliasKind' => 'generated_plural'
        ],
        'kaysers' => [
            'type' => 'alias',
            'name' => 'kaysers',
            'def' => 'kayser',
            'aliasKind' => 'generated_plural'
        ],
        'dynamics' => [
            'type' => 'alias',
            'name' => 'dynamics',
            'def' => 'geopotential',
            'aliasKind' => 'generated_plural'
        ],
        'geopotentials' => [
            'type' => 'alias',
            'name' => 'geopotentials',
            'def' => 'geopotential',
            'aliasKind' => 'generated_plural'
        ],
        'work_years' => [
            'type' => 'alias',
            'name' => 'work_years',
            'def' => 'work_year',
            'aliasKind' => 'generated_plural'
        ],
        'work_months' => [
            'type' => 'alias',
            'name' => 'work_months',
            'def' => 'work_month',
            'aliasKind' => 'generated_plural'
        ],
        'potential_vorticity_units' => [
            'type' => 'alias',
            'name' => 'potential_vorticity_units',
            'def' => 'potential_vorticity_unit',
            'aliasKind' => 'generated_plural'
        ],
        'counts' => [
            'type' => 'alias',
            'name' => 'counts',
            'def' => 'count',
            'aliasKind' => 'generated_plural'
        ],
        'bits' => [
            'type' => 'alias',
            'name' => 'bits',
            'def' => 'bit',
            'aliasKind' => 'generated_plural'
        ],
        'bytes' => [
            'type' => 'alias',
            'name' => 'bytes',
            'def' => 'octet',
            'aliasKind' => 'generated_plural'
        ],
        'octets' => [
            'type' => 'alias',
            'name' => 'octets',
            'def' => 'octet',
            'aliasKind' => 'generated_plural'
        ],
        'dobsons' => [
            'type' => 'alias',
            'name' => 'dobsons',
            'def' => 'dobson',
            'aliasKind' => 'generated_plural'
        ],
        'molecs' => [
            'type' => 'alias',
            'name' => 'molecs',
            'def' => 'molecule',
            'aliasKind' => 'generated_plural'
        ],
        'nucleons' => [
            'type' => 'alias',
            'name' => 'nucleons',
            'def' => 'molecule',
            'aliasKind' => 'generated_plural'
        ],
        'nucs' => [
            'type' => 'alias',
            'name' => 'nucs',
            'def' => 'molecule',
            'aliasKind' => 'generated_plural'
        ],
        'molecules' => [
            'type' => 'alias',
            'name' => 'molecules',
            'def' => 'molecule',
            'aliasKind' => 'generated_plural'
        ]
    ],
    'base' => [
        'meter',
        'kilogram',
        'second',
        'ampere',
        'kelvin',
        'mole',
        'candela'
    ],
    'prefixes' => [
        'yotta' => '1e24',
        'Y' => '1e24',
        'zetta' => '1e21',
        'Z' => '1e21',
        'exa' => '1e18',
        'E' => '1e18',
        'peta' => '1e15',
        'P' => '1e15',
        'tera' => '1e12',
        'T' => '1e12',
        'giga' => '1e9',
        'G' => '1e9',
        'mega' => '1e6',
        'M' => '1e6',
        'kilo' => '1e3',
        'k' => '1e3',
        'hecto' => '100',
        'h' => '100',
        'deka' => '10',
        'da' => '10',
        'deci' => '0.1',
        'd' => '0.1',
        'centi' => '0.01',
        'c' => '0.01',
        'milli' => '1e-3',
        'm' => '1e-3',
        'micro' => '1e-6',
        'u' => '1e-6',
        'nano' => '1e-9',
        'n' => '1e-9',
        'pico' => '1e-12',
        'p' => '1e-12',
        'femto' => '1e-15',
        'f' => '1e-15',
        'atto' => '1e-18',
        'a' => '1e-18',
        'zepto' => '1e-21',
        'z' => '1e-21',
        'yocto' => '1e-24',
        'y' => '1e-24'
    ],
    'prefixMetadata' => [
        'yotta' => [
            'name' => 'yotta',
            'kind' => 'canonical',
            'value' => '1e24'
        ],
        'Y' => [
            'name' => 'yotta',
            'kind' => 'symbol',
            'value' => '1e24'
        ],
        'zetta' => [
            'name' => 'zetta',
            'kind' => 'canonical',
            'value' => '1e21'
        ],
        'Z' => [
            'name' => 'zetta',
            'kind' => 'symbol',
            'value' => '1e21'
        ],
        'exa' => [
            'name' => 'exa',
            'kind' => 'canonical',
            'value' => '1e18'
        ],
        'E' => [
            'name' => 'exa',
            'kind' => 'symbol',
            'value' => '1e18'
        ],
        'peta' => [
            'name' => 'peta',
            'kind' => 'canonical',
            'value' => '1e15'
        ],
        'P' => [
            'name' => 'peta',
            'kind' => 'symbol',
            'value' => '1e15'
        ],
        'tera' => [
            'name' => 'tera',
            'kind' => 'canonical',
            'value' => '1e12'
        ],
        'T' => [
            'name' => 'tera',
            'kind' => 'symbol',
            'value' => '1e12'
        ],
        'giga' => [
            'name' => 'giga',
            'kind' => 'canonical',
            'value' => '1e9'
        ],
        'G' => [
            'name' => 'giga',
            'kind' => 'symbol',
            'value' => '1e9'
        ],
        'mega' => [
            'name' => 'mega',
            'kind' => 'canonical',
            'value' => '1e6'
        ],
        'M' => [
            'name' => 'mega',
            'kind' => 'symbol',
            'value' => '1e6'
        ],
        'kilo' => [
            'name' => 'kilo',
            'kind' => 'canonical',
            'value' => '1e3'
        ],
        'k' => [
            'name' => 'kilo',
            'kind' => 'symbol',
            'value' => '1e3'
        ],
        'hecto' => [
            'name' => 'hecto',
            'kind' => 'canonical',
            'value' => '100'
        ],
        'h' => [
            'name' => 'hecto',
            'kind' => 'symbol',
            'value' => '100'
        ],
        'deka' => [
            'name' => 'deka',
            'kind' => 'canonical',
            'value' => '10'
        ],
        'da' => [
            'name' => 'deka',
            'kind' => 'symbol',
            'value' => '10'
        ],
        'deci' => [
            'name' => 'deci',
            'kind' => 'canonical',
            'value' => '0.1'
        ],
        'd' => [
            'name' => 'deci',
            'kind' => 'symbol',
            'value' => '0.1'
        ],
        'centi' => [
            'name' => 'centi',
            'kind' => 'canonical',
            'value' => '0.01'
        ],
        'c' => [
            'name' => 'centi',
            'kind' => 'symbol',
            'value' => '0.01'
        ],
        'milli' => [
            'name' => 'milli',
            'kind' => 'canonical',
            'value' => '1e-3'
        ],
        'm' => [
            'name' => 'milli',
            'kind' => 'symbol',
            'value' => '1e-3'
        ],
        'micro' => [
            'name' => 'micro',
            'kind' => 'canonical',
            'value' => '1e-6'
        ],
        'u' => [
            'name' => 'micro',
            'kind' => 'symbol',
            'value' => '1e-6'
        ],
        'nano' => [
            'name' => 'nano',
            'kind' => 'canonical',
            'value' => '1e-9'
        ],
        'n' => [
            'name' => 'nano',
            'kind' => 'symbol',
            'value' => '1e-9'
        ],
        'pico' => [
            'name' => 'pico',
            'kind' => 'canonical',
            'value' => '1e-12'
        ],
        'p' => [
            'name' => 'pico',
            'kind' => 'symbol',
            'value' => '1e-12'
        ],
        'femto' => [
            'name' => 'femto',
            'kind' => 'canonical',
            'value' => '1e-15'
        ],
        'f' => [
            'name' => 'femto',
            'kind' => 'symbol',
            'value' => '1e-15'
        ],
        'atto' => [
            'name' => 'atto',
            'kind' => 'canonical',
            'value' => '1e-18'
        ],
        'a' => [
            'name' => 'atto',
            'kind' => 'symbol',
            'value' => '1e-18'
        ],
        'zepto' => [
            'name' => 'zepto',
            'kind' => 'canonical',
            'value' => '1e-21'
        ],
        'z' => [
            'name' => 'zepto',
            'kind' => 'symbol',
            'value' => '1e-21'
        ],
        'yocto' => [
            'name' => 'yocto',
            'kind' => 'canonical',
            'value' => '1e-24'
        ],
        'y' => [
            'name' => 'yocto',
            'kind' => 'symbol',
            'value' => '1e-24'
        ]
    ],
    'prefixRegex' => '~^((?:yotta)|(?:Y)|(?:zetta)|(?:Z)|(?:exa)|(?:E)|(?:peta)|(?:P)|(?:tera)|(?:T)|(?:giga)|(?:G)|(?:mega)|(?:M)|(?:kilo)|(?:k)|(?:hecto)|(?:h)|(?:deka)|(?:da)|(?:deci)|(?:d)|(?:centi)|(?:c)|(?:milli)|(?:m)|(?:micro)|(?:u)|(?:nano)|(?:n)|(?:pico)|(?:p)|(?:femto)|(?:f)|(?:atto)|(?:a)|(?:zepto)|(?:z)|(?:yocto)|(?:y))~'
];
