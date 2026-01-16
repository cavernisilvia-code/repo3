<?php
declare(strict_types=1);

/**
 * Entry point CLI.
 * Esempi:
 *   php bin/console.php help
 * 
 *   php bin/console.php books:list
 *   php bin/console.php loans:list
 *   php bin/console.php book:lend B1 M1
 *   php bin/console.php book:return B1
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Src\Library\LibraryService;
use Src\Storage\CsvStorage;
use Src\Storage\Repositories\BookRepository;
use Src\Storage\Repositories\LoanRepository;
use Src\Storage\Repositories\MemberRepository;

// Legge configurazione da .env
$dataDir = env('DATA_DIR', './data');
$dateFormat = env('DATE_FORMAT', 'd/m/Y'); // usato solo per eventuali stampe (qui è pronto per estensioni)
$maxLoans = (int)env('MAX_LOANS_PER_MEMBER', '2');

// Costruisce dipendenze (manuale, senza container DI, per semplicità didattica)
$storage = new CsvStorage($dataDir);
$booksRepo = new BookRepository($storage);
$membersRepo = new MemberRepository($storage);
$loansRepo = new LoanRepository($storage);

$service = new LibraryService($booksRepo, $membersRepo, $loansRepo, $maxLoans);

// Parsing argomenti
$args = $argv;
array_shift($args); // rimuove nome script

$command = $args[0] ?? 'help';
$todayYmd = date('Y-m-d'); // nel CSV salviamo sempre in formato stabile

switch ($command) {
    case 'help':
        echo "Biblioteca CLI\n";
        echo "Questi sono i comandi disponibili:\n";
        echo "  help\n";                     
        echo "Mostra questa guida\n";
        echo "  books:list\n";                
        echo "Elenca libri\n";
        echo "  loans:list\n";                
        echo "Elenca prestiti aperti\n";
        echo "  book:lend <BOOK> <MEM>\n";    
        echo "Presta un libro a un membro\n";
        echo "  book:return <BOOK>\n";        
        echo "Registra la restituzione di un libro\n";
        echo " members:list\n";
        echo "Elenca i membri\n";
        echo "\nEsempi:\n";
        echo "  php bin/console.php books:list\n"; 
        
        echo "  php bin/console.php book:lend B1 M1\n"; 
        
        echo "  php bin/console.php book:return B1\n"; 
        
        echo "  php bin/console.php members:list\n"; 
        
        echo "\nConfigurazione (.env):\n";
        echo "DATA_DIR ";
        echo "DATE_FORMAT";
        echo  "MAX_LOANS_PER_MEMBER";
        exit(0);

    case 'books:list':
        foreach ($service->listBooks() as $line) {
            echo $line . "\n";
        }
        exit(0);

    case 'loans:list':
        foreach ($service->listOpenLoans() as $line) {
            echo $line . "\n";
        }
        exit(0);

    case 'book:lend':
        // Nota: qui è facile introdurre errori -> utile per i corsisti
        $bookId = $args[1] ?? '';
        $memberId = $args[2] ?? '';

        if ($bookId === '' || $memberId === '') {
            echo "Uso: php bin/console.php book:lend <BOOK_ID> <MEMBER_ID>\n";
            exit(1);
        }

        echo $service->lendBook($bookId, $memberId, $todayYmd) . "\n";
        exit(0);

    case 'book:return':
        $bookId = $args[1] ?? '';
        if ($bookId === '') {
            echo "Uso: php bin/console.php book:return <BOOK_ID>\n";
            exit(1);
        }

        echo $service->returnBook($bookId, $todayYmd) . "\n";
        exit(0);

    
case 'members:list':
    $members = $service->listMembers();

    if (empty($members)) {
        echo "Nessun membro.\n";
    } else {
        echo "ID | NOME\n";
        foreach ($members as $member) {
            echo "{$member->id()} | {$member->fullName()}\n";
        }
    }
    exit(0);





    default:
        echo "Comando sconosciuto: $command\n";
        echo "Suggerimento: php bin/console.php help\n";
        exit(1);
}
