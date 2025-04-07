<?php

namespace App\Tests\Service;

use App\Entity\GeoPoint;
use App\Entity\Informant;
use App\Service\InformantService;
use PHPUnit\Framework\TestCase;

class InformantServiceTest extends TestCase
{
    private readonly InformantService $informantService;

    public function setUp(): void
    {
        parent::setUp();

        $this->informantService = new InformantService();
    }

    /** @dataProvider dataFiles
     * @param array<Informant> $informants
     */
    public function testGetDuplicates(array $informants)
    {
        $result = $this->informantService->getDuplicates($informants);

        $this->assertCount(2, $result, 'Должно быть два массива с дубликатами');

        $this->assertContains([$informants[0], $informants[1]], $result, 'Результат должен содержать дубликаты 1 и 2 информантов');
        $this->assertContains([$informants[1], $informants[2]], $result, 'Результат должен содержать дубликаты 2 и 3 информантов');
    }

    private function dataFiles(): array
    {
        $geo1 = (new GeoPoint('First'));
        $geo2 = (new GeoPoint('Second'));
        $geo3 = (new GeoPoint('Third'));
        $inf1 = (new Informant())
            ->setFirstName('Danik')
            ->setGeoPointBirth($geo1);
        $inf2 = (new Informant())
            ->setFirstName('Danik')
            ->setGeoPointBirth($geo1)
            ->setGeoPointCurrent($geo3);
        $inf3 = (new Informant())
            ->setFirstName('Danik')
            ->setGeoPointBirth($geo2)
            ->setGeoPointCurrent($geo3);
        $inf4 = (new Informant())
            ->setFirstName('Nadia')
            ->setGeoPointBirth($geo2)
            ->setGeoPointCurrent($geo3);

        return [
            [
                [$inf1, $inf2, $inf3, $inf4]
            ],
        ];
    }
}
