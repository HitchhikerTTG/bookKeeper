<?php

// Sprawdzenie czy pliki json z danymi istnieją. Jeśli nie, to tworzymy je.
if (!file_exists('_ksiazki/authors.json')) {
    file_put_contents('_ksiazki/authors.json', '[]');
}

if (!file_exists('_ksiazki/genres.json')) {
    file_put_contents('_ksiazki/genres.json', '[]');
}

if (!file_exists('_ksiazki/series.json')) {
    file_put_contents('_ksiazki/series.json', '[]');
}

// Wczytanie danych z plików json
$authors = json_decode(file_get_contents('_ksiazki/authors.json'), true) ?? [];
$genres = json_decode(file_get_contents('_ksiazki/genres.json'), true) ?? [];
$series = json_decode(file_get_contents('_ksiazki/series.json'), true) ?? [];
$bookData = json_decode(file_get_contents('_ksiazki/bookData.json'), true) ?? [];

// Funkcja do aktualizacji danych w plikach json
function updateJSON($filename, $value) {
    $data = json_decode(file_get_contents("_ksiazki/$filename.json"), true) ?? [];
    if (!in_array($value, $data)) {
        $data[] = $value;
        if (file_put_contents("_ksiazki/$filename.json", json_encode($data, JSON_PRETTY_PRINT)) === false) {
            // Obsługa błędu podczas zapisu do pliku
            echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: ' . $filename . '</div>';
        }
    }
}

// Funkcja do pobierania nazwy pliku mobi bez rozszerzenia
function getMobiFileName($filePath) {
    $parts = explode('.', basename($filePath));
    return $parts[0];
}

// Funkcja do generowania tabeli z książkami
function generateBookTable($books, $page, $perPage, $isMetadata = true) {
    $start = ($page - 1) * $perPage;
    $end = min($start + $perPage, count($books));
    $booksToDisplay = array_slice($books, $start, $end);

    if ($isMetadata) {
        $table = '
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tytuł</th>
                        <th>Autor</th>
                        <th>Edytuj</th>
                    </tr>
                </thead>
                <tbody>
        ';
        foreach ($booksToDisplay as $book) {
            $table .= '
                <tr>
                    <td>' . $book['title'] . '</td>
                    <td>[' . $book['author'] . ']</td>
                    <td><a href="#editBookModal" data-toggle="modal" data-book-id="' . getMobiFileName($book['mobiFile']) . '">Edytuj</a></td>
                </tr>
            ';
        }
        $table .= '
                </tbody>
            </table>
        ';
    } else {
        $table = '
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Plik Mobi</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
        ';
        foreach ($booksToDisplay as $book) {
            $table .= '
                <tr>
                    <td>' . $book . '</td>
                    <td><a href="#addBookModal" data-toggle="modal" data-book-id="' . $book . '">Dodaj metadane</a></td>
                </tr>
            ';
        }
        $table .= '
                </tbody>
            </table>
        ';
    }

    // Generowanie paginacji
    $totalPages = ceil(count($books) / $perPage);
    $pagination = '
        <nav aria-label="Page navigation example">
            <ul class="pagination">
    ';
    for ($i = 1; $i <= $totalPages; $i++) {
        $pagination .= '
            <li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>
        ';
    }
    $pagination .= '
            </ul>
        </nav>
    ';

    return $table . $pagination;
}

// Pobranie listy plików mobi z katalogu
$mobiFiles = glob('_ksiazki/*.mobi');
$mobiFiles = array_map('basename', $mobiFiles);

// Sprawdzenie czy plik bookData.json istnieje i wczytanie danych z niego
$bookDataExists = file_exists('_ksiazki/bookData.json');
if ($bookDataExists) {
    $bookData = json_decode(file_get_contents('_ksiazki/bookData.json'), true) ?? [];
}

// Pobranie listy książek z metadanymi i bez metadanych
$booksWithMetadata = [];
$booksWithoutMetadata = [];
foreach ($mobiFiles as $mobiFile) {
    $mobiFileName = getMobiFileName($mobiFile);
    if (isset($bookData[$mobiFileName])) {
        $booksWithMetadata[] = $bookData[$mobiFileName];
    } else {
        $booksWithoutMetadata[] = $mobiFile;
    }
}

// Pobranie numeru strony z adresu URL
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 15;

// Generowanie statusu
$status = '
    <div class="alert alert-info" role="alert">
        Status:
        <ul>
            <li>Liczba książek: ' . count($mobiFiles) . '</li>
            <li>Plik bookData.json: ' . ($bookDataExists ? 'Tak' : 'Nie') . '</li>
            <li>Metadane dla: ' . count($booksWithMetadata) . ' / ' . count($mobiFiles) . ' książek</li>
        </ul>
    </div>
';

// Generowanie zakładek
$tabs = '
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="withMetadata-tab" data-toggle="tab" href="#withMetadata" role="tab" aria-controls="withMetadata" aria-selected="true">Książki z metadanymi</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="withoutMetadata-tab" data-toggle="tab" href="#withoutMetadata" role="tab" aria-controls="withoutMetadata" aria-selected="false">Książki bez metadanych</a>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="withMetadata" role="tabpanel" aria-labelledby="withMetadata-tab">
            ' . generateBookTable($booksWithMetadata, $page, $perPage) . '
        </div>
        <div class="tab-pane fade" id="withoutMetadata" role="tabpanel" aria-labelledby="withoutMetadata-tab">
            ' . generateBookTable($booksWithoutMetadata, $page, $perPage, false) . '
        </div>
    </div>
';

// Formularz dodawania metadanych
$addBookForm = '
    <div class="modal fade" id="addBookModal" tabindex="-1" role="dialog" aria-labelledby="addBookModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBookModalLabel">Dodaj metadane</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addMetadataForm">
                        <input type="hidden" name="file" id="file">
                        <div class="form-group">
                            <label for="title">Tytuł:</label>
                            <input type="text" class="form-control" id="title" name="title">
                        </div>
                        <div class="form-group">
                            <label for="firstName">Imię autora:</label>
                            <input type="text" class="form-control" id="firstName" name="firstName">
                        </div>
                        <div class="form-group">
                            <label for="lastName">Nazwisko autora:</label>
                            <input type="text" class="form-control" id="lastName" name="lastName">
                        </div>
                        <div class="form-group">
                            <label for="newAuthor">Nowy autor:</label>
                            <input type="text" class="form-control" id="newAuthor" name="newAuthor">
                        </div>
                        <div class="form-group">
                            <label for="genre">Gatunek:</label>
                            <select class="form-control" id="genre" name="genre">
                                <option value="">Wybierz gatunek</option>
                                ';
                                foreach ($genres as $genre) {
                                    $addBookForm .= '<option value="' . $genre . '">' . $genre . '</option>';
                                }
                                $addBookForm .= '
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newGenre">Nowy gatunek:</label>
                            <input type="text" class="form-control" id="newGenre" name="newGenre">
                        </div>
                        <div class="form-group">
                            <label for="series">Cykl:</label>
                            <select class="form-control" id="series" name="series">
                                <option value="">Wybierz cykl</option>
                                ';
                                foreach ($series as $series) {
                                    $addBookForm .= '<option value="' . $series . '">' . $series . '</option>';
                                }
                                $addBookForm .= '
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newSeries">Nowy cykl:</label>
                            <input type="text" class="form-control" id="newSeries" name="newSeries">
                        </div>
                        <div class="form-group">
                            <label for="seriesPosition">Pozycja w cyklu:</label>
                            <input type="number" class="form-control" id="seriesPosition" name="seriesPosition">
                        </div>
                        <div class="form-group">
                            <label for="recommend">Polecam:</label>
                            <select class="form-control" id="recommend" name="recommend">
                                <option value="tak">Tak</option>
                                <option value="nie">Nie</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dateAdded">Data dodania:</label>
                            <input type="date" class="form-control" id="dateAdded" name="dateAdded">
                        </div>
                        <button type="submit" class="btn btn-primary">Zapisz</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
';

// Formularz edycji metadanych
$editBookForm = '
    <div class="modal fade" id="editBookModal" tabindex="-1" role="dialog" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBookModalLabel">Edytuj metadane</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editMetadataForm">
                        <input type="hidden" name="file" id="file">
                        <div class="form-group">
                            <label for="title">Tytuł:</label>
                            <input type="text" class="form-control" id="title" name="title">
                        </div>
                        <div class="form-group">
                            <label for="firstName">Imię autora:</label>
                            <input type="text" class="form-control" id="firstName" name="firstName">
                        </div>
                        <div class="form-group">
                            <label for="lastName">Nazwisko autora:</label>
                            <input type="text" class="form-control" id="lastName" name="lastName">
                        </div>
                        <div class="form-group">
                            <label for="newAuthor">Nowy autor:</label>
                            <input type="text" class="form-control" id="newAuthor" name="newAuthor">
                        </div>
                        <div class="form-group">
                            <label for="genre">Gatunek:</label>
                            <select class="form-control" id="genre" name="genre">
                                <option value="">Wybierz gatunek</option>
                                ';
                                foreach ($genres as $genre) {
                                    $editBookForm .= '<option value="' . $genre . '">' . $genre . '</option>';
                                }
                                $editBookForm .= '
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newGenre">Nowy gatunek:</label>
                            <input type="text" class="form-control" id="newGenre" name="newGenre">
                        </div>
                        <div class="form-group">
                            <label for="series">Cykl:</label>
                            <select class="form-control" id="series" name="series">
                                <option value="">Wybierz cykl</option>
                                ';
                                foreach ($series as $series) {
                                    $editBookForm .= '<option value="' . $series . '">' . $series . '</option>';
                                }
                                $editBookForm .= '
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newSeries">Nowy cykl:</label>
                            <input type="text" class="form-control" id="newSeries" name="newSeries">
                        </div>
                        <div class="form-group">
                            <label for="seriesPosition">Pozycja w cyklu:</label>
                            <input type="number" class="form-control" id="seriesPosition" name="seriesPosition">
                        </div>
                        <div class="form-group">
                            <label for="recommend">Polecam:</label>
                            <select class="form-control" id="recommend" name="recommend">
                                <option value="tak">Tak</option>
                                <option value="nie">Nie</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dateAdded">Data dodania:</label>
                            <input type="date" class="form-control" id="dateAdded" name="dateAdded">
                        </div>
                        <button type="submit" class="btn btn-primary">Zapisz</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
';

// Obsługa formularza dodawania metadanych
if (isset($_POST['addMetadataForm'])) {
    $file = $_POST['file'];
    $title = $_POST['title'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $genre = $_POST['genre'];
    $series = $_POST['series'];
    $seriesPosition = $_POST['seriesPosition'];
    $recommend = $_POST['recommend'];
    $dateAdded = $_POST['dateAdded'];

    // Dodanie autora, gatunku i serii do plików JSON
    $newAuthor = $_POST['newAuthor'];
    if (!empty($newAuthor)) {
        if (file_put_contents("_ksiazki/authors.json", json_encode(array_merge(json_decode(file_get_contents("_ksiazki/authors.json"), true), [$newAuthor]), JSON_PRETTY_PRINT)) === false) {
            echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: authors.json</div>';
        }
    }
    $newGenre = $_POST['newGenre'];
    if (!empty($newGenre)) {
        if (file_put_contents("_ksiazki/genres.json", json_encode(array_merge(json_decode(file_get_contents("_ksiazki/genres.json"), true), [$newGenre]), JSON_PRETTY_PRINT)) === false) {
            echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: genres.json</div>';
        }
    }
    $newSeries = $_POST['newSeries'];
    if (!empty($newSeries)) {
        if (file_put_contents("_ksiazki/series.json", json_encode(array_merge(json_decode(file_get_contents("_ksiazki/series.json"), true), [$newSeries]), JSON_PRETTY_PRINT)) === false) {
            echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: series.json</div>';
        }
    }

    // Dodanie metadanych do bookData.json
    $bookData[$file] = [
        'title' => $title,
        'author' => $firstName . ' ' . $lastName,
        'genre' => $genre,
        'series' => $series,
        'seriesPosition' => $seriesPosition,
        'recommend' => $recommend,
        'dateAdded' => $dateAdded,
        'httpLink' => 'http://' . $_SERVER['HTTP_HOST'] . '/_ksiazki/' . $file . '.mobi',
        'httpsLink' => 'https://' . $_SERVER['HTTP_HOST'] . '/_ksiazki/' . $file . '.mobi',
        'mobiFile' => $file . '.mobi'
    ];

    if (file_put_contents('_ksiazki/bookData.json', json_encode($bookData, JSON_PRETTY_PRINT)) === false) {
        // Obsługa błędu podczas zapisu do pliku
        echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: bookData.json</div>';
    } else {
        // Wyświetlenie komunikatu o zaktualizowaniu metadanych
        echo '<div class="alert alert-success" role="alert">Metadane zostały zaktualizowane. Pliki JSON zostały zaktualizowane.</div>';
    }
}

// Obsługa formularza edycji metadanych
if (isset($_POST['editMetadataForm'])) {
    $file = $_POST['file'];
    $title = $_POST['title'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $genre = $_POST['genre'];
    $series = $_POST['series'];
    $seriesPosition = $_POST['seriesPosition'];
    $recommend = $_POST['recommend'];
    $dateAdded = $_POST['dateAdded'];

    // Aktualizacja metadanych w bookData.json
    $bookData[$file] = [
        'title' => $title,
        'author' => $firstName . ' ' . $lastName,
        'genre' => $genre,
        'series' => $series,
        'seriesPosition' => $seriesPosition,
        'recommend' => $recommend,
        'dateAdded' => $dateAdded,
        'httpLink' => 'http://' . $_SERVER['HTTP_HOST'] . '/_ksiazki/' . $file . '.mobi',
        'httpsLink' => 'https://' . $_SERVER['HTTP_HOST'] . '/_ksiazki/' . $file . '.mobi',
        'mobiFile' => $file . '.mobi'
    ];

    // Dodanie autora, gatunku i serii do plików JSON
    $newAuthor = $_POST['newAuthor'];
    if (!empty($newAuthor)) {
        if (file_put_contents("_ksiazki/authors.json", json_encode(array_merge(json_decode(file_get_contents("_ksiazki/authors.json"), true), [$newAuthor]), JSON_PRETTY_PRINT)) === false) {
            echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: authors.json</div>';
        }
    }
    $newGenre = $_POST['newGenre'];
    if (!empty($newGenre)) {
        if (file_put_contents("_ksiazki/genres.json", json_encode(array_merge(json_decode(file_get_contents("_ksiazki/genres.json"), true), [$newGenre]), JSON_PRETTY_PRINT)) === false) {
            echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: genres.json</div>';
        }
    }
    $newSeries = $_POST['newSeries'];
    if (!empty($newSeries)) {
        if (file_put_contents("_ksiazki/series.json", json_encode(array_merge(json_decode(file_get_contents("_ksiazki/series.json"), true), [$newSeries]), JSON_PRETTY_PRINT)) === false) {
            echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: series.json</div>';
        }
    }

    if (file_put_contents('_ksiazki/bookData.json', json_encode($bookData, JSON_PRETTY_PRINT)) === false) {
        // Obsługa błędu podczas zapisu do pliku
        echo '<div class="alert alert-danger" role="alert">Błąd podczas aktualizacji pliku JSON: bookData.json</div>';
    } else {
        // Wyświetlenie komunikatu o zaktualizowaniu metadanych
        echo '<div class="alert alert-success" role="alert">Metadane zostały zaktualizowane. Pliki JSON zostały zaktualizowane.</div>';
    }
}

// Wyświetlenie statusu, zakładek, formularzy i tabeli
echo $status;
echo $tabs;
echo $addBookForm;
echo $editBookForm;

// Dodanie skryptu JavaScript do obsługi formularzy i stronnicowania
echo '
    <script>
        $(document).ready(function() {
            // Obsługa formularza dodawania metadanych
            $("#addMetadataForm").submit(function(event) {
                event.preventDefault();
                var file = $("#file").val();
                var title = $("#title").val();
                var firstName = $("#firstName").val();
                var lastName = $("#lastName").val();
                var genre = $("#genre").val();
                var series = $("#series").val();
                var seriesPosition = $("#seriesPosition").val();
                var recommend = $("#recommend").val();
                var dateAdded = $("#dateAdded").val();
                var newAuthor = $("#newAuthor").val();
                var newGenre = $("#newGenre").val();
                var newSeries = $("#newSeries").val();
                $.ajax({
                    url: "bookManager.php",
                    type: "POST",
                    data: {
                        addMetadataForm: 1,
                        file: file,
                        title: title,
                        firstName: firstName,
                        lastName: lastName,
                        genre: genre,
                        series: series,
                        seriesPosition: seriesPosition,
                        recommend: recommend,
                        dateAdded: dateAdded,
                        newAuthor: newAuthor,
                        newGenre: newGenre,
                        newSeries: newSeries
                    },
                    success: function(response) {
                        $("#addBookModal").modal("hide");
                        // Odświeżenie strony po dodaniu metadanych
                        location.reload();
                    }
                });
            });

            // Obsługa formularza edycji metadanych
            $("#editMetadataForm").submit(function(event) {
                event.preventDefault();
                var file = $("#file").val();
                var title = $("#title").val();
                var firstName = $("#firstName").val();
                var lastName = $("#lastName").val();
                var genre = $("#genre").val();
                var series = $("#series").val();
                var seriesPosition = $("#seriesPosition").val();
                var recommend = $("#recommend").val();
                var dateAdded = $("#dateAdded").val();
                var newAuthor = $("#newAuthor").val();
                var newGenre = $("#newGenre").val();
                var newSeries = $("#newSeries").val();
                $.ajax({
                    url: "bookManager.php",
                    type: "POST",
                    data: {
                        editMetadataForm: 1,
                        file: file,
                        title: title,
                        firstName: firstName,
                        lastName: lastName,
                        genre: genre,
                        series: series,
                        seriesPosition: seriesPosition,
                        recommend: recommend,
                        dateAdded: dateAdded,
                        newAuthor: newAuthor,
                        newGenre: newGenre,
                        newSeries: newSeries
                    },
                    success: function(response) {
                        $("#editBookModal").modal("hide");
                        // Odświeżenie strony po edycji metadanych
                        location.reload();
                    }
                });
            });

            // Obsługa modalnego okna edycji metadanych
            $("#editBookModal").on("show.bs.modal", function(event) {
                var button = $(event.relatedTarget);
                var bookId = button.data("book-id");
                var modal = $(this);
                modal.find("#file").val(bookId);
                modal.find("#title").val(bookData[bookId].title);
                var authorParts = bookData[bookId].author.split(' ');
                modal.find("#firstName").val(authorParts[0]);
                modal.find("#lastName").val(authorParts[1]);
                modal.find("#genre").val(bookData[bookId].genre);
                modal.find("#series").val(bookData[bookId].series);
                modal.find("#seriesPosition").val(bookData[bookId].seriesPosition);
                modal.find("#recommend").val(bookData[bookId].recommend);
                modal.find("#dateAdded").val(bookData[bookId].dateAdded);
            });

            // Obsługa modalnego okna dodawania metadanych
            $("#addBookModal").on("show.bs.modal", function(event) {
                var button = $(event.relatedTarget);
                var bookId = button.data("book-id");
                var modal = $(this);
                modal.find("#file").val(bookId);
            });
        });
    </script>
';

// Wczytanie Bootstrap z CDN
echo '
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
';

?>