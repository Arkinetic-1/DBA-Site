<?php

$host = 'localhost';
$port = '5432';
$dbname = 'projectdb';
$user = 'postgres';
$password = 'REGULATORdriver999';

try {

    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
} catch (PDOException $e) {

    die('Database connection failed.');
}


// ----------------------------------------------------
// CREATE
// ----------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'create'
) {

    $stmt = $pdo->prepare(
        'INSERT INTO borrow_transactions
            (borrower_id, borrow_date, due_date, status)
         VALUES
            (:borrower_id, :borrow_date, :due_date, :status)'
    );

    $stmt->execute([
        ':borrower_id' => (int)($_POST['borrower_id'] ?? 1),
        ':borrow_date' => trim($_POST['borrow_date'] ?? ''),
        ':due_date' => trim($_POST['due_date'] ?? ''),
        ':status' => trim($_POST['status'] ?? '')
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------------------------------
// UPDATE
// ----------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update'
) {

    $stmt = $pdo->prepare(
        'UPDATE borrow_transactions
         SET borrower_id = :borrower_id,
             borrow_date = :borrow_date,
             due_date = :due_date,
             status = :status
         WHERE borrow_id = :borrow_id'
    );

    $stmt->execute([
        ':borrower_id' => trim($_POST['borrower_id'] ?? ''),
        ':borrow_date' => trim($_POST['borrow_date'] ?? ''),
        ':due_date' => trim($_POST['due_date'] ?? ''),
        ':status' => trim($_POST['status'] ?? ''),
        ':borrow_id' => (int)($_POST['borrow_id'] ?? 0)
    ]);


    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


// ----------------------------------------------------
// DELETE
// ----------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'delete'
) {

    $stmt = $pdo->prepare(
        'DELETE FROM borrow_transactions
         WHERE borrow_id = :borrow_id'
    );

    $stmt->execute([
        ':borrow_id' => (int)($_POST['borrow_id'] ?? 0)
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


// ----------------------------------------------------
// READ
// ----------------------------------------------------

$stmt = $pdo->query(
    'SELECT borrow_id, borrower_id, borrow_date, due_date, status
     FROM borrow_transactions
     ORDER BY borrow_id DESC'
);

$borrow_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>BORROWER'S CRUD</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 30px auto;
            padding: 0 15px;
        }

        form {
            margin-bottom: 20px;
        }

        input,
        button {
            padding: 8px;
            margin: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
            list-style: none;
            display: flex;
        }

        .nav-links li {
            margin-left: 20px;
        }

        .nav-links a {
            text-decoration: none;
        }
    </style>

</head>

<body>
    <nav class="navbar">
        <h1>BORROWER'S CRUD</h1>
        <ul class="nav-links">
            <li><a href="week6.php">Week 6</a></li>
            <li><a href="week7.php">Week 7</a></li>
            <li><a href="week8.php">Week 8</a></li>
            <li><a href="week10.php">Week 10</a></li>
        </ul>
    </nav>
    <div class="col-12 d-flex justify-content-between align-items-center">
        <a href="borrower.php">Borrowers</a>
        <a href="equipment.php">Equipment</a>
        <a href="#">Transactions</a>
        <a href="records.php">Records</a>
    </div>
    <hr>


    <h2>ADD TRANSACTION </h2>

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="create">

        <input
            type="text"
            name="borrower_no"
            placeholder="Borrower No."
            required>

        <input
            type="date"
            name="borrow_date"
            placeholder="Borrow Date"
            required>

        <input
            type="date"
            name="due_date"
            placeholder="Due Date">

        <input
            type="text"
            name="status"
            placeholder="Status">

        <button type="submit">
            Add Transaction
        </button>

    </form>

    <hr>

    <h2>Records</h2>

    <table>
        <h3>Transactions</h3>

        <thead>

            <tr>
                <th>Borrow ID</th>
                <th>Borrower ID</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

        </thead>

        <tbody>

            <?php foreach ($borrow_transactions as $ts): ?>

                <tr>

                    <td>
                        <?= (int)$ts['borrow_id'] ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $ts['borrower_id'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $ts['borrow_date'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $ts['due_date'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $ts['status'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        ?>
                    </td>

                    <td>

                        <form
                            method="post"
                            style="display:inline">

                            <input
                                type="hidden"
                                name="action"
                                value="delete">

                            <input
                                type="hidden"
                                name="borrow_id"
                                value="<?= (int)$ts['borrow_id'] ?>">

                            <button type="submit">
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

    <hr>


    <h2>Update Transaction</h2>

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="update">

        <input
            type="number"
            name="borrow_id"
            placeholder="Borrow ID"
            required>

        <input
            type="integer"
            name="borrower_id"
            placeholder="Borrower ID"
            required>

        <input
            type="date"
            name="borrow_date"
            placeholder="Borrow Date"
            required>

        <input
            type="date"
            name="due_date"
            placeholder="Due Date">

        <input
            type="text"
            name="status"
            placeholder="Status">

        <button type="submit">
            Update
        </button>

    </form>



</body>

</html>