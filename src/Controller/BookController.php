<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Notification;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use App\Repository\AuthorSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/book')]
class BookController extends AbstractController
{
    #[Route('/new', name: 'book_new', methods: ['GET', 'POST'])]
    #[Route('/new/{authorId}', name: 'book_new_with_author', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, AuthorRepository $authorRepository, AuthorSubscriptionRepository $subscriptionRepo, EntityManagerInterface $em, ?int $authorId = null): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $authorId = $request->request->get('author') ?: $authorId;
            $year = $request->request->get('year');
            $isbn = $request->request->get('isbn');
            $description = $request->request->get('description');
            
            $book = new Book();
            $book->setTitle($title);
            $book->setYear($year ? (int)$year : null);
            $book->setIsbn($isbn ?: null);
            $book->setDescription($description ?: null);
            
            $author = null;
            if ($authorId) {
                $author = $authorRepository->find($authorId);
                if ($author) {
                    $book->setAuthor($author);
                }
            }
            
            // Обработка загрузки обложки
            $coverFile = $request->files->get('cover');
            if ($coverFile) {
                $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename . '-' . uniqid() . '.' . $coverFile->guessExtension();
                
                $coverFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/books_covers',
                    $newFilename
                );
                
                $book->setImageName($newFilename);
            }
            
            $em->persist($book);
            $em->flush();
            
            // Отправка уведомлений подписчикам автора
            if ($author) {
                $subscriptions = $subscriptionRepo->findSubscribersByAuthor($author->getId());
                foreach ($subscriptions as $subscription) {
                    $user = $subscription->getUser();
                    $notification = new Notification();
                    $notification->setUser($user);
                    $notification->setTitle('Новая книга автора ' . $author->getName());
                    $notification->setMessage('Автор ' . $author->getName() . ' выпустил новую книгу: "' . $book->getTitle() . '"');
                    $em->persist($notification);
                }
                $em->flush();
            }
            
            $this->addFlash('success', 'Книга добавлена');
            
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }

        $authors = $authorRepository->findAll();
        
        return $this->render('book/edit.html.twig', [
            'book' => null,
            'authors' => $authors,
            'selectedAuthorId' => $authorId,
        ]);
    }

    #[Route('/', name: 'book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        $page = 1;
        $perPage = 20;
        
        $totalBooks = $bookRepository->count([]);
        $totalPages = ceil($totalBooks / $perPage);
        
        $books = $bookRepository->createQueryBuilder('b')
            ->orderBy('b.id', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
        
        return $this->render('book/index.html.twig', [
            'books' => $books,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/load-more', name: 'book_more', methods: ['GET'])]
    public function more(Request $request, BookRepository $bookRepository): Response
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = 20;
        
        $books = $bookRepository->createQueryBuilder('b')
            ->orderBy('b.id', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
        
        return $this->render('book/_list.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/{id}', name: 'book_show', methods: ['GET'])]
    public function show(int $id, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->find($id);
        
        if (!$book) {
            throw $this->createNotFoundException('Книга не найдена');
        }

        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'book_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request, BookRepository $bookRepository, AuthorRepository $authorRepository, EntityManagerInterface $em): Response
    {
        $book = $bookRepository->find($id);
        
        if (!$book) {
            throw $this->createNotFoundException('Книга не найдена');
        }
        
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $authorId = $request->request->get('author');
            $year = $request->request->get('year');
            $isbn = $request->request->get('isbn');
            $description = $request->request->get('description');
            
            $book->setTitle($title);
            $book->setYear($year ? (int)$year : null);
            $book->setIsbn($isbn ?: null);
            $book->setDescription($description ?: null);
            
            if ($authorId) {
                $author = $authorRepository->find($authorId);
                if ($author) {
                    $book->setAuthor($author);
                }
            } else {
                $book->setAuthor(null);
            }
            
            // Обработка загрузки обложки
            $coverFile = $request->files->get('cover');
            if ($coverFile) {
                $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename . '-' . uniqid() . '.' . $coverFile->guessExtension();
                
                $coverFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/books_covers',
                    $newFilename
                );
                
                // Удалить старую обложку
                if ($book->getImageName() && file_exists($this->getParameter('kernel.project_dir') . '/public/uploads/books_covers/' . $book->getImageName())) {
                    unlink($this->getParameter('kernel.project_dir') . '/public/uploads/books_covers/' . $book->getImageName());
                }
                
                $book->setImageName($newFilename);
                $book->setImageLink(null); // Очистить внешнюю ссылку
            }
            
            $em->flush();
            $this->addFlash('success', 'Книга обновлена');
            
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }
        
        $authors = $authorRepository->findAll();
        
        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'authors' => $authors,
        ]);
    }

    #[Route('/{id}/delete-cover', name: 'book_delete_cover', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteCover(int $id, BookRepository $bookRepository, EntityManagerInterface $em): Response
    {
        $book = $bookRepository->find($id);
        
        if (!$book) {
            throw $this->createNotFoundException('Книга не найдена');
        }
        
        if ($book->getImageName()) {
            $coverPath = $this->getParameter('kernel.project_dir') . '/public/uploads/books_covers/' . $book->getImageName();
            if (file_exists($coverPath)) {
                unlink($coverPath);
            }
            $book->setImageName(null);
            $em->flush();
            $this->addFlash('success', 'Обложка удалена');
        }
        
        return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
    }

    #[Route('/{id}/delete', name: 'book_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, BookRepository $bookRepository, EntityManagerInterface $em): Response
    {
        $book = $bookRepository->find($id);
        
        if (!$book) {
            throw $this->createNotFoundException('Книга не найдена');
        }
        
        // Удалить обложку
        if ($book->getImageName()) {
            $coverPath = $this->getParameter('kernel.project_dir') . '/public/uploads/books_covers/' . $book->getImageName();
            if (file_exists($coverPath)) {
                unlink($coverPath);
            }
        }
        
        $em->remove($book);
        $em->flush();
        
        $this->addFlash('success', 'Книга удалена');
        
        return $this->redirectToRoute('book_index');
    }
}
