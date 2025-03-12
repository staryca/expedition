<?php

declare(strict_types=1);

namespace App\Tests\Service\SubjectService;

use App\Dto\NamePartDto;
use App\Service\SubjectService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ExplodeTest extends TestCase
{
    private readonly ReflectionClass $reflectionSubjectService;

    public function setUp(): void
    {
        parent::setUp();

        $this->reflectionSubjectService = new ReflectionClass(SubjectService::class);
    }

    /** @dataProvider dataFiles */
    public function testExplode(array $filenames, array $expectedParts): void
    {
        $method = $this->reflectionSubjectService->getMethod('explode');
        /** @var array<int, NamePartDto> $nameParts */
        $nameParts = $method->invokeArgs(new SubjectService(), [$filenames, true]);
        $this->assertCount(count($filenames), $nameParts);

        foreach ($expectedParts as $key => $expectedPart) {
            $this->assertArrayHasKey($key, $nameParts);
            $this->assertTrue($nameParts[$key]->isEqualParts($expectedPart));
        }
    }

    private function dataFiles(): array
    {
        return [
            [
                ['1998.01 Lepel.(I)-2-a.wav', '1998.01 Lepel.(I)-2-b.wav'],
                [
                    ['1998.01 Lepel.(I)-2-', 'a', '.wav'],
                    ['1998.01 Lepel.(I)-2-', 'b', '.wav'],
                ],
            ],
            [
                ['9_Surazh_Kniazi_a_marked.wav', '10_Surazh_Kniazi_a_marked.wav'],
                [
                    ['9', '_Surazh_Kniazi_a_marked.wav'],
                    ['10', '_Surazh_Kniazi_a_marked.wav'],
                ],
            ],
            [
                ['09_Surazh_Navasiolki_a_2007.wav', '09_Surazh_Navasiolki_b_2007.wav', '09_Surazh_Navasiolki_a_2008.wav'],
                [
                    ['09_Surazh_Navasiolki_', 'a', '_200' , '7', '.wav'],
                    ['09_Surazh_Navasiolki_', 'b', '_200' , '7', '.wav'],
                    ['09_Surazh_Navasiolki_', 'a', '_200' , '8', '.wav'],
                ],
            ],
            [
                ['2000.01.(n)-9-a.wav', '2000.01.(n)-10-a.wav'],
                [
                    ['2000.01.(n)-', '9', '-a.wav'],
                    ['2000.01.(n)-', '10', '-a.wav'],
                ],
            ],
        ];
    }
}
