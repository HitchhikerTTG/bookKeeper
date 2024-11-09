<?php
    $files = scandir('_ksiazki');
    $bookDataExists = file_exists('_ksiazki/bookData.json');
?>

<!DOCTYPE html>
<html>
<head>
    <title>BookKeeper</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <div class="alert alert-info" role="alert">
            <div id="status"></div>
        </div>

        <?php
        if (count($files) > 2 && $bookDataExists) {
            echo "Książki znalezione!";
        } else {
            echo "Brak książek lub pliku bookData.json!";
        }
        ?>
    </div>

    <script>
        // Pobranie danych
        const files = <?php echo json_encode(scandir('_ksiazki')); ?>;
        const bookDataExists = <?php echo json_encode(file_exists('_ksiazki/bookData.json')); ?>;

        // Obliczenie liczby książek
        const bookCount = files.length - 2; // Odejmujemy 2 dla "." i ".."
        let metaDataCount = 0;
        if (bookDataExists) {
            // Wczytanie danych z pliku JSON (nie pokazane w przykładzie)
            // ...
            // Zakładamy, że metaDataCount jest ustawiony
        }

        // Tworzenie tekstu statusu
        const statusText = `Liczba książek: ${bookCount} | Plik JSON: ${bookDataExists ? 'tak' : 'nie'} | Metadane dla: ${metaDataCount}/${bookCount} książek`;

        // Ustawienie tekstu w elemencie HTML
        document.getElementById('status').innerHTML = statusText;
    </script>
</body>
</html>