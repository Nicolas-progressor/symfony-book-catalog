<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import-books')]
class ImportBooksCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $authorsFile = __DIR__ . '/../../demo-data/authors.json';
        $booksFile = __DIR__ . '/../../demo-data/books.json';

        if (!file_exists($authorsFile) || !file_exists($booksFile)) {
            $output->writeln('<error>Files not found</error>');
            return Command::FAILURE;
        }

        $authorsData = json_decode(file_get_contents($authorsFile), true);
        $booksData = json_decode(file_get_contents($booksFile), true);

        $authorRepo = $this->em->getRepository(Author::class);
        $bookRepo = $this->em->getRepository(Book::class);

        // Import authors
        foreach ($authorsData as $authorData) {
            $author = new Author();
            $author->setName($authorData['name']);
            $author->setBiography($authorData['biography'] ?? null);
            $author->setBirthYear($authorData['birth_year'] ?? null);
            $author->setDeathYear($authorData['death_year'] ?? null);
            $this->em->persist($author);
        }
        $this->em->flush();

        // Import books
        foreach ($booksData as $bookData) {
            $book = $bookRepo->find($bookData['book_id']);
            if (!$book) {
                $book = new Book();
            }
            $book->setTitle($bookData['title']);
            $book->setYear($bookData['year'] ?? null);
            $book->setDescription($bookData['description'] ?? null);
            $book->setIsbn($bookData['isbn'] ?? null);
            $book->setImageName($bookData['imagename'] ?? null);
            $book->setImageLink($bookData['imagelink'] ?? null);
            
            $author = $authorRepo->find($bookData['author_id']);
            if ($author) {
                $book->setAuthor($author);
            }
            
            $this->em->persist($book);
        }
        $this->em->flush();

        $output->writeln(sprintf('Imported %d authors and %d books', count($authorsData), count($booksData)));

        return Command::SUCCESS;
    }
}
