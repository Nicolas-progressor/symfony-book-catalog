<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, FormFactoryInterface $formFactory, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        
        $form = $formFactory->createBuilder()
            ->add('username', TextType::class, ['label' => 'Имя пользователя', 'required' => true])
            ->add('email', EmailType::class, ['label' => 'Email', 'required' => true])
            ->add('save', SubmitType::class, ['label' => 'Сохранить', 'attr' => ['class' => 'btn-primary']])
            ->getForm();

        // Устанавливаем данные из сущности user
        $form->get('username')->setData($user->getUsername());
        $form->get('email')->setData($user->getEmail());

        // Форма смены пароля
        $passwordForm = $formFactory->createBuilder()
            ->add('current_password', PasswordType::class, ['label' => 'Текущий пароль', 'attr' => ['class' => 'form-control'], 'required' => true])
            ->add('new_password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Новый пароль', 'attr' => ['class' => 'form-control'], 'required' => true],
                'second_options' => ['label' => 'Повторите пароль', 'attr' => ['class' => 'form-control'], 'required' => true],
                'invalid_message' => 'Пароли не совпадают',
            ])
            ->add('change_password', SubmitType::class, ['label' => 'Сменить пароль', 'attr' => ['class' => 'btn-warning']])
            ->getForm();

        $passwordForm->handleRequest($request);

        // Проверяем, была ли нажата кнопка смены пароля
        $changePasswordClicked = $passwordForm->get('change_password')->isClicked();

        if ($changePasswordClicked && $passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = $passwordForm->get('current_password')->getData();
            $newPassword = $passwordForm->get('new_password')->getData();

            if (empty($currentPassword)) {
                $this->addFlash('danger', 'Введите текущий пароль');
            } elseif (empty($newPassword)) {
                $this->addFlash('danger', 'Введите новый пароль');
            } elseif (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'Неверный текущий пароль');
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $em->flush();
                $this->addFlash('success', 'Пароль успешно изменён');
                return $this->redirectToRoute('app_profile');
            }
        } elseif ($changePasswordClicked && $passwordForm->isSubmitted()) {
            $this->addFlash('danger', 'Пароли не совпадают или заполнены некорректно');
        }

        // Обрабатываем форму профиля только если нажата кнопка сохранения
        $saveClicked = $form->get('save')->isClicked();

        if ($saveClicked) {
            $form->handleRequest($request);
        }

        if ($saveClicked && $form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            $email = $form->get('email')->getData();
            
            if ($username) {
                $user->setUsername($username);
            }
            if ($email) {
                $user->setEmail($email);
            }
            $em->flush();
            $this->addFlash('success', 'Профиль обновлён');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }

    #[Route('/notifications', name: 'app_notifications', methods: ['GET'])]
    public function notifications(NotificationRepository $notificationRepository): Response
    {
        $notifications = $notificationRepository->findAllByUser($this->getUser()->getId());

        return $this->render('profile/notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(Notification $notification, EntityManagerInterface $em): Response
    {
        if ($notification->getUser()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $notification->setIsRead(true);
        $em->flush();

        return $this->redirectToRoute('app_notifications');
    }

    #[Route('/notifications/mark-all-read', name: 'app_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(EntityManagerInterface $em, NotificationRepository $notificationRepository): Response
    {
        $notifications = $notificationRepository->findUnreadByUser($this->getUser()->getId());
        
        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }
        
        $em->flush();

        return $this->redirectToRoute('app_notifications');
    }
}
