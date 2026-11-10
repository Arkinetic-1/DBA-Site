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
        'INSERT INTO borrow_items
            (borrow_id, equipment_id, quantity)
         VALUES
            (:borrow_id, :equipment_id, :quantity)'
    );

    $stmt->execute([
        ':borrow_id' => trim($_POST['borrow_id'] ?? ''),
        ':equipment_id' => trim($_POST['equipment_id'] ?? ''),
        ':quantity' => trim($_POST['quantity'] ?? '')
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
        'UPDATE borrow_items
         SET borrow_id = :borrow_id,
             equipment_id = :equipment_id,
             quantity = :quantity
         WHERE borrow_item_id = :borrow_item_id'
    );

    $stmt->execute([
        ':borrow_id' => (int)($_POST['borrow_id'] ?? 4),
        ':equipment_id' => (int)($_POST['equipment_id'] ?? 1),
        ':quantity' => (int)($_POST['quantity'] ?? 1),
        ':borrow_item_id' => (int)($_POST['borrow_item_id'] ?? 1)
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
        'DELETE FROM borrow_items
         WHERE borrow_item_id = :borrow_item_id'
    );

    $stmt->execute([
        ':borrow_item_id' => (int)($_POST['borrow_item_id'] ?? 0)
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


// ----------------------------------------------------
// READ
// ----------------------------------------------------

$stmt = $pdo->query(
    'SELECT borrow_item_id, borrow_id, equipment_id, quantity
     FROM borrow_items
     ORDER BY borrow_item_id DESC'
);

$borrow_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

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
            <li><a href="borrower.php">Borrowers</a></li>
            <li><a href="equipment.php">Equipment</a></li>
            <li><a href="transactions.php">Transactions</a></li>
        </ul>
    </nav> <hr>


    <h2>ADD BORROW RECORD </h2>

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="create"
        >

        <input
            type="integer"
            name="borrow_id"
            placeholder="Borrow ID"
            required
        >

        <input
            type="integer"
            name="equipment_id"
            placeholder="Equipment ID"
            required
        >

        <input
            type="number"
            name="quantity"
            placeholder="Quantity"
            required
        >

        <button type="submit">
            Add Borrow Record
        </button>

    </form>

    <hr>

    <h2>Records</h2>

    <table>
        <h3>Borrow Record</h3>

        <thead>

            <tr>
                <th>Borrow Item ID</th>
                <th>Borrow ID</th>
                <th>Equipment ID</th>
                <th>Quantity</th>
                <th>Actions</th>
            </tr>

        </thead>

        <tbody>

        <?php foreach ($borrow_items as $bi): ?>

            <tr>

                <td>
                    <?= (int)$bi['borrow_item_id'] ?>
                </td>

                <td>
                    <?=
                        htmlspecialchars(
                            $bi['borrow_id'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ?>
                </td>

                <td>
                    <?=
                        htmlspecialchars(
                            $bi['equipment_id'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ?>
                </td>

                <td>
                    <?=
                        htmlspecialchars(
                            $bi['quantity'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ?>
                </td>

                <td>

                    <form
                        method="post"
                        style="display:inline"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="delete"
                        >

                        <input
                            type="hidden"
                            name="borrow_item_id"
                            value="<?= (int)$bi['borrow_item_id'] ?>"
                        >

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


    <h2>Update Borrow Record</h2>

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="update"
        >

        <input
            type="number"
            name="borrow_item_id"
            placeholder="Borrow Item ID"
            required
        >

        <input
            type="integer"
            name="borrower_id"
            placeholder="Borrower ID"
            required
        >

        <input
            type="integer"
            name="equipment_id"
            placeholder="Equipment ID"
            required
        >

        <input
            type="integer"
            name="quantity"
            placeholder="Quantity"
        >

        <button type="submit">
            Update
        </button>

    </form>


</body>

</html>