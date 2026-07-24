<?php

namespace jbboehr\IudexMensurarumMysteriorum\Tests;

use jbboehr\IudexMensurarumMysteriorum\Number\Rational;
use jbboehr\IudexMensurarumMysteriorum\Units;
use PHPUnit\Framework\TestCase;

final class RealWorldFormulaTest extends TestCase
{
    public function testHighwaySpeedConvertsMilesPerHourToMetersPerSecond(): void
    {
        $units = Units::default();

        $speed = $units
            ->quantity(60, 'mile')
            ->div($units->quantity(1, 'hour'))
            ->simplify();

        $this->assertSame('16764/625', $speed->valueToString());
        $this->assertSame('meter / second', $speed->unitToString());
        $this->assertSame('16764/625', $speed->valueIn('meter / second')->toString());
    }

    public function testNewtonsSecondLawComputesForce(): void
    {
        $units = Units::default();

        $force = $units
            ->quantity(1500, 'kilogram')
            ->mul($units->quantity(3, 'meter / second^2'))
            ->simplify();

        $this->assertSame('4500', $force->valueToString());
        $this->assertSame('kilogram * meter / second ^ 2', $force->unitToString());
        $this->assertSame('4500', $force->valueIn('newton')->toString());
    }

    public function testWorkFromForceOverDistanceConvertsToJoules(): void
    {
        $units = Units::default();

        $force = $units
            ->quantity(1500, 'kilogram')
            ->mul($units->quantity(3, 'meter / second^2'));
        $work = $force
            ->mul($units->quantity(100, 'meter'))
            ->simplify();

        $this->assertSame('450000', $work->valueToString());
        $this->assertSame('kilogram * meter ^ 2 / second ^ 2', $work->unitToString());
        $this->assertSame('450000', $work->valueIn('joule')->toString());
    }

    public function testPressureFromForceOverAreaConvertsToPascals(): void
    {
        $units = Units::default();

        $pressure = $units
            ->quantity(600, 'newton')
            ->div($units->quantity(6, 'meter^2'))
            ->simplify();

        $this->assertSame('100', $pressure->valueToString());
        $this->assertSame('kilogram / (meter * second ^ 2)', $pressure->unitToString());
        $this->assertSame('100', $pressure->valueIn('pascal')->toString());
    }

    public function testPowerFromEnergyOverTimeConvertsToWatts(): void
    {
        $units = Units::default();

        $power = $units
            ->quantity(3600000, 'joule')
            ->div($units->quantity(1, 'hour'))
            ->simplify();

        $this->assertSame('1000', $power->valueToString());
        $this->assertSame('kilogram * meter ^ 2 / second ^ 3', $power->unitToString());
        $this->assertSame('1000', $power->valueIn('watt')->toString());
    }

    public function testVolumetricFlowConvertsLitersPerMinuteToCubicMetersPerSecond(): void
    {
        $units = Units::default();

        $flow = $units
            ->quantity(120, 'liter')
            ->div($units->quantity(1, 'minute'))
            ->simplify();

        $this->assertSame('1/500', $flow->valueToString());
        $this->assertSame('meter ^ 3 / second', $flow->unitToString());
        $this->assertSame('1/500', $flow->valueIn('meter^3 / second')->toString());
    }

    public function testDensityConvertsWaterDensityToGramsPerCubicCentimeter(): void
    {
        $units = Units::default();

        $density = $units
            ->quantity(1000, 'kilogram')
            ->div($units->quantity(1, 'meter^3'))
            ->simplify();

        $this->assertSame('1000', $density->valueToString());
        $this->assertSame('kilogram / meter ^ 3', $density->unitToString());
        $this->assertSame('1', $density->valueIn('gram / centimeter^3')->toString());
    }

    public function testOhmsLawConvertsCurrentAndResistanceToVolts(): void
    {
        $units = Units::default();

        $voltage = $units
            ->quantity(2, 'ampere')
            ->mul($units->quantity(5, 'ohm'))
            ->simplify();

        $this->assertSame('10', $voltage->valueToString());
        $this->assertSame('kilogram * meter ^ 2 / (ampere * second ^ 3)', $voltage->unitToString());
        $this->assertSame('10', $voltage->valueIn('volt')->toString());
    }

    public function testAverageAccelerationFromSpeedChangeOverTime(): void
    {
        $units = Units::default();

        $acceleration = $units
            ->quantity(30, 'meter / second')
            ->div($units->quantity(10, 'second'))
            ->simplify();

        $this->assertSame('3', $acceleration->valueToString());
        $this->assertSame('meter / second ^ 2', $acceleration->unitToString());
        $this->assertSame('3', $acceleration->valueIn('meter / second^2')->toString());
    }

    public function testXkcdTypesOfApproximationForCircularTrackPeriodAssumesPiIsOne(): void
    {
        $units = Units::default();

        // xkcd 2205, "Types of Approximation": https://xkcd.com/2205/
        $pi = $units->quantity(1, '1');
        $circumference = $units
            ->quantity(100, 'meter')
            ->mul($pi)
            ->mul(2);
        $period = $circumference
            ->div($units->quantity(10, 'meter / second'))
            ->simplify();

        $this->assertSame('20', $period->valueToString());
        $this->assertSame('second', $period->unitToString());
        $this->assertSame('20', $period->valueIn('second')->toString());
    }

    public function testKineticEnergyConvertsToJoules(): void
    {
        $units = Units::default();

        $speed = $units->quantity(10, 'meter / second');
        $energy = $units
            ->quantity(1000, 'kilogram')
            ->mul($speed)
            ->mul($speed)
            ->div(2)
            ->simplify();

        $this->assertSame('50000', $energy->valueToString());
        $this->assertSame('kilogram * meter ^ 2 / second ^ 2', $energy->unitToString());
        $this->assertSame('50000', $energy->valueIn('joule')->toString());
    }

    public function testMomentumFromMassTimesVelocityMatchesNewtonSeconds(): void
    {
        $units = Units::default();

        $momentum = $units
            ->quantity(1000, 'kilogram')
            ->mul($units->quantity(10, 'meter / second'))
            ->simplify();

        $this->assertSame('10000', $momentum->valueToString());
        $this->assertSame('kilogram * meter / second', $momentum->unitToString());
        $this->assertSame('10000', $momentum->valueIn('newton * second')->toString());
    }

    public function testImpulseFromForceOverTimeMatchesMomentumUnits(): void
    {
        $units = Units::default();

        $impulse = $units
            ->quantity(500, 'newton')
            ->mul($units->quantity(4, 'second'))
            ->simplify();

        $this->assertSame('2000', $impulse->valueToString());
        $this->assertSame('kilogram * meter / second', $impulse->unitToString());
        $this->assertSame('2000', $impulse->valueIn('newton * second')->toString());
    }

    public function testHydrostaticPressureConvertsToPascals(): void
    {
        $units = Units::default();

        $pressure = $units
            ->quantity(1000, 'kilogram / meter^3')
            ->mul($units->quantity(new Rational(981, 100), 'meter / second^2'))
            ->mul($units->quantity(5, 'meter'))
            ->simplify();

        $this->assertSame('49050', $pressure->valueToString());
        $this->assertSame('kilogram / (meter * second ^ 2)', $pressure->unitToString());
        $this->assertSame('49050', $pressure->valueIn('pascal')->toString());
    }

    public function testElectricChargeFromCurrentOverTimeConvertsToCoulombs(): void
    {
        $units = Units::default();

        $charge = $units
            ->quantity(2, 'ampere')
            ->mul($units->quantity(30, 'second'))
            ->simplify();

        $this->assertSame('60', $charge->valueToString());
        $this->assertSame('ampere * second', $charge->unitToString());
        $this->assertSame('60', $charge->valueIn('coulomb')->toString());
    }

    public function testCapacitanceFromChargeOverVoltageConvertsToFarads(): void
    {
        $units = Units::default();

        $capacitance = $units
            ->quantity(60, 'coulomb')
            ->div($units->quantity(12, 'volt'))
            ->simplify();

        $this->assertSame('5', $capacitance->valueToString());
        $this->assertSame('ampere ^ 2 * second ^ 4 / (kilogram * meter ^ 2)', $capacitance->unitToString());
        $this->assertSame('5', $capacitance->valueIn('farad')->toString());
    }

    public function testMagneticFluxDensityConvertsWebersPerAreaToTeslas(): void
    {
        $units = Units::default();

        $fluxDensity = $units
            ->quantity(3, 'weber')
            ->div($units->quantity(2, 'meter^2'))
            ->simplify();

        $this->assertSame('3/2', $fluxDensity->valueToString());
        $this->assertSame('kilogram / (ampere * second ^ 2)', $fluxDensity->unitToString());
        $this->assertSame('3/2', $fluxDensity->valueIn('tesla')->toString());
    }

    public function testIlluminanceConvertsLumensPerAreaToLux(): void
    {
        $units = Units::default();

        $illuminance = $units
            ->quantity(800, 'lumen')
            ->div($units->quantity(20, 'meter^2'))
            ->simplify();

        $this->assertSame('40', $illuminance->valueToString());
        $this->assertSame('candela / meter ^ 2', $illuminance->unitToString());
        $this->assertSame('40', $illuminance->valueIn('lux')->toString());
    }

    public function testAbsorbedDoseFromEnergyPerMassConvertsToGrays(): void
    {
        $units = Units::default();

        $dose = $units
            ->quantity(6, 'joule')
            ->div($units->quantity(2, 'kilogram'))
            ->simplify();

        $this->assertSame('3', $dose->valueToString());
        $this->assertSame('meter ^ 2 / second ^ 2', $dose->unitToString());
        $this->assertSame('3', $dose->valueIn('gray')->toString());
    }

    public function testCatalyticActivityConvertsMolesPerSecondToKatals(): void
    {
        $units = Units::default();

        $activity = $units
            ->quantity(6, 'mole')
            ->div($units->quantity(3, 'second'))
            ->simplify();

        $this->assertSame('2', $activity->valueToString());
        $this->assertSame('mole / second', $activity->unitToString());
        $this->assertSame('2', $activity->valueIn('katal')->toString());
    }

    public function testRadioactivityConvertsEventsPerSecondToBecquerels(): void
    {
        $units = Units::default();

        $activity = $units
            ->quantity(3000, '1')
            ->div($units->quantity(60, 'second'))
            ->simplify();

        $this->assertSame('50', $activity->valueToString());
        $this->assertSame('1 / second', $activity->unitToString());
        $this->assertSame('50', $activity->valueIn('becquerel')->toString());
    }

    public function testSquareFieldAreaConvertsToHectares(): void
    {
        $units = Units::default();

        $area = $units
            ->quantity(100, 'meter')
            ->mul($units->quantity(100, 'meter'))
            ->simplify();

        $this->assertSame('10000', $area->valueToString());
        $this->assertSame('meter ^ 2', $area->unitToString());
        $this->assertSame('1', $area->valueIn('hectare')->toString());
    }

    public function testBodyMassIndexUsesMassPerArea(): void
    {
        $units = Units::default();

        $bodyMassIndex = $units
            ->quantity(80, 'kilogram')
            ->div($units->quantity(2, 'meter'))
            ->div($units->quantity(2, 'meter'))
            ->simplify();

        $this->assertSame('20', $bodyMassIndex->valueToString());
        $this->assertSame('kilogram / meter ^ 2', $bodyMassIndex->unitToString());
        $this->assertSame('20', $bodyMassIndex->valueIn('kilogram / meter^2')->toString());
    }

    public function testConductanceFromCurrentOverVoltageConvertsToSiemens(): void
    {
        $units = Units::default();

        $conductance = $units
            ->quantity(2, 'ampere')
            ->div($units->quantity(10, 'volt'))
            ->simplify();

        $this->assertSame('1/5', $conductance->valueToString());
        $this->assertSame('ampere ^ 2 * second ^ 3 / (kilogram * meter ^ 2)', $conductance->unitToString());
        $this->assertSame('1/5', $conductance->valueIn('siemens')->toString());
    }

    public function testElectricalEnergyConvertsKilowattsOverHoursToKilowattHours(): void
    {
        $units = Units::default();

        $energy = $units
            ->quantity(2, 'kilowatt')
            ->mul($units->quantity(3, 'hour'))
            ->simplify();

        $this->assertSame('21600000', $energy->valueToString());
        $this->assertSame('kilogram * meter ^ 2 / second ^ 2', $energy->unitToString());
        $this->assertSame('6', $energy->valueIn('kilowatthour')->toString());
    }

    // -----------------------------------------------------------------------
    // Equivalents of the native-type cases covering add / sub / pow
    // (see tests/PHPStan/data/unit-real-world-native.php).
    // -----------------------------------------------------------------------

    public function testMultiLegTripDistanceAddsSegments(): void
    {
        $units = Units::default();

        $distance = $units
            ->quantity(1200, 'meter')
            ->add($units->quantity(800, 'meter'))
            ->add($units->quantity(450, 'meter'));

        $this->assertSame('2450', $distance->valueToString());
        $this->assertSame('meter', $distance->unitToString());
        $this->assertSame('2450', $distance->valueIn('meter')->toString());
    }

    public function testNetElevationGainSubtractsTrailheadFromSummit(): void
    {
        $units = Units::default();

        $elevation = $units
            ->quantity(4410, 'meter')
            ->sub($units->quantity(1800, 'meter'));

        $this->assertSame('2610', $elevation->valueToString());
        $this->assertSame('meter', $elevation->unitToString());
        $this->assertSame('2610', $elevation->valueIn('meter')->toString());
    }

    public function testNetForceAddsDriveAndWindThenSubtractsDrag(): void
    {
        $units = Units::default();

        $force = $units
            ->quantity(5000, 'newton')
            ->add($units->quantity(200, 'newton'))
            ->sub($units->quantity(800, 'newton'))
            ->simplify();

        $this->assertSame('4400', $force->valueToString());
        $this->assertSame('kilogram * meter / second ^ 2', $force->unitToString());
        $this->assertSame('4400', $force->valueIn('newton')->toString());
    }

    public function testKinematicsFinalVelocityAddsAccelerationOverTime(): void
    {
        $units = Units::default();

        $velocity = $units
            ->quantity(10, 'meter / second')
            ->add(
                $units
                    ->quantity(2, 'meter / second^2')
                    ->mul($units->quantity(5, 'second')),
            );

        $this->assertSame('20', $velocity->valueToString());
        $this->assertSame('meter / second', $velocity->unitToString());
        $this->assertSame('20', $velocity->valueIn('meter / second')->toString());
    }

    public function testDisplacementAddsInitialPositionAndVelocityOverTime(): void
    {
        $units = Units::default();

        $position = $units
            ->quantity(100, 'meter')
            ->add(
                $units
                    ->quantity(15, 'meter / second')
                    ->mul($units->quantity(4, 'second')),
            );

        $this->assertSame('160', $position->valueToString());
        $this->assertSame('meter', $position->unitToString());
        $this->assertSame('160', $position->valueIn('meter')->toString());
    }

    public function testTemperatureRiseSubtractsInitialFromFinalKelvin(): void
    {
        $units = Units::default();

        $deltaT = $units
            ->quantity(350, 'kelvin')
            ->sub($units->quantity(300, 'kelvin'));

        $this->assertSame('50', $deltaT->valueToString());
        $this->assertSame('kelvin', $deltaT->unitToString());
        $this->assertSame('50', $deltaT->valueIn('kelvin')->toString());
    }

    public function testRemainingFuelRangeSubtractsDistanceDriven(): void
    {
        $units = Units::default();

        $range = $units
            ->quantity(600, 'kilometer')
            ->sub($units->quantity(185, 'kilometer'));

        $this->assertSame('415', $range->valueToString());
        $this->assertSame('kilometer', $range->unitToString());
        $this->assertSame('415', $range->valueIn('kilometer')->toString());
    }

    public function testSquareFieldAreaViaPower(): void
    {
        $units = Units::default();

        $area = $units->quantity(100, 'meter')->pow(2)->simplify();

        $this->assertSame('10000', $area->valueToString());
        $this->assertSame('meter ^ 2', $area->unitToString());
        $this->assertSame('1', $area->valueIn('hectare')->toString());
    }

    public function testCubeVolumeViaPower(): void
    {
        $units = Units::default();

        $volume = $units->quantity(2, 'meter')->pow(3)->simplify();

        $this->assertSame('8', $volume->valueToString());
        $this->assertSame('meter ^ 3', $volume->unitToString());
        $this->assertSame('8', $volume->valueIn('meter^3')->toString());
    }

    public function testKineticEnergyViaPower(): void
    {
        $units = Units::default();

        $energy = $units
            ->quantity(1000, 'kilogram')
            ->mul($units->quantity(10, 'meter / second')->pow(2))
            ->div(2)
            ->simplify();

        $this->assertSame('50000', $energy->valueToString());
        $this->assertSame('kilogram * meter ^ 2 / second ^ 2', $energy->unitToString());
        $this->assertSame('50000', $energy->valueIn('joule')->toString());
    }

    public function testFreeFallDistanceViaPower(): void
    {
        $units = Units::default();

        $distance = $units
            ->quantity(new Rational(981, 100), 'meter / second^2')
            ->mul($units->quantity(3, 'second')->pow(2))
            ->div(2)
            ->simplify();

        $this->assertSame('8829/200', $distance->valueToString());
        $this->assertSame('meter', $distance->unitToString());
        $this->assertSame('8829/200', $distance->valueIn('meter')->toString());
    }

    public function testDaltonPartialPressuresAdd(): void
    {
        $units = Units::default();

        $pressure = $units
            ->quantity(21000, 'pascal')
            ->add($units->quantity(79000, 'pascal'));

        $this->assertSame('100000', $pressure->valueToString());
        $this->assertSame('pascal', $pressure->unitToString());
        $this->assertSame('100000', $pressure->valueIn('pascal')->toString());
    }

    public function testEnergyBudgetGeneratedMinusConsumedPlusImported(): void
    {
        $units = Units::default();

        $energy = $units
            ->quantity(new Rational(5_000_000_000, 1), 'joule')
            ->sub($units->quantity(new Rational(4_200_000_000, 1), 'joule'))
            ->add($units->quantity(new Rational(300_000_000, 1), 'joule'));

        $this->assertSame('1100000000', $energy->valueToString());
        $this->assertSame('joule', $energy->unitToString());
        $this->assertSame('1100000000', $energy->valueIn('joule')->toString());
    }

    public function testOppositeDisplacementNegatesMagnitude(): void
    {
        $units = Units::default();

        $west = $units->quantity(40, 'meter')->neg();

        $this->assertSame('-40', $west->valueToString());
        $this->assertSame('meter', $west->unitToString());
        $this->assertSame('-40', $west->valueIn('meter')->toString());
    }
}
