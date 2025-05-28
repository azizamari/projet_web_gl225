<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Creates an admin user with ROLE_ADMIN',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username for admin user')
            ->addArgument('email', InputArgument::REQUIRED, 'Email for admin user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for admin user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->warning("User with email $email already exists. Skipping admin creation.");
            return Command::SUCCESS;
        }

        // Create new user
        $user = new User();
        $user->setEmail($email);
        
        // Set first name and last name based on username if not already an email format
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $user->setFirstName('Admin');
            $user->setLastName('User');
        } else {
            $parts = explode(' ', $username, 2);
            $user->setFirstName($parts[0]);
            $user->setLastName($parts[1] ?? 'Admin');
        }

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Set admin role
        $user->setRoles(['ROLE_ADMIN']);

        // Save to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("Admin user {$email} successfully created with ROLE_ADMIN");

        return Command::SUCCESS;
    }
}
