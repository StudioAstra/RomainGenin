<?php

namespace App\Controller;

use App\Enum\ProjectKind;
use App\Repository\CertificationRepository;
use App\Repository\EducationRepository;
use App\Repository\ExperienceRepository;
use App\Repository\ProfileRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        ProfileRepository $profiles,
        ExperienceRepository $experiences,
        ProjectRepository $projects,
        CertificationRepository $certifications,
        EducationRepository $educations,
    ): Response {
        $profile = $profiles->findCurrent();

        if (null === $profile) {
            throw $this->createNotFoundException('Profil non configuré : lancez « bin/console app:seed » ou créez-le dans /admin.');
        }

        if ($profile->isMaintenance() && !$this->isGranted('ROLE_ADMIN')) {
            $response = $this->render('home/maintenance.html.twig', ['profile' => $profile]);
            $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
            $response->headers->set('Retry-After', '3600');
            $response->headers->set('X-Robots-Tag', 'noindex');

            return $response;
        }

        return $this->render('home/index.html.twig', [
            'profile' => $profile,
            'experiences' => $experiences->findPublished(),
            'pro_projects' => $projects->findPublished(ProjectKind::Pro),
            'perso_projects' => $projects->findPublished(ProjectKind::Perso),
            'certifications' => $certifications->findOrdered(),
            'educations' => $educations->findOrdered(),
        ]);
    }
}
