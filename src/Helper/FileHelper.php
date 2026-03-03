<?php

declare(strict_types=1);

namespace App\Helper;

use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\UnavailableStream;

class FileHelper
{
    /**
     * @throws InvalidArgument
     * @throws UnavailableStream
     * @throws Exception
     */
    public static function getArrayFromFile(string $filename): array
    {
        $result = [];

        $csv = Reader::from($filename);
        $csv->setDelimiter(';');
        foreach ($csv->getRecords() as $record) {
            $result[$record[0]] = $record[1];
        }

        return $result;
    }

    /**
     * @throws InvalidArgument
     * @throws UnavailableStream
     * @throws Exception
     */
    public static function getListFromFile(string $filename): array
    {
        $result = [];

        $csv = Reader::from($filename);
        $csv->setDelimiter(';');
        foreach ($csv->getRecords() as $record) {
            $result[] = $record[0];
        }

        return $result;
    }
}
