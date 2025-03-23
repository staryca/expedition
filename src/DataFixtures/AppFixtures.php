<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Expedition;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $expedition = new Expedition();
        $expedition->setName('Base expedition');
        $manager->persist($expedition);

        $manager->flush();
    }
}
