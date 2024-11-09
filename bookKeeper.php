<?php
    $files = scandir('_ksiazki');
    $bookDataExists = file_exists('_ksiazki/bookData.json');
    if (count($files) > 2 && $bookDataExists) {
        echo "Książki znalezione!";
    } else {
        echo "Brak książek lub pliku bookData.json!";
    }
?>