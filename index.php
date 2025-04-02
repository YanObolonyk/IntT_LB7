<?php
include 'connection.php';

try {
    $pdo = new PDO($dsn, $user, $pass); // php data object для підключення до бд
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // режим обробки помилок
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ua">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LB7</title>
    <style>
    select {
        background-color: lightgray;
        border-radius: 5px;
    }
    </style>
    <script>
        function fetchResults() {
            var publisher = document.getElementById("publisher").value; 
            var xhr = new XMLHttpRequest(); // об'єкт XMLHttpRequest для відправки HTTP-запиту
            xhr.open("GET", "get1.php?publisher=" + encodeURIComponent(publisher), true); // передаємо значення publisher як параметр у URL
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) { // запит завершено і успішно отримано відповідь
                    document.getElementById("result1").innerHTML = xhr.responseText; // вставляємо в result1
                }
            };
            xhr.send();
        }
        function fetchBooks() {
            var startYear = document.getElementById('start_year').value;
            var endYear = document.getElementById('end_year').value;
            
            if (startYear && endYear) {
                var xhr = new XMLHttpRequest(); // новий об'єкт XMLHttpRequest
                xhr.open('GET', 'get2.php?start_year=' + startYear + '&end_year=' + endYear, true); // відкриваємо GET-запит на сервер
                xhr.setRequestHeader('Content-Type', 'application/xml'); // заголовок запиту
                
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var xml = xhr.responseXML; // отримуємо XML-відповідь від сервера
                        var table = '<table border="1">' +
                                    '<tr style="background-color: Blue; color: white;">' +
                                    '<th>Рік</th><th>Назва</th><th>ISBN</th><th>Кількість</th><th>Жанр</th><th>Автор</th></tr>';
                        
                        var books = xml.getElementsByTagName('book'); // отримуємо всі елементи <book> з XML
                        for (var i = 0; i < books.length; i++) { // перебираємо всі знайдені книги
                            table += '<tr>' +
                                     '<td>' + books[i].getElementsByTagName('year')[0].textContent + '</td>' +
                                     '<td>' + books[i].getElementsByTagName('name')[0].textContent + '</td>' +
                                     '<td>' + books[i].getElementsByTagName('isbn')[0].textContent + '</td>' +
                                     '<td>' + books[i].getElementsByTagName('quantity')[0].textContent + '</td>' +
                                     '<td>' + books[i].getElementsByTagName('literate')[0].textContent + '</td>' +
                                     '<td>' + books[i].getElementsByTagName('authors')[0].textContent + '</td>' +
                                     '</tr>';
                        }
                        table += '</table>';
                        document.getElementById('result2').innerHTML = table; // вставляємо таблицю в HTML
                    }
                };
                xhr.send(); // відправляємо запит
            } else {
                alert('Будь ласка, введіть коректні роки.');
            }
        }
    </script>

</head>
<body>
    <form onsubmit="event.preventDefault(); fetchResults();"> <!-- у форматі простого тексту -->
        <label for="publisher">Оберіть назву видавництва:</label>
        <select name="publisher" id="publisher">
            <?php 
            $query = "SELECT DISTINCT PUBLISHER FROM literature ORDER BY PUBLISHER";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $publishers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($publishers as $publisher): ?>
                <option value="<?= htmlspecialchars($publisher) ?>"> <?= htmlspecialchars($publisher) ?> </option> 
            <?php endforeach; ?>
        </select>
        <br><br>
        <button type="submit">Результати пошуку</button>
    </form>
    <br>
    <div id="result1"></div>


    <form onsubmit="event.preventDefault(); fetchBooks();"> <!-- у форматі XML -->
        <label for="start_year">Початковий рік:</label>
        <input type="number" id="start_year" min="1900" max="2100">
        <br>
        <label for="end_year">Кінцевий рік:</label>
        <input type="number" id="end_year" min="1900" max="2100">
        <br><br>
        <button type="submit">Результати пошуку</button>
    </form>
    <div id="result2"></div>

    <br><br>    

    <form id="authorForm"> <!-- у форматі JSON -->
        <label for="author">Оберіть автора:</label>
        <select name="author_id" id="author">
            <?php
            $stmt = $pdo->query("SELECT Id, NAME FROM author ORDER BY NAME ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<option value="' . htmlspecialchars($row['Id']) . '">' . htmlspecialchars($row['NAME']) . '</option>';
            }
            ?>
        </select>
        <br><br>
        <button type="submit">Результати пошуку</button>
    </form>

    <div id="result3"></div>

    <script>
    document.getElementById("authorForm").addEventListener("submit", function(event) {
        event.preventDefault();
        let authorId = document.getElementById("author").value;

        fetch("get3.php?author_id=" + authorId) // відправляємо GET-запит на сервер
            .then(response => response.json()) // отримуємо відповідь у форматі JSON
            .then(data => {
                let resultDiv = document.getElementById("result3");
                resultDiv.innerHTML = "";

                if (data.error) {
                    resultDiv.innerHTML = "<p>" + data.error + "</p>";  // якщо помилка, виводимо 
                } else {
                    let table = "<h2>Книги автора: " + data.author_name + "</h2>"; // формуємо таблицю
                    table += "<table style='border: 1px solid black;'><tr style='background-color: Blue; color: white;'><th>Назва</th><th>Рік</th><th>ISBN</th><th>Кількість</th><th>Жанр</th></tr>";
                    data.books.forEach(book => {
                        table += `<tr>
                                    <td>${book.NAME}</td>
                                    <td>${book.YEAR}</td>
                                    <td>${book.ISBN}</td>
                                    <td>${book.QUANTITY}</td>
                                    <td>${book.LITERATE}</td>
                                  </tr>`;
                    });
                    table += "</table>";
                    resultDiv.innerHTML = table; // вставляємо таблицю в HTML
                }
            })
            .catch(error => {
                document.getElementById("result3").innerHTML = "<p>Помилка при отриманні даних</p>";
            });
    });
    </script>
</body>
</html>