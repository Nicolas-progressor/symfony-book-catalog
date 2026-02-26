<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            
            // Валидация
            if (!$email || !$username || !$password) {
                $this->addFlash('error', 'Заполните все поля');
                return $this->redirectToRoute('app_register');
            }
            
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Пароли не совпадают');
                return $this->redirectToRoute('app_register');
            }
            
            // Проверка существующего пользователя
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Пользователь с таким email уже существует');
                return $this->redirectToRoute('app_register');
            }
            
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setRoles(['ROLE_USER']);
            
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            $em->persist($user);
            $em->flush();
            
            $this->addFlash('success', 'Регистрация успешна. Теперь вы можете войти.');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('registration/register.html.twig');
    }
}
