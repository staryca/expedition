<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Subject;
use App\Repository\SubjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SubjectController extends AbstractController
{
    public function __construct(
        private readonly SubjectRepository $subjectRepository,
    ) {
    }

    #[Route('/subject/{id}', name: 'subject_show')]
    public function show(int $id): Response
    {
        /** @var Subject|null $subject */
        $subject = $this->subjectRepository->find($id);
        if (!$subject) {
            throw $this->createNotFoundException('The subject does not exist');
        }

        return $this->render('subject/show.html.twig', [
            'subject' => $subject,
        ]);
    }
}
