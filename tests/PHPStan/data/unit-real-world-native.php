<?php

/**
 * Real-world formula cases using native unit_float types (PHPStan analyse).
 *
 * Mirrors tests/RealWorldFormulaTest.php, but:
 * - magnitudes are plain floats with unit_float PHPDoc
 * - arithmetic uses PHP operators (*, /)
 * - each result is passed to a sink with the expected unit in the signature
 *
 * Expected units are the *algebraic* product of the operations (exact unit mode).
 * Conversion to named SI derived units (newton, joule, …) is not covered here yet.
 *
 * Sink functions stay one-line and colocated with each case (excluded from CS Fixer).
 */

// --- Highway speed: mile / hour (catalog aliases mile → international_mile) ---

/** @param unit_float<'international_mile / hour'> $speed */
function expectHighwaySpeed(float $speed): void {}

/** @var unit_float<'mile'> $distanceMi */
$distanceMi = 60.0;
/** @var unit_float<'hour'> $timeH */
$timeH = 1.0;
expectHighwaySpeed($distanceMi / $timeH);

// --- Newton's second law: F = m a ---

/** @param unit_float<'kilogram * meter / second ^ 2'> $force */
function expectForce(float $force): void {}

/** @var unit_float<'kilogram'> $massKg */
$massKg = 1500.0;
/** @var unit_float<'meter / second^2'> $accel */
$accel = 3.0;
expectForce($massKg * $accel);

// --- Work: F × d ---

/** @param unit_float<'kilogram * meter ^ 2 / second ^ 2'> $work */
function expectWork(float $work): void {}

/** @var unit_float<'kilogram'> $workMass */
$workMass = 1500.0;
/** @var unit_float<'meter / second^2'> $workAccel */
$workAccel = 3.0;
/** @var unit_float<'meter'> $workDistance */
$workDistance = 100.0;
expectWork($workMass * $workAccel * $workDistance);

// --- Pressure: force / area (newton kept symbolic) ---

/** @param unit_float<'newton / meter ^ 2'> $pressure */
function expectPressureFromForceOverArea(float $pressure): void {}

/** @var unit_float<'newton'> $forceN */
$forceN = 600.0;
/** @var unit_float<'meter^2'> $areaM2 */
$areaM2 = 6.0;
expectPressureFromForceOverArea($forceN / $areaM2);

// --- Power: energy / time ---

/** @param unit_float<'joule / hour'> $power */
function expectPower(float $power): void {}

/** @var unit_float<'joule'> $energyJ */
$energyJ = 3600000.0;
/** @var unit_float<'hour'> $powerTime */
$powerTime = 1.0;
expectPower($energyJ / $powerTime);

// --- Volumetric flow: liter / minute ---

/** @param unit_float<'liter / minute'> $flow */
function expectVolumetricFlow(float $flow): void {}

/** @var unit_float<'liter'> $volumeL */
$volumeL = 120.0;
/** @var unit_float<'minute'> $flowTime */
$flowTime = 1.0;
expectVolumetricFlow($volumeL / $flowTime);

// --- Density: mass / volume ---

/** @param unit_float<'kilogram / meter ^ 3'> $density */
function expectDensity(float $density): void {}

/** @var unit_float<'kilogram'> $densityMass */
$densityMass = 1000.0;
/** @var unit_float<'meter^3'> $densityVolume */
$densityVolume = 1.0;
expectDensity($densityMass / $densityVolume);

// --- Ohm's law: V = I R ---

/** @param unit_float<'ampere * ohm'> $voltage */
function expectOhmsLawVoltage(float $voltage): void {}

/** @var unit_float<'ampere'> $currentA */
$currentA = 2.0;
/** @var unit_float<'ohm'> $resistanceOhm */
$resistanceOhm = 5.0;
expectOhmsLawVoltage($currentA * $resistanceOhm);

// --- Average acceleration: Δv / Δt ---

/** @param unit_float<'meter / second ^ 2'> $acceleration */
function expectAcceleration(float $acceleration): void {}

/** @var unit_float<'meter / second'> $deltaV */
$deltaV = 30.0;
/** @var unit_float<'second'> $deltaT */
$deltaT = 10.0;
expectAcceleration($deltaV / $deltaT);

// --- xkcd circular track period (π ≈ 1) ---

/** @param unit_float<'second'> $period */
function expectCircularTrackPeriod(float $period): void {}

/** @var unit_float<'1'> $piApprox */
$piApprox = 1.0;
/** @var unit_float<'meter'> $radius */
$radius = 100.0;
/** @var unit_float<'meter / second'> $trackSpeed */
$trackSpeed = 10.0;
$circumference = $radius * $piApprox * 2;
expectCircularTrackPeriod($circumference / $trackSpeed);

// --- Kinetic energy: ½ m v² ---

/** @param unit_float<'kilogram * meter ^ 2 / second ^ 2'> $energy */
function expectKineticEnergy(float $energy): void {}

/** @var unit_float<'kilogram'> $keMass */
$keMass = 1000.0;
/** @var unit_float<'meter / second'> $keSpeed */
$keSpeed = 10.0;
expectKineticEnergy($keMass * $keSpeed * $keSpeed / 2);

// --- Momentum: m v ---

/** @param unit_float<'kilogram * meter / second'> $momentum */
function expectMomentum(float $momentum): void {}

/** @var unit_float<'kilogram'> $momMass */
$momMass = 1000.0;
/** @var unit_float<'meter / second'> $momVelocity */
$momVelocity = 10.0;
expectMomentum($momMass * $momVelocity);

// --- Impulse: F t ---

/** @param unit_float<'newton * second'> $impulse */
function expectImpulse(float $impulse): void {}

/** @var unit_float<'newton'> $impulseForce */
$impulseForce = 500.0;
/** @var unit_float<'second'> $impulseTime */
$impulseTime = 4.0;
expectImpulse($impulseForce * $impulseTime);

// --- Hydrostatic pressure: ρ g h ---

/** @param unit_float<'kilogram / (meter * second ^ 2)'> $pressure */
function expectHydrostaticPressure(float $pressure): void {}

/** @var unit_float<'kilogram / meter^3'> $rho */
$rho = 1000.0;
/** @var unit_float<'meter / second^2'> $g */
$g = 9.81;
/** @var unit_float<'meter'> $depth */
$depth = 5.0;
expectHydrostaticPressure($rho * $g * $depth);

// --- Electric charge: I t ---

/** @param unit_float<'ampere * second'> $charge */
function expectCharge(float $charge): void {}

/** @var unit_float<'ampere'> $chargeCurrent */
$chargeCurrent = 2.0;
/** @var unit_float<'second'> $chargeTime */
$chargeTime = 30.0;
expectCharge($chargeCurrent * $chargeTime);

// --- Capacitance: Q / V ---

/** @param unit_float<'coulomb / volt'> $capacitance */
function expectCapacitance(float $capacitance): void {}

/** @var unit_float<'coulomb'> $capCharge */
$capCharge = 60.0;
/** @var unit_float<'volt'> $capVoltage */
$capVoltage = 12.0;
expectCapacitance($capCharge / $capVoltage);

// --- Magnetic flux density: Φ / A ---

/** @param unit_float<'weber / meter ^ 2'> $fluxDensity */
function expectFluxDensity(float $fluxDensity): void {}

/** @var unit_float<'weber'> $flux */
$flux = 3.0;
/** @var unit_float<'meter^2'> $fluxArea */
$fluxArea = 2.0;
expectFluxDensity($flux / $fluxArea);

// --- Illuminance: Φv / A ---

/** @param unit_float<'lumen / meter ^ 2'> $illuminance */
function expectIlluminance(float $illuminance): void {}

/** @var unit_float<'lumen'> $luminousFlux */
$luminousFlux = 800.0;
/** @var unit_float<'meter^2'> $illumArea */
$illumArea = 20.0;
expectIlluminance($luminousFlux / $illumArea);

// --- Absorbed dose: E / m ---

/** @param unit_float<'joule / kilogram'> $dose */
function expectAbsorbedDose(float $dose): void {}

/** @var unit_float<'joule'> $doseEnergy */
$doseEnergy = 6.0;
/** @var unit_float<'kilogram'> $doseMass */
$doseMass = 2.0;
expectAbsorbedDose($doseEnergy / $doseMass);

// --- Catalytic activity: n / t ---

/** @param unit_float<'mole / second'> $activity */
function expectCatalyticActivity(float $activity): void {}

/** @var unit_float<'mole'> $amount */
$amount = 6.0;
/** @var unit_float<'second'> $catalyticTime */
$catalyticTime = 3.0;
expectCatalyticActivity($amount / $catalyticTime);

// --- Radioactivity: events / t ---

/** @param unit_float<'1 / second'> $activity */
function expectRadioactivity(float $activity): void {}

/** @var unit_float<'1'> $events */
$events = 3000.0;
/** @var unit_float<'second'> $radioTime */
$radioTime = 60.0;
expectRadioactivity($events / $radioTime);

// --- Square field area ---

/** @param unit_float<'meter ^ 2'> $area */
function expectArea(float $area): void {}

/** @var unit_float<'meter'> $side */
$side = 100.0;
expectArea($side * $side);

// --- BMI: m / h² ---

/** @param unit_float<'kilogram / meter ^ 2'> $bmi */
function expectBodyMassIndex(float $bmi): void {}

/** @var unit_float<'kilogram'> $bmiMass */
$bmiMass = 80.0;
/** @var unit_float<'meter'> $bmiHeight */
$bmiHeight = 2.0;
expectBodyMassIndex($bmiMass / $bmiHeight / $bmiHeight);

// --- Conductance: I / V ---

/** @param unit_float<'ampere / volt'> $conductance */
function expectConductance(float $conductance): void {}

/** @var unit_float<'ampere'> $condCurrent */
$condCurrent = 2.0;
/** @var unit_float<'volt'> $condVoltage */
$condVoltage = 10.0;
expectConductance($condCurrent / $condVoltage);

// --- Electrical energy: P t ---

/** @param unit_float<'kilowatt * hour'> $energy */
function expectElectricalEnergy(float $energy): void {}

/** @var unit_float<'kilowatt'> $powerKw */
$powerKw = 2.0;
/** @var unit_float<'hour'> $energyHours */
$energyHours = 3.0;
expectElectricalEnergy($powerKw * $energyHours);

// ---------------------------------------------------------------------------
// Extra real-world cases covering +, -, and ** (mul/div already well covered)
// ---------------------------------------------------------------------------

// --- Multi-leg trip distance: d1 + d2 + d3 ---

/** @param unit_float<'meter'> $distance */
function expectTripDistance(float $distance): void {}

/** @var unit_float<'meter'> $legA */
$legA = 1200.0;
/** @var unit_float<'meter'> $legB */
$legB = 800.0;
/** @var unit_float<'meter'> $legC */
$legC = 450.0;
expectTripDistance($legA + $legB + $legC);

// --- Net elevation gain: summit - trailhead ---

/** @param unit_float<'meter'> $elevation */
function expectNetElevation(float $elevation): void {}

/** @var unit_float<'meter'> $summit */
$summit = 4410.0;
/** @var unit_float<'meter'> $trailhead */
$trailhead = 1800.0;
expectNetElevation($summit - $trailhead);

// --- Net force on a body: F_drive + F_wind - F_drag ---

/** @param unit_float<'newton'> $force */
function expectNetForce(float $force): void {}

/** @var unit_float<'newton'> $driveForce */
$driveForce = 5000.0;
/** @var unit_float<'newton'> $windForce */
$windForce = 200.0;
/** @var unit_float<'newton'> $dragForce */
$dragForce = 800.0;
expectNetForce($driveForce + $windForce - $dragForce);

// --- Kinematics: v = v0 + a t  (mix of +, *) ---

/** @param unit_float<'meter / second'> $velocity */
function expectFinalVelocity(float $velocity): void {}

/** @var unit_float<'meter / second'> $v0 */
$v0 = 10.0;
/** @var unit_float<'meter / second^2'> $aKinematic */
$aKinematic = 2.0;
/** @var unit_float<'second'> $tKinematic */
$tKinematic = 5.0;
expectFinalVelocity($v0 + $aKinematic * $tKinematic);

// --- Displacement: s = s0 + v t  (mix of +, *) ---

/** @param unit_float<'meter'> $position */
function expectDisplacement(float $position): void {}

/** @var unit_float<'meter'> $s0 */
$s0 = 100.0;
/** @var unit_float<'meter / second'> $vConst */
$vConst = 15.0;
/** @var unit_float<'second'> $tMove */
$tMove = 4.0;
expectDisplacement($s0 + $vConst * $tMove);

// --- Temperature rise budget: T_final - T_initial (same unit; not affine convert) ---

/** @param unit_float<'kelvin'> $deltaT */
function expectTemperatureRise(float $deltaT): void {}

/** @var unit_float<'kelvin'> $tFinal */
$tFinal = 350.0;
/** @var unit_float<'kelvin'> $tInitial */
$tInitial = 300.0;
expectTemperatureRise($tFinal - $tInitial);

// --- Remaining fuel range: full range - distance already driven ---

/** @param unit_float<'kilometer'> $range */
function expectRemainingRange(float $range): void {}

/** @var unit_float<'kilometer'> $fullRange */
$fullRange = 600.0;
/** @var unit_float<'kilometer'> $driven */
$driven = 185.0;
expectRemainingRange($fullRange - $driven);

// --- Square field area via **: side ** 2 ---

/** @param unit_float<'meter ^ 2'> $area */
function expectAreaPow(float $area): void {}

/** @var unit_float<'meter'> $fieldSide */
$fieldSide = 100.0;
expectAreaPow($fieldSide ** 2);

// --- Storage volume via **: side ** 3 ---

/** @param unit_float<'meter ^ 3'> $volume */
function expectCubeVolume(float $volume): void {}

/** @var unit_float<'meter'> $cubeSide */
$cubeSide = 2.0;
expectCubeVolume($cubeSide ** 3);

// --- Kinetic energy with **: ½ m v² ---

/** @param unit_float<'kilogram * meter ^ 2 / second ^ 2'> $energy */
function expectKineticEnergyPow(float $energy): void {}

/** @var unit_float<'kilogram'> $keMassPow */
$keMassPow = 1000.0;
/** @var unit_float<'meter / second'> $keSpeedPow */
$keSpeedPow = 10.0;
expectKineticEnergyPow($keMassPow * $keSpeedPow ** 2 / 2);

// --- Free-fall distance: ½ a t²  (mix of *, /, **) ---

/** @param unit_float<'meter'> $distance */
function expectFreeFallDistance(float $distance): void {}

/** @var unit_float<'meter / second^2'> $gFall */
$gFall = 9.81;
/** @var unit_float<'second'> $tFall */
$tFall = 3.0;
expectFreeFallDistance($gFall * $tFall ** 2 / 2);

// --- Ideal gas-ish pressure proxy: n R T / V with + on partial pressures later ---
// Combined: p_total = p1 + p2 (Dalton), then p V product unit check via *

/** @param unit_float<'pascal'> $pressure */
function expectTotalPartialPressure(float $pressure): void {}

/** @var unit_float<'pascal'> $pOxygen */
$pOxygen = 21000.0;
/** @var unit_float<'pascal'> $pNitrogen */
$pNitrogen = 79000.0;
expectTotalPartialPressure($pOxygen + $pNitrogen);

// --- Balance change: deposit - withdrawal + interest (same currency-as-unit stand-in: joule budget) ---
// Energy budget: generated - consumed + imported

/** @param unit_float<'joule'> $energy */
function expectEnergyBudget(float $energy): void {}

/** @var unit_float<'joule'> $generated */
$generated = 5.0e9;
/** @var unit_float<'joule'> $consumed */
$consumed = 4.2e9;
/** @var unit_float<'joule'> $imported */
$imported = 3.0e8;
expectEnergyBudget($generated - $consumed + $imported);

// --- Unary minus: reverse a displacement vector component ---

/** @param unit_float<'meter'> $displacement */
function expectOppositeDisplacement(float $displacement): void {}

/** @var unit_float<'meter'> $east */
$east = 40.0;
expectOppositeDisplacement(-$east);

// --- Unary plus is a no-op on unit types ---

/** @param unit_float<'second'> $duration */
function expectUnaryPlusDuration(float $duration): void {}

/** @var unit_float<'second'> $wait */
$wait = 12.0;
expectUnaryPlusDuration(+$wait);

// --- Modulo of same unit (e.g. packing remainder) ---

/** @param unit_int<'meter'> $remainder */
function expectPackingRemainder(int $remainder): void {}

/** @var unit_int<'meter'> $stockLength */
$stockLength = 100;
/** @var unit_int<'meter'> $pieceLength */
$pieceLength = 12;
expectPackingRemainder($stockLength % $pieceLength);
