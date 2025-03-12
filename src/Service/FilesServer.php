<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\FoldersDto;
use Doctrine\Common\Collections\Collection;

class FilesServer
{
    /**
     * @param string $path
     * @return array<string>
     */
    public function getFolders(string $path): array
    {
        $files = scandir($path);
        $files = array_diff($files, ['.', '..']);

        foreach ($files as $key => $file) {
            $fullPath = $path . '/' . $file;
            if (is_file($fullPath)) {
                unset($files[$key]);
            }
        }

        return $files;
    }

    public function getFolderInfo(array $folders, Collection $reports): FoldersDto
    {
        $result = new FoldersDto();

        foreach ($reports as $report) {
            $code = $report->getCode();

            $key = false;
            foreach ($folders as $keyFolder => $folder) {
                if (str_contains($code, $folder)) {
                    $key = $keyFolder;
                    break;
                }
            }
            if ($key === false) {
                $name =
                    mb_strlen($code) < 8
                        ?
                        $report->getDateAction()?->format('Ymd') . '_' .
                        ($report->getLeader() ? ($report->getLeader()->getNicks() ?? (string) $report->getLeader()->getId()) : '') . '_' .
                        $report->getShortGeoPlace() . '_' .
                        $code . '[id=' . $report->getId() . ' ~ ' . $report->getCode() . ']'
                        : $code . '[id=' . $report->getId() . ']';
                if ($report->getLeader()) {
                    $name .= ' (' . $report->getLeader()->getNicks() . '=' . $report->getLeader()->gelFullName() . ')';
                }
                $result->foldersAbsent[$report->getId()] = $name;
            } else {
                $result->foldersOk[$report->getId()] = $folders[$key];
                unset($folders[$key]);
            }
        }
        $result->foldersNew = $folders;

        return $result;
    }
}
