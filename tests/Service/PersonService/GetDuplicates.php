<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Entity\GeoPoint;
use App\Entity\Informant;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class GetDuplicates extends TestCase
{
    private readonly PersonService $personService;

    public function setUp(): void
    {
        parent::setUp();

        $this->personService = new PersonService();
    }

    /**
     * @dataProvider dataInformants
     * @param array $informantData1
     * @param array $informantData2
     * @param bool $expectedDuplicates
     */
    public function testGetDuplicates(array $informantData1, array $informantData2, bool $expectedDuplicates): void
    {
        $informant1 = new Informant();
        $informant1->setFirstName($informantData1[0]);
        $informant1->setGeoPointBirth($informantData1[1]);
        $informant1->setGeoPointCurrent($informantData1[2]);

        $informant2 = new Informant();
        $informant2->setFirstName($informantData2[0]);
        $informant2->setGeoPointBirth($informantData2[1]);
        $informant2->setGeoPointCurrent($informantData2[2]);

        $result = $this->personService->getDuplicates([$informant1, $informant2]);

        $this->assertEquals(
            $expectedDuplicates,
            count($result) > 0,
            sprintf('Error for %s and %s!', $informant1->getFirstName(), $informant2->getFirstName())
        );
    }

    private function dataInformants(): array
    {
        $geo1 = (new GeoPoint('First'));
        $geo2 = (new GeoPoint('Second'));
        $geo3 = (new GeoPoint('Third'));

        return [
            [
                ['Name', $geo1, $geo2],
                ['NameDiff', $geo1, $geo2],
                false
            ],
            [
                ['Name Without Geo', null, null],
                ['Name Without Geo', null, null],
                false
            ],
            [
                ['NameWith Geo1 Null', $geo1, null],
                ['NameWith Geo1 Null', $geo1, null],
                true
            ],
            [
                ['NameWith Geo2 Null', null, $geo2],
                ['NameWith Geo2 Null', null, $geo2],
                true
            ],
            [
                ['NameWith Geo1 AndNull', $geo1, null],
                ['NameWith Geo1 AndNull', null, $geo1],
                true
            ],
            [
                ['NameWith DiffGeo', $geo1, null],
                ['NameWith DiffGeo', $geo2, $geo3],
                false
            ],
            [
                ['NameWith Geo2 AndNull', null, $geo2],
                ['NameWith Geo2 AndNull', $geo2, null],
                true
            ],
        ];
    }
}
