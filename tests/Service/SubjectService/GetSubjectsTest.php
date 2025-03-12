<?php

declare(strict_types=1);

namespace App\Tests\Service\SubjectService;

use App\Dto\FileDto;
use App\Service\SubjectService;
use PHPUnit\Framework\TestCase;

class GetSubjectsTest extends TestCase
{
    private readonly SubjectService $subjectService;

    public function setUp(): void
    {
        parent::setUp();

        $this->subjectService = new SubjectService();
    }

    /**
     * @dataProvider dataFiles
     */
    public function testGetSubjectsWithGrouping(array $filenames, int $amountSubjects, array $subjectNames): void
    {
        $files = [];

        foreach ($filenames as $filename) {
            $files[] = new FileDto($filename);
        }

        $subjects = $this->subjectService->getSubjects($files, true);

        $this->assertCount($amountSubjects, $subjects);
        foreach ($subjects as $key => $subject) {
            $this->assertEquals($subjectNames[$key], $subject->name);
        }
    }

    /**
     * @dataProvider dataFiles
     */
    public function testGetSubjectsWithoutGrouping(array $filenames /* don't use other parameters */): void
    {
        $files = [];

        foreach ($filenames as $filename) {
            $files[] = new FileDto($filename);
        }

        $subjects = $this->subjectService->getSubjects($files);

        $this->assertCount(count($filenames), $subjects);
        foreach ($subjects as $key => $subject) {
            $this->assertEquals($files[$key]->getNameWithoutType(), $subject->name);
        }
    }

    private function dataFiles(): array
    {
        return [
            [
                ['1998.01 Lepel.(I)-2-a.wav', '1998.01 Lepel.(I)-2-b.wav'],
                1,
                ['1998.01 Lepel.(I)-2'],
            ],
            [
                ['2000.01.(n)-9-a.wav', '2000.01.(n)-9-b.wav', '2000.01.(n)-10-a.wav', '2000.01.(n)-10-c.wav'],
                2,
                ['2000.01.(n)-9', '2000.01.(n)-10'],
            ],
        ];
    }
}
