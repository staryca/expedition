<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\YoutubeService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private readonly YoutubeService $youtubeService,
    ) {
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(Request $request): Response
    {
        $client = $this->youtubeService->getGoogleClient();
        $session = $request->getSession();

        $authUrl = null;
        if ($request->get('cleanToken')) {
            $session->remove('access_token');
        } elseif ($request->get('code')) {
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
            $this->logger->info(var_export($token, true));
            $client->setAccessToken($token);
            $request->getSession()->set('access_token', $token);
        } elseif ($session->get('access_token')) {
            $client->setAccessToken($session->get('access_token'));
            if ($client->isAccessTokenExpired()) {
                $session->remove('access_token');
            }
        }
        if (!$client->getAccessToken()) {
            $authUrl = $client->createAuthUrl();
        }

        return $this->render('user/profile.html.twig',
            ['authUrl' => $authUrl]
        );
    }
}
