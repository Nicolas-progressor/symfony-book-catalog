<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\AuthorSubscription;
use App\Entity\Notification;
use App\Repository\AuthorRepository;
use App\Repository\AuthorSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/author')]
class AuthorController extends AbstractController
{
    #[Route('/new', name: 'author_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $birthYear = $request->request->get('birth_year');
            $deathYear = $request->request->get('death_year');
            $biography = $request->request->get('biography');
            
            $author = new Author();
            $author->setName($name);
            $author->setBirthYear($birthYear ? (int)$birthYear : null);
            $author->setDeathYear($deathYear ? (int)$deathYear : null);
            $author->setBiography($biography ?: null);
            
            $em->persist($author);
            $em->flush();
            
            $this->addFlash('success', 'Автор добавлен');
            
            return $this->redirectToRoute('author_show', ['id' => $author->getId()]);
        }
        
        return $this->render('author/edit.html.twig', [
            'author' => null,
        ]);
    }

    #[Route('/', name: 'author_index', methods: ['GET'])]
    public function index(AuthorRepository $authorRepository): Response
    {
        $page = 1;
        $perPage = 20;
        
        $totalAuthors = $authorRepository->count([]);
        $totalPages = ceil($totalAuthors / $perPage);
        
        $authors = $authorRepository->createQueryBuilder('a')
            ->orderBy('a.id', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
        
        return $this->render('author/index.html.twig', [
            'authors' => $authors,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/load-more', name: 'author_more', methods: ['GET'])]
    public function more(Request $request, AuthorRepository $authorRepository): Response
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = 20;
        
        $authors = $authorRepository->createQueryBuilder('a')
            ->orderBy('a.id', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
        
        return $this->render('author/_list.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/{id}/books-preview', name: 'author_books_preview', methods: ['GET'])]
    public function booksPreview(int $id, AuthorRepository $authorRepository, Request $request): Response
    {
        $author = $authorRepository->find($id);
        
        if (!$author) {
            throw $this->createNotFoundException('Автор не найден');
        }
        
        $limit = (int) $request->query->get('limit', 3);
        $books = $author->getBooks()->toArray();
        $totalBooks = count($books);
        $books = array_slice($books, 0, $limit);
        
        return $this->render('author/_books_preview.html.twig', [
            'books' => $books,
            'authorId' => $id,
            'limit' => $limit,
            'totalBooks' => $totalBooks,
        ]);
    }

    #[Route('/{id}', name: 'author_show', methods: ['GET'])]
    public function show(int $id, AuthorRepository $authorRepository, AuthorSubscriptionRepository $subscriptionRepo, Request $request): Response
    {
        $author = $authorRepository->find($id);
        
        if (!$author) {
            throw $this->createNotFoundException('Автор не найден');
        }
        
        $limit = (int) $request->query->get('limit', 20);
        $allBooks = $author->getBooks()->toArray();
        $books = array_slice($allBooks, 0, $limit);
        $hasMore = count($allBooks) > $limit;
        
        $isSubscribed = false;
        if ($this->getUser()) {
            $subscription = $subscriptionRepo->findByUserAndAuthor($this->getUser()->getId(), $id);
            $isSubscribed = $subscription !== null;
        }
        
        return $this->render('author/show.html.twig', [
            'author' => $author,
            'books' => $books,
            'limit' => $limit,
            'totalBooks' => count($allBooks),
            'hasMore' => $hasMore,
            'isSubscribed' => $isSubscribed,
        ]);
    }

    #[Route('/{id}/books-more', name: 'author_books_more', methods: ['GET'])]
    public function booksMore(int $id, AuthorRepository $authorRepository, Request $request): Response
    {
        $author = $authorRepository->find($id);
        
        if (!$author) {
            throw $this->createNotFoundException('Автор не найден');
        }
        
        $limit = (int) $request->query->get('limit', 3);
        $allBooks = $author->getBooks()->toArray();
        $books = array_slice($allBooks, 0, $limit);
        
        return $this->render('book/_list.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/{id}/edit', name: 'author_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request, AuthorRepository $authorRepository, EntityManagerInterface $em): Response
    {
        $author = $authorRepository->find($id);
        
        if (!$author) {
            throw $this->createNotFoundException('Автор не найден');
        }
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $birthYear = $request->request->get('birth_year');
            $deathYear = $request->request->get('death_year');
            $biography = $request->request->get('biography');
            
            $author->setName($name);
            $author->setBirthYear($birthYear ? (int)$birthYear : null);
            $author->setDeathYear($deathYear ? (int)$deathYear : null);
            $author->setBiography($biography ?: null);
            
            $em->flush();
            $this->addFlash('success', 'Автор обновлён');
            
            return $this->redirectToRoute('author_show', ['id' => $author->getId()]);
        }
        
        return $this->render('author/edit.html.twig', [
            'author' => $author,
        ]);
    }

    #[Route('/{id}/delete', name: 'author_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, AuthorRepository $authorRepository, EntityManagerInterface $em): Response
    {
        $author = $authorRepository->find($id);
        
        if (!$author) {
            throw $this->createNotFoundException('Автор не найден');
        }
        
        $em->remove($author);
        $em->flush();
        
        $this->addFlash('success', 'Автор удалён');
        
        return $this->redirectToRoute('author_index');
    }

    #[Route('/{id}/subscribe', name: 'author_subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function subscribe(int $id, AuthorRepository $authorRepository, AuthorSubscriptionRepository $subscriptionRepo, EntityManagerInterface $em): Response
    {
        $author = $authorRepository->find($id);
        
        if (!$author) {
            throw $this->createNotFoundException('Автор не найден');
        }
        
        $user = $this->getUser();
        
        // Проверяем, не подписан ли уже
        $existing = $subscriptionRepo->findByUserAndAuthor($user->getId(), $id);
        if ($existing) {
            $this->addFlash('info', 'Вы уже подписаны на этого автора');
            return $this->redirectToRoute('author_show', ['id' => $id]);
        }
        
        $subscription = new AuthorSubscription();
        $subscription->setUser($user);
        $subscription->setAuthor($author);
        
        $em->persist($subscription);
        $em->flush();
        
        $this->addFlash('success', 'Вы подписались на автора ' . $author->getName());
        
        return $this->redirectToRoute('author_show', ['id' => $id]);
    }

    #[Route('/{id}/unsubscribe', name: 'author_unsubscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unsubscribe(int $id, AuthorRepository $authorRepository, AuthorSubscriptionRepository $subscriptionRepo, EntityManagerInterface $em): Response
    {
        $author = $authorRepository->find($id);
        
        if (!$author) {
            throw $this->createNotFoundException('Автор не найден');
        }
        
        $user = $this->getUser();
        
        $subscription = $subscriptionRepo->findByUserAndAuthor($user->getId(), $id);
        if ($subscription) {
            $em->remove($subscription);
            $em->flush();
            $this->addFlash('success', 'Вы отписались от автора ' . $author->getName());
        }
        
        return $this->redirectToRoute('author_show', ['id' => $id]);
    }
}
