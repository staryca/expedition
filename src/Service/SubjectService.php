<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\FileDto;
use App\Dto\NamePartDto;
use App\Dto\SubjectDto;
use App\Entity\Type\SubjectType;

class SubjectService
{
    /**
     * @param array<FileDto> $files
     * @param bool $withGroupingByFilename
     * @return array<SubjectDto>
     */
    public function getSubjects(array $files, bool $withGroupingByFilename = false): array
    {
        /** @var array<SubjectDto> $subjects */
        $subjects = [];
        $keySubject = -1;

        $filenames = array_map(static function ($file) {
            return $file->getFilename();
        }, $files);

        /* Create DTOs */
        $exploded = $this->explode($filenames, $withGroupingByFilename);
        $keyPrev = null;
        foreach ($exploded as $keyFile => $nameParts) {
            if ($withGroupingByFilename && $keyPrev !== null) {
                $nameBase = $nameParts->getSameName($exploded[$keyPrev]);
                if ($nameBase !== null) {
                    $subjects[$keySubject]->name = $nameBase;
                    $subjects[$keySubject]->files[$keyFile] = $files[$keyFile];
                    $keyPrev = $keyFile;

                    continue;
                }
            }
            $keySubject++;
            $subjects[$keySubject] = new SubjectDto();

            $subjects[$keySubject]->name = trim(mb_substr($files[$keyFile]->getNameWithoutType(), 0, 290), " +-.,;:");
            $subjects[$keySubject]->files[$keyFile] = $files[$keyFile];
            $subjects[$keySubject]->type = SubjectType::getTypeByFileType($files[$keyFile]->type);

            $keyPrev = $keyFile;
        }

        if (count($subjects) < 2) {
            return $subjects;
        }

        foreach ($subjects as $keySubject => $subject) {
            if (count($subject->files) < 2) {
                /* Detect subject's name */
                $otherSubject = null;
                foreach ($subjects as $key2 => $subject2) {
                    if ($key2 !== $keySubject && count($subject2->files) > 1) {
                        $otherSubject = $subject;
                        break;
                    }
                }
                if ($otherSubject !== null) {
                    $subject->name = mb_substr($subject->name, 0, mb_strlen($otherSubject->name));
                }
            } else {
                /* Set file's notes */
                $keyFirst = array_key_first($subject->files);
                $keyLast = null;
                foreach ($subject->files as $keyFile => $file) {
                    if ($keyFile !== $keyFirst) {
                        $files[$keyFile]->notes =
                            FileDto::NOTES_PART . mb_strtoupper($exploded[$keyFile]->getDifferentPart($exploded[$keyFirst]));
                        $keyLast = $keyFile;
                    }
                }
                $files[$keyFirst]->notes =
                    FileDto::NOTES_PART . mb_strtoupper($exploded[$keyFirst]->getDifferentPart($exploded[$keyLast]));
            }
        }

        return $subjects;
    }

    /**
     * @param array<int, string> $filenames
     * @param bool $detectParts
     * @return array<int, NamePartDto>
     */
    private function explode(array $filenames, bool $detectParts): array
    {
        $result = [];
        $maxLength = 0;
        foreach ($filenames as $key => $filename) {
            $result[$key] = new NamePartDto();
            if ($maxLength < mb_strlen($filename)) {
                $maxLength = mb_strlen($filename);
            }
        }
        if (!$detectParts || empty($filenames)) {
            return $result;
        }

        while ($maxLength > 0) {
            $index = 0;
            $isRepeat = true;
            $isNumber = true;
            while ($isRepeat && $index < $maxLength) {
                $letter = null;
                $isNumber = true;
                foreach ($filenames as $filename) {
                    $letterKey = mb_substr($filename, $index, 1);
                    $isNumber = $isNumber && is_numeric($letterKey);
                    if ($letter === null) {
                        $letter = $letterKey;
                    } elseif ($letter !== $letterKey) {
                        $isRepeat = false;
                    }
                }
                if ($isRepeat) {
                    $index++;
                }
            }

            if ($index > 0) {
                foreach ($filenames as $key => $filename) {
                    $text = mb_substr($filename, 0, $index);
                    $result[$key]->addPart($text, true);
                    $filenames[$key] = mb_substr($filename, $index);
                }
            }
            $maxLength = 0;
            foreach ($filenames as $key => $filename) {
                if ('' === $filename) {
                    continue;
                }
                $amount = $isNumber ? mb_strlen((string) (int) $filename) : 1;
                $text = mb_substr($filename, 0, $amount);
                $result[$key]->addPart($text, false);
                $filenames[$key] = mb_substr($filename, $amount);
                if ($maxLength < mb_strlen($filenames[$key])) {
                    $maxLength = mb_strlen($filenames[$key]);
                }
            }
        }

        return $result;
    }
}
