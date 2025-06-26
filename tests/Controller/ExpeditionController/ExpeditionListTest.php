<?php

declare(strict_types=1);

namespace App\Tests\Controller\ExpeditionController;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExpeditionListTest extends WebTestCase
{
    public function testList(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Сьпіс экспедыцый');

        self::assertCount(0, $crawler->filter('a.list-group-item'));
    }
}
