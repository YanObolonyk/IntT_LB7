<?php
include 'connection.php';

header('Content-Type: application/json'); // встановлюємо заголовок відповіді як JSON

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_GET['author_id'])) {
        $author_id = (int) $_GET['author_id'];

        $author_sql = "SELECT NAME FROM author WHERE Id = :author_id";
        $author_stmt = $pdo->prepare($author_sql);
        $author_stmt->bindParam(':author_id', $author_id, PDO::PARAM_INT);
        $author_stmt->execute();
        $author = $author_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$author) {
            echo json_encode(["error" => "Автор не знайдений."]); // якщо не знайдено, повертаємо JSON з помилкою
            exit;
        }

        $sql = "SELECT l.NAME, l.YEAR, l.ISBN, l.QUANTITY, l.LITERATE
                FROM literature l
                LEFT JOIN book_authrs ba ON l.Id = ba.FID_BOOK
                LEFT JOIN author a ON ba.FID_AUTH = a.Id
                WHERE a.Id = :author_id
                GROUP BY l.Id
                ORDER BY l.NAME";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':author_id', $author_id, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["author_name" => $author['NAME'], "books" => $books]);
    } else {
        echo json_encode(["error" => "Будь ласка, виберіть автора."]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Помилка запиту: " . $e->getMessage()]);
}
?>
