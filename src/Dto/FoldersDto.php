<?php

declare(strict_types=1);

namespace App\Dto;

class FoldersDto
{
    public const FOLDER_OK = 1;
    public const FOLDER_NEW = 2;
    public const FOLDER_ABSENT = 3;

    /** @var array<int, string> $foldersOk */
    public array $foldersOk = [];
    /** @var array<string> $foldersNew */
    public array $foldersNew = [];
    /** @var array<int, string> $foldersAbsent */
    public array $foldersAbsent = [];

    /**
     * @return array<FolderInfo>
     */
    public function getFoldersForTable(): array
    {
        $foldersInfo = [];

        $foldersAll = array_merge($this->foldersOk, $this->foldersNew, $this->foldersAbsent);
        sort($foldersAll);
        foreach ($foldersAll as $folder) {
            $folderIndo = new FolderInfo();
            $folderIndo->name = $folder;

            $id = array_search($folder, $this->foldersOk, true);
            if ($id !== false) {
                $folderIndo->reportId = $id;
                $folderIndo->type = self::FOLDER_OK;
            } elseif (false !== ($id = array_search($folder, $this->foldersAbsent, true))) {
                $folderIndo->reportId = $id;
                $folderIndo->type = self::FOLDER_ABSENT;
            } else {
                $folderIndo->type = self::FOLDER_NEW;
            }

            $foldersInfo[] = $folderIndo;
        }

        return $foldersInfo;
    }
}
