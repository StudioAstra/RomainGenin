<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Crée le compte admin, ou met à jour son mot de passe.
 * Sans argument, lit ADMIN_EMAIL et ADMIN_PASSWORD (utilisé au démarrage du conteneur).
 */
#[AsCommand(name: 'app:admin', description: 'Crée ou met à jour le compte administrateur')]
class EnsureAdminCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('E-mail de connexion (défaut : $ADMIN_EMAIL)')] ?string $email = null,
        #[Argument('Mot de passe (défaut : $ADMIN_PASSWORD)')] ?string $password = null,
    ): int {
        $email ??= $_SERVER['ADMIN_EMAIL'] ?? $_ENV['ADMIN_EMAIL'] ?? null;
        $password ??= $_SERVER['ADMIN_PASSWORD'] ?? $_ENV['ADMIN_PASSWORD'] ?? null;

        if (!$email || !$password) {
            $io->warning('ADMIN_EMAIL / ADMIN_PASSWORD non définis : compte admin inchangé.');

            return Command::SUCCESS;
        }

        if (\strlen($password) < 12) {
            $io->error('Le mot de passe admin doit faire au moins 12 caractères.');

            return Command::FAILURE;
        }

        $user = $this->users->findOneBy(['email' => $email]);
        $created = null === $user;
        $user ??= new User($email);

        if (!$created && $this->hasher->isPasswordValid($user, $password)) {
            $io->note(\sprintf('Compte admin « %s » déjà à jour.', $email));

            return Command::SUCCESS;
        }

        $user->setRoles(['ROLE_ADMIN'])->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $io->success(\sprintf('Compte admin « %s » %s.', $email, $created ? 'créé' : 'mis à jour'));

        return Command::SUCCESS;
    }
}
