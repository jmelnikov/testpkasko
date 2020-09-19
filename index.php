<?php
$start = microtime(true);
require_once ('GetBooksClass.php');

$booksClass = new GetBooksClass();
if(!$booksClass->run()) {
    echo 'Some error';
} else {
    $books = $booksClass->getBooks();
    require_once('template.html');
}

echo PHP_EOL, PHP_EOL, 'Execution time: ', number_format(microtime(true)-$start, 4, '.', ' ');
