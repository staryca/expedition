<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UserRolesDto;
use App\Entity\Type\UserRoleType;
use App\Entity\User;
use App\Helper\TextHelper;
use App\Repository\UserRepository;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TextHelper $textHelper,
    ) {
    }

    /**
     * @param string $text
     * @return array<UserRolesDto>
     */
    public function getUsers(string $text): array
    {
        $result = [];

        $text = $this->textHelper->cleanManySpaces($text);
        $text = str_replace('=', '-', $text);

        $blocks = explode(';', $text);
        if (count($blocks) === 1) {
            $blocks = $this->textHelper->explodeWithBrackets([','], $text);
        }
        foreach ($blocks as $block) {
            $block = trim($block);
            if (str_contains($block, '-')) {
                [$fullName, $rolesText] = explode('-', $block);
            } else {
                [$part1, $part2] = $this->textHelper->getNotes($block);
                $fullName = $part1;
                $rolesText = $part2;
            }

            [$part1, $part2] = $this->textHelper->getNotes($rolesText);
            if ($part2 === '') {
                $rolesText = $part1;
            } else {
                $fullName .= ' ' . $part1;
                $rolesText = $part2;
            }

            $roleKeys = [];

            // Name
            $names = explode(' ', $fullName);
            $name = trim($names[0]);
            $user = $this->userRepository->findByNameOrNick($name);
            if (null === $user && isset($names[1])) {
                $user = $this->userRepository->findByNameOrNick(trim($names[1]), $name);
            } elseif ($user?->getNicks() && false !== mb_stripos($user->getNicks(), $name)) {
                $roleKeys[] = UserRoleType::ROLE_LEADER;
            }

            // Roles
            $roles = explode(',', $rolesText);
            foreach ($roles as $role) {
                $role = mb_strtolower(trim($role, " .\t\n\r\0\x0B"));
                foreach (UserRoleType::VARIANTS as $roleKey => $variants) {
                    if (in_array($role, $variants, true)) {
                        $roleKeys[] = $roleKey;
                        break;
                    }
                }
            }

            if (count($result) === 0 && count($roleKeys) === 0) {
                $roleKeys[] = UserRoleType::ROLE_LEADER;
            }
            if (count($result) > 0 && count($roleKeys) === 0) {
                $roleKeys[] = UserRoleType::ROLE_VIEWER;
            }


            $userRole = new UserRolesDto();
            $userRole->user = $user;
            $userRole->roles = array_unique($roleKeys);
            $result[] = $userRole;
        }

        return $result;
    }

    public function findByFullName(string $fullName): ?User
    {
        $names = explode(' ', $fullName);
        if (1 === count($names)) {
            return $this->userRepository->findByNameOrNick($names[0]);
        }

        if (2 <= count($names)) {
            $user = $this->userRepository->findByNameOrNick($names[0], $names[1]);
            if (null === $user) {
                $user = $this->userRepository->findByNameOrNick($names[1], $names[0]);
            }

            return $user;
        }

        return null;
    }
}
