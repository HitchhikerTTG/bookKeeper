<?php
    $files = scandir('_ksiazki');
    $bookData = json_decode(file_get_contents('_ksiazki/bookData.json'), true) ?? [];

    // Generowanie pliku ksiazki.php
    if (isset($_POST['generateKsiazki'])) {
        // Wczytanie danych z pliku bookData.json
        $bookData = json_decode(file_get_contents('_ksiazki/bookData.json'), true);

        // Pobranie pola sortowania z formularza
        $sortField = $_POST['sortField'];

        // Sortowanie danych
        usort($bookData, function($a, $b) use ($sortField) {
            return strcasecmp($a[$sortField], $b[$sortField]);
        });

        // Tworzenie treści pliku ksiazki.php
        $ksiazkiContent = '<!DOCTYPE html>
        <html>
        <head>
            <title>Lista Książek</title>
        </head>
        <body>
            <h1>Lista Książek</h1>
            <ul>';

        // Pętla po książkach
        foreach ($bookData as $book) {
            $ksiazkiContent .= '<li>
                <h2>' . $book['title'] . '</h2>
                <p><strong>Cykl:</strong> ' . $book['cycle'] . '</p>
                <p><strong>Autor:</strong> ' . $book['author'] . '</p>
                <p><strong>Gatunek:</strong> ' . $book['genre'] . '</p>
                <p><strong>Data dodania:</strong> ' . $book['dateAdded'] . '</p>
                <p><strong>Polecam:</strong> ' . $book['recommend'] . '</p>
                <p><strong>Link HTTP (Mobi):</strong> <a href="' . $book['httpLink'] . '">' . $book['httpLink'] . '</a></p>
                <p><strong>Link HTTPS (Mobi):</strong> <a href="' . $book['httpsLink'] . '">' . $book['httpsLink'] . '</a></p>
            </li>';
        }

        $ksiazkiContent .= '</ul>
        </body>
        </html>';

        // Zapisanie treści do pliku ksiazki.php
        file_put_contents('ksiazki.php', $ksiazkiContent);

        echo "Plik ksiazki.php został wygenerowany!";
    }

    // Obsługa formularza dodawania książki
    if (isset($_POST['addBookForm'])) {
        // Pobranie danych z formularza
        $title = $_POST['title'];
        $cycle = $_POST['cycle'];
        $author = $_POST['author'];
        $genre = $_POST['genre'];
        $dateAdded = $_POST['dateAdded'];
        $recommend = $_POST['recommend'];
        $httpLink = $_POST['httpLink'];
        $httpsLink = $_POST['httpsLink'];
        $mobiFile = $_FILES['mobiFile']['name'];

        // Przetworzenie pliku mobi
        move_uploaded_file($_FILES['mobiFile']['tmp_name'], '_ksiazki/' . $mobiFile);

        // Stworzenie obiektu książki
        $newBook = [
            'title' => $title,
            'cycle' => $cycle,
            'author' => $author,
            'genre' => $genre,
            'dateAdded' => $dateAdded,
            'recommend' => $recommend,
            'httpLink' => $httpLink,
            'httpsLink' => $httpsLink,
            'mobiFile' => $mobiFile 
        ];

        // Sprawdzenie, czy plik bookData.json istnieje
        if ($bookDataExists) {
            // Wczytanie danych z pliku bookData.json
            $bookData = json_decode(file_get_contents('_ksiazki/bookData.json'), true);

            // Dodanie nowej książki do tablicy
            $bookData[] = $newBook;

            // Zapisanie zaktualizowanych danych do pliku bookData.json
            file_put_contents('_ksiazki/bookData.json', json_encode($bookData, JSON_PRETTY_PRINT));
        } else {
            // Tworzenie nowego pliku bookData.json z nową książką
            file_put_contents('_ksiazki/bookData.json', json_encode([$newBook], JSON_PRETTY_PRINT));
        }
    }

    // Lista plików MOBI bez metadanych
    $mobiFilesWithoutMetadata = [];
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'mobi' && !array_key_exists($file, $bookData)) {
            $mobiFilesWithoutMetadata[] = $file;
        }
    }

    // Stronnkowanie
    $itemsPerPage = 10;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $totalPages = ceil(count($mobiFilesWithoutMetadata) / $itemsPerPage);
    $start = ($currentPage - 1) * $itemsPerPage;
    $end = min($start + $itemsPerPage, count($mobiFilesWithoutMetadata));
    $mobiFilesWithoutMetadata = array_slice($mobiFilesWithoutMetadata, $start, $end);

    // Wyświetl tabelę
    echo '<table>';
    echo '<thead><tr><th>Plik MOBI</th><th>Edytuj</th></tr></thead>';
    echo '<tbody>';
    foreach ($mobiFilesWithoutMetadata as $file) {
        echo '<tr>';
        echo '<td>' . $file . '</td>';
        echo '<td><a href="?action=edit&file=' . urlencode($file) . '">Edytuj</a></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';

    // Dodaj paginację
    if ($totalPages > 1) {
        echo '<div class="pagination">';
        for ($i = 1; $i <= $totalPages; $i++) {
            echo '<a href="?page=' . $i . '">' . $i . '</a>';
        }
        echo '</div>';
    }

    // Formularz edycji
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['file'])) {
        $file = $_GET['file'];
        $book = $bookData[$file] ?? [];
        echo '<form method="POST" action="?action=save&file=' . urlencode($file) . '">';
        echo '<input type="hidden" name="file" value="' . $file . '">';
        echo '<div class="form-group">';
        echo '<label for="title">Tytuł:</label>';
        echo '<input type="text" class="form-control" id="title" name="title" value="' . htmlspecialchars($book['title']) . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="cycle">Cykl:</label>';
        echo '<input type="text" class="form-control" id="cycle" name="cycle" value="' . htmlspecialchars($book['cycle']) . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="author">Autor:</label>';
        echo '<input type="text" class="form-control" id="author" name="author" value="' . htmlspecialchars($book['author']) . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="genre">Gatunek:</label>';
        echo '<input type="text" class="form-control" id="genre" name="genre" value="' . htmlspecialchars($book['genre']) . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="dateAdded">Data dodania:</label>';
        echo '<input type="date" class="form-control" id="dateAdded" name="dateAdded" value="' . htmlspecialchars($book['dateAdded']) . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="recommend">Polecam:</label>';
        echo '<select class="form-control" id="recommend" name="recommend">';
        echo '<option value="tak"' . ($book['recommend'] === 'tak' ? ' selected' : '') . '>Tak</option>';
        echo '<option value="nie"' . ($book['recommend'] === 'nie' ? ' selected' : '') . '>Nie</option>';
        echo '</select>';
        echo '</div>';
        echo '<button type="submit" class="btn btn-primary">Zapisz</button>';
        echo '</form>';
    }

    // Zapisywanie danych
    if (isset($_POST['action']) && $_POST['action'] === 'save' && isset($_POST['file'])) {
        $file = $_POST['file'];
        $bookData = json_decode(file_get_contents('_ksiazki/bookData.json'), true) ?? [];
        $bookData[$file] = [
            'title' => $_POST['title'],
            'cycle' => $_POST['cycle'],
            'author' => $_POST['author'],
            'genre' => $_POST['genre'],
            'dateAdded' => $_POST['dateAdded'],
            'recommend' => $_POST['recommend'],
            'httpLink' => 'http://' . $_SERVER['HTTP_HOST'] . '/_ksiazki/' . $file,
            'httpsLink' => 'https://' . $_SERVER['HTTP_HOST'] . '/_ksiazki/' . $file
        ];
        file_put_contents('_ksiazki/bookData.json', json_encode($bookData, JSON_PRETTY_PRINT));
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // ... (reszta kodu) ...

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

        <h2>Dodaj nową książkę</h2>
        <form id="addBookForm" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="mobiFile">Plik Mobi:</label>
                <input type="file" class="form-control-file" id="mobiFile" name="mobiFile">
            </div>
            <div class="form-group">
                <label for="title">Tytuł:</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="cycle">Cykl:</label>
                <input type="text" class="form-control" id="cycle" name="cycle">
            </div>
            <div class="form-group">
                <label for="author">Autor:</label>
                <input type="text" class="form-control" id="author" name="author" required>
            </div>
            <div class="form-group">
                <label for="genre">Gatunek:</label>
                <input type="text" class="form-control" id="genre" name="genre" required>
            </div>
            <div class="form-group">
                <label for="dateAdded">Data dodania:</label>
                <input type="date" class="form-control" id="dateAdded" name="dateAdded" required>
            </div>
            <div class="form-group">
                <label for="recommend">Polecam:</label>
                <select class="form-control" id="recommend" name="recommend">
                    <option value="tak">Tak</option>
                    <option value="nie">Nie</option>
                </select>
            </div>
            <div class="form-group">
                <label for="httpLink">Link HTTP (Mobi):</label>
                <input type="text" class="form-control" id="httpLink" name="httpLink" required>
            </div>
            <div class="form-group">
                <label for="httpsLink">Link HTTPS (Mobi):</label>
                <input type="text" class="form-control" id="httpsLink" name="httpsLink" required>
            </div>
            <button type="submit" class="btn btn-primary" name="addBookForm">Dodaj książkę</button>
        </form>

        <!-- Form for generating ksiazki.php -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="sortField">Sortuj po:</label>
                <select class="form-control" id="sortField" name="sortField">
                    <option value="title">Tytuł</option>
                    <option value="cycle">Cykl</option>
                    <option value="author">Autor</option>
                    <option value="genre">Gatunek</option>
                    <option value="dateAdded">Data dodania</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" name="generateKsiazki">Generuj ksiazki.php</button>
        </form>

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

        // Obsługa formularza dodawania książki
        document.getElementById('addBookForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Zapobieganie domyślnemu przesyłaniu formularza

            // Pobranie danych z formularza
            const title = document.getElementById('title').value;
            const cycle = document.getElementById('cycle').value;
            const author = document.getElementById('author').value;
            const genre = document.getElementById('genre').value;
            const dateAdded = document.getElementById('dateAdded').value;
            const recommend = document.getElementById('recommend').value;
            const httpLink = document.getElementById('httpLink').value;
            const httpsLink = document.getElementById('httpsLink').value;
            const mobiFile = document.getElementById('mobiFile').files[0]; // Pobranie pliku mobi

            // Stworzenie obiektu książki
            const newBook = {
                title: title,
                cycle: cycle,
                author: author,
                genre: genre,
                dateAdded: dateAdded,
                recommend: recommend,
                httpLink: httpLink,
                httpsLink: httpsLink,
                mobiFile: mobiFile.name // Dodanie nazwy pliku mobi do obiektu książki
            };

            // Sprawdzenie, czy plik bookData.json istnieje
            if (bookDataExists) {
                // Wczytanie danych z pliku bookData.json
                fetch('_ksiazki/bookData.json')
                    .then(response => response.json())
                    .then(data => {
                        // Dodanie nowej książki do tablicy
                        data.push(newBook);

                        // Zapisanie zaktualizowanych danych do pliku bookData.json
                        const jsonData = JSON.stringify(data, null, 2);
                        fetch('_ksiazki/bookData.json', {
                            method: 'POST',
                            body: jsonData,
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(() => {
                            // Odświeżenie strony po dodaniu książki
                            location.reload();
                        })
                        .catch(error => console.error('Błąd podczas zapisywania do pliku:', error));
                    })
                    .catch(error => console.error('Błąd podczas wczytywania danych:', error));
            } else {
                // Tworzenie nowego pliku bookData.json z nową książką
                const jsonData = JSON.stringify([newBook], null, 2);
                fetch('_ksiazki/bookData.json', {
                    method: 'POST',
                    body: jsonData,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(() => {
                    // Odświeżenie strony po dodaniu książki
                    location.reload();
                })
                .catch(error => console.error('Błąd podczas tworzenia pliku:', error));
            }
        });

        // Obsługa edycji książki
        function editBook(index) {
            // Pobranie danych z pliku bookData.json
            fetch('_ksiazki/bookData.json')
                .then(response => response.json())
                .then(data => {
                    // Pobranie danych książki do edycji
                    const bookToEdit = data[index];

                    // Utworzenie formularza do edycji
                    const editForm = document.createElement('form');
                    editForm.id = 'editBookForm';
                    editForm.innerHTML = `
                        <div class="form-group">
                            <label for="title">Tytuł:</label>
                            <input type="text" class="form-control" id="title" name="title" value="${bookToEdit.title}" required>
                        </div>
                        <div class="form-group">
                            <label for="cycle">Cykl:</label>
                            <input type="text" class="form-control" id="cycle" name="cycle" value="${bookToEdit.cycle}">
                        </div>
                        <div class="form-group">
                            <label for="author">Autor:</label>
                            <input type="text" class="form-control" id="author" name="author" value="${bookToEdit.author}" required>
                        </div>
                        <div class="form-group">
                            <label for="genre">Gatunek:</label>
                            <input type="text" class="form-control" id="genre" name="genre" value="${bookToEdit.genre}" required>
                        </div>
                        <div class="form-group">
                            <label for="dateAdded">Data dodania:</label>
                            <input type="date" class="form-control" id="dateAdded" name="dateAdded" value="${bookToEdit.dateAdded}" required>
                        </div>
                        <div class="form-group">
                            <label for="recommend">Polecam:</label>
                            <select class="form-control" id="recommend" name="recommend">
                                <option value="tak" ${bookToEdit.recommend === 'tak' ? 'selected' : ''}>Tak</option>
                                <option value="nie" ${bookToEdit.recommend === 'nie' ? 'selected' : ''}>Nie</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="httpLink">Link HTTP (Mobi):</label>
                            <input type="text" class="form-control" id="httpLink" name="httpLink" value="${bookToEdit.httpLink}" required>
                        </div>
                        <div class="form-group">
                            <label for="httpsLink">Link HTTPS (Mobi):</label>
                            <input type="text" class="form-control" id="httpsLink" name="httpsLink" value="${bookToEdit.httpsLink}" required>
                        </div>
                        <input type="hidden" name="editIndex" value="${index}"> 
                        <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    `;

                    // Dodanie formularza do strony
                    document.getElementById('status').appendChild(editForm);

                    // Obsługa formularza edycji
                    editForm.addEventListener('submit', function(event) {
                        event.preventDefault();

                        // Pobranie danych z formularza
                        const title = document.getElementById('title').value;
                        const cycle = document.getElementById('cycle').value;
                        const author = document.getElementById('author').value;
                        const genre = document.getElementById('genre').value;
                        const dateAdded = document.getElementById('dateAdded').value;
                        const recommend = document.getElementById('recommend').value;
                        const httpLink = document.getElementById('httpLink').value;
                        const httpsLink = document.getElementById('httpsLink').value;
                        const editIndex = document.querySelector('input[name="editIndex"]').value; // Pobranie indexu książki do edycji

                        // Zaktualizowanie danych książki
                        data[editIndex] = {
                            title: title,
                            cycle: cycle,
                            author: author,
                            genre: genre,
                            dateAdded: dateAdded,
                            recommend: recommend,
                            httpLink: httpLink,
                            httpsLink: httpsLink
                        };

                        // Zapisanie zaktualizowanych danych do pliku bookData.json
                        const jsonData = JSON.stringify(data, null, 2);
                        fetch('_ksiazki/bookData.json', {
                            method: 'POST',
                            body: jsonData,
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(() => {
                            // Odświeżenie strony po dodaniu książki
                            location.reload();
                        })
                        .catch(error => console.error('Błąd podczas zapisywania do pliku:', error));
                    });
                })
                .catch(error => console.error('Błąd podczas wczytywania danych:', error));
        }
    </script>
</body>
</html>