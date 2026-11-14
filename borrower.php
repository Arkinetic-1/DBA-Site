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
        'INSERT INTO borrowers
            (borrower_no, full_name, borrower_type)
         VALUES
            (:borrower_no, :full_name, :borrower_type)'
    );

    $stmt->execute([
        ':borrower_no' => trim($_POST['borrower_no'] ?? ''),
        ':full_name' => trim($_POST['full_name'] ?? ''),
        ':borrower_type' => trim($_POST['borrower_type'] ?? '')
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------------------------------
// READ
// ----------------------------------------------------

$stmt = $pdo->query(
    'SELECT borrower_id, borrower_no, full_name, borrower_type
     FROM borrowers
     ORDER BY borrower_id DESC'
);

$borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------------------
// UPDATE
// ----------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update'
) {

    $stmt = $pdo->prepare(
        'UPDATE borrowers
         SET borrower_no = :borrower_no,
             full_name = :full_name,
             borrower_type = :borrower_type
         WHERE borrower_id = :borrower_id'
    );

    $stmt->execute([
        ':borrower_no' => trim($_POST['borrower_no'] ?? ''),
        ':full_name' => trim($_POST['full_name'] ?? ''),
        ':borrower_type' => trim($_POST['borrower_type'] ?? ''),
        ':borrower_id' => (int)($_POST['borrower_id'] ?? 0)
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
        'DELETE FROM borrowers
         WHERE borrower_id = :borrower_id'
    );

    $stmt->execute([
        ':borrower_id' => (int)($_POST['borrower_id'] ?? 0)
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


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
        <a href="#">Borrowers</a>
        <a href="equipment.php">Equipment</a>
        <a href="transactions.php">Transactions</a>
        <a href="records.php">Records</a>
    </div>
    <hr>


    <h2>ADD BORROWER</h2>

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
            type="text"
            name="full_name"
            placeholder="Full Name"
            required>

        <input
            type="text"
            name="borrower_type"
            placeholder="Borrower Type">

        <button type="submit">
            Add Borrower
        </button>

    </form>
    <hr>


    <h2>Records</h2>

    <table>
        <h3>Borrowers</h3>

        <thead>

            <tr>
                <th>Borrower ID</th>
                <th>Borrower No.</th>
                <th>Full Name</th>
                <th>Borrower_type</th>
                <th>Actions</th>
            </tr>

        </thead>

        <tbody>

            <?php foreach ($borrowers as $borrower): ?>

                <tr>

                    <td>
                        <?= (int)$borrower['borrower_id'] ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $borrower['borrower_no'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $borrower['full_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $borrower['borrower_type'] ?? '',
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
                                name="borrower_id"
                                value="<?= (int)$borrower['borrower_id'] ?>">

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


    <h2>Update Borrower</h2>

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="update">

        <input
            type="number"
            name="borrower_id"
            placeholder="Borrower ID"
            required>

        <input
            type="text"
            name="borrower_no"
            placeholder="Borrower No.">

        <input
            type="text"
            name="full_name"
            placeholder="Full Name">

        <input
            type="text"
            name="borrower_type"
            placeholder="Borrower Type">

        <button type="submit">
            Update
        </button>

    </form>
    <hr>

</body>

</html>