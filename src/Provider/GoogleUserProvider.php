<?php

declare(strict_types=1);

namespace App\Provider;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class GoogleUserProvider implements UserProviderInterface, OAuthAwareUserProviderInterface
{
    private string $class;

    /**
     * @var array<string, string>
     */
    private array $properties = [
        'identifier' => 'id',
        'email' => 'email',
    ];

    public function __construct(
        string $class,
        array $properties,
        private readonly UserRepository $userRepository,
        private readonly UserService $userService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        $this->class = $class;
        $this->properties = array_merge($this->properties, $properties);
    }
    public function refreshUser(UserInterface $user): UserInterface
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $identifier = $this->properties['identifier'];
        if (!$accessor->isReadable($user, $identifier) || !$this->supportsClass($user::class)) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $userId = $accessor->getValue($user, $identifier);

        $username = $user->getUserIdentifier();

        if (null === $user = $this->findUser([$identifier => $userId])) {
            throw $this->createUserNotFoundException($username, \sprintf('User with ID "%d" could not be reloaded.', $userId));
        }

        $this->userService->onUserLogged($user);

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return $class === $this->class || is_subclass_of($class, $this->class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->findUser(['username' => $identifier]);

        if (!$user) {
            throw $this->createUserNotFoundException($identifier, \sprintf("User '%s' not found", $identifier));
        }

        return $user;
    }

    private function createUser(UserResponseInterface $response): User
    {
        $user = new User();
        $user->setFirstName($response->getFirstName());
        $user->setLastName($response->getLastName());
        $user->setEmail($response->getEmail());
        $user->setDateJoined(new \DateTime());
        $user->setActive(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function findUser(array $criteria): ?UserInterface
    {
        return $this->userRepository->findOneBy($criteria);
    }

    private function createUserNotFoundException(string $username, string $message): UserNotFoundException
    {
        $exception = new AccountNotLinkedException($message);
        $exception->setUserIdentifier($username);

        return $exception;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): ?UserInterface
    {
        $this->logger->info('Loading user: ', [
            'email' => $response->getEmail(),
            'username' => $response->getUsername(),
            'identifier' => $response->getNickname(),
            'firstName' => $response->getFirstName(),
            'lastName' => $response->getLastName(),
        ]);

        $email = method_exists($response, 'getEmail') ? $response->getEmail() : $response->getUsername();
        if (null === $user = $this->findUser([$this->properties['email'] => $email])) {
            $user = $this->createUser($response);
            // throw $this->createUserNotFoundException($email, \sprintf("User '%s' not found.", $email));
        }

        return $user;
    }
}
