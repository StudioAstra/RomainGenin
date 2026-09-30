<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin');
        }

        return $this->render('@EasyAdmin/page/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'page_title' => 'Administration',
            'csrf_token_intention' => 'authenticate',
            'target_path' => $this->generateUrl('admin'),
            'username_label' => 'E-mail',
            'password_label' => 'Mot de passe',
            'sign_in_label' => 'Connexion',
            'remember_me_enabled' => true,
            'remember_me_label' => 'Rester connecté',
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function logout(): never
    {
        throw new \LogicException('Intercepté par le pare-feu.');
    }
}
