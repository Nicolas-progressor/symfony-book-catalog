<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:add-admin')]
class AddAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        
        if (!$user) {
            $output->writeln('<error>Пользователь не найден</error>');
            return Command::FAILURE;
        }
        
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->em->flush();
        
        $output->writeln('Пользователю test@example.com присвоена роль ROLE_ADMIN');
        
        return Command::SUCCESS;
    }
}
