<?php

declare(strict_types=1);

namespace App\Tests\Service\UserService;

use App\Entity\Type\UserRoleType;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PersonService;
use App\Service\UserService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class RolesTest extends TestCase
{
    private readonly UserService $userService;
    private readonly UserRepository $userRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepository::class);
        $personService = new PersonService();
        $this->userService = new UserService(
            $this->userRepository,
            $personService,
            $this->createMock(EntityManager::class),
        );
    }

    public function testThreeUsers(): void
    {
        $text = 'Code - кіраўнік, фота, замалёўкі; Тэст21 Тэст22 - нататкі, фота; Тэст31 Тэст32 (Photo2) - фота';

        $users = $this->userService->getUsers($text);

        $this->assertCount(3, $users);

        $this->assertCount(3, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_LEADER, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_SKETCH, $users[0]->roles);

        $this->assertCount(2, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_NOTES, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[1]->roles);

        $this->assertCount(1, $users[2]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[2]->roles);
    }

    public function testWithNotes(): void
    {
        $text = 'Code - Тэст11 Тэст12 (кіраўнік, дыктафон, фота); Тэст21 Тэст22 - фота, відэа; Тэст31 Тэст32 - нататнік.';

        $users = $this->userService->getUsers($text);

        $this->assertCount(3, $users);

        $this->assertCount(3, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_LEADER, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_AUDIO, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[0]->roles);

        $this->assertCount(2, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_VIDEO, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[1]->roles);

        $this->assertCount(1, $users[2]->roles);
        $this->assertContains(UserRoleType::ROLE_NOTES, $users[2]->roles);
    }

    public function testUsersWithNotes(): void
    {
        $text = 'Code= Тэст11 Тэст12 (кіраўнік, аўдыя, нататкі); Тэст21 Тэст22 (фота, аўдыя)';

        $users = $this->userService->getUsers($text);

        $this->assertCount(2, $users);

        $this->assertCount(3, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_LEADER, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_AUDIO, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_NOTES, $users[0]->roles);

        $this->assertCount(2, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_AUDIO, $users[1]->roles);
    }

    public function testUsersWithoutRole(): void
    {
        $text = 'Тэст11 Тэст12 – Code; Тэст21 Тэст22; Тэст31 Тэст32 (фота)';

        $users = $this->userService->getUsers($text);

        $this->assertCount(3, $users);

        $this->assertCount(1, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_LEADER, $users[0]->roles);

        $this->assertCount(1, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_VIEWER, $users[1]->roles);

        $this->assertCount(1, $users[2]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[2]->roles);
    }

    public function testUsersWithLeader(): void
    {
        $user = new User();
        $user->setNicks('Code1,Code2,Code3,Code4');
        $this->userRepository->expects($this->atMost(3))
            ->method('findByNameOrNick')
            ->willReturn($user);

        $text = 'Code2 = Тэст11 Тэст12 (гутарка, аўдыё); Тэст21 Тэст22 (фота); Тэст31 Тэст32 (дзённік)';

        $users = $this->userService->getUsers($text);

        $this->assertCount(3, $users);

        $this->assertCount(3, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_LEADER, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_AUDIO, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_INTERVIEWER, $users[0]->roles);

        $this->assertCount(1, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[1]->roles);

        $this->assertCount(1, $users[2]->roles);
        $this->assertContains(UserRoleType::ROLE_NOTES, $users[2]->roles);
    }

    public function testUsersWithComma(): void
    {
        $text = 'Code = Тэст11 Тэст12 (гутарка, аўдыё), Тэст21 Тэст22 (фота), Тэст31 Тэст32 (дзённік)';

        $users = $this->userService->getUsers($text);

        $this->assertCount(3, $users);

        $this->assertCount(2, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_AUDIO, $users[0]->roles);
        $this->assertContains(UserRoleType::ROLE_INTERVIEWER, $users[0]->roles);

        $this->assertCount(1, $users[1]->roles);
        $this->assertContains(UserRoleType::ROLE_PHOTO, $users[1]->roles);

        $this->assertCount(1, $users[2]->roles);
        $this->assertContains(UserRoleType::ROLE_NOTES, $users[2]->roles);
    }
}
