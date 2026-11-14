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
        'INSERT INTO equipment
            (property_no, equipment_name, quantity_available)
         VALUES
            (:property_no, :equipment_name, :quantity_available)'
    );

    $stmt->execute([
        ':property_no' => trim($_POST['property_no'] ?? ''),
        ':equipment_name' => trim($_POST['equipment_name'] ?? ''),
        ':quantity_available' => trim($_POST['quantity_available'] ?? '')
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
        'UPDATE equipment
         SET property_no = :property_no,
             equipment_name = :equipment_name,
             quantity_available = :quantity_available
         WHERE equipment_id = :equipment_id'
    );

    $stmt->execute([
        ':property_no' => trim($_POST['property_no'] ?? ''),
        ':equipment_name' => trim($_POST['equipment_name'] ?? ''),
        ':quantity_available' => (int)($_POST['quantity_available'] ?? ''),
        ':equipment_id' => (int)($_POST['equipment_id'] ?? 0)
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
        'DELETE FROM equipment
         WHERE equipment_id = :equipment_id'
    );

    $stmt->execute([
        ':equipment_id' => (int)($_POST['equipment_id'] ?? 0)
    ]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


// ----------------------------------------------------
// READ
// ----------------------------------------------------

$stmt = $pdo->query(
    'SELECT equipment_id, property_no, equipment_name, quantity_available
     FROM equipment
     ORDER BY equipment_id DESC'
);

$equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <a href="#">Equipment</a>
        <a href="transactions.php">Transactions</a>
        <a href="records.php">Records</a>
    </div>
    <hr>


    <h2>ADD EQUIPMENT</h2>

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="create">

        <input
            type="text"
            name="property_no"
            placeholder="Property No."
            required>

        <input
            type="text"
            name="equipment_name"
            placeholder="Equipment Name"
            required>

        <input
            type="integer"
            name="quantity_available"
            placeholder="Quantity Available">

        <button type="submit">
            Add Equipment
        </button>

    </form>

    <hr>

    <h2>Records</h2>

    <table>
        <h3>Equipment</h3>

        <thead>

            <tr>
                <th>Equipment ID</th>
                <th>Property No.</th>
                <th>Equipment Name</th>
                <th>Quantity_Available</th>
                <th>Actions</th>
            </tr>

        </thead>

        <tbody>

            <?php foreach ($equipment as $eq): ?>

                <tr>

                    <td>
                        <?= (int)$eq['equipment_id'] ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $eq['property_no'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $eq['equipment_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        ?>
                    </td>

                    <td>
                        <?=
                        htmlspecialchars(
                            $eq['quantity_available'] ?? '',
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
                                name="equipment_id"
                                value="<?= (int)$eq['equipment_id'] ?>">

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


    <h2>Update Equipment</h2>

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="update">

        <input
            type="number"
            name="equipment_id"
            placeholder="Equipment ID"
            required>

        <input
            type="text"
            name="property_no"
            placeholder="Property No.">

        <input
            type="text"
            name="equipment_name"
            placeholder="Equipment Name">

        <input
            type="integer"
            name="quantity_available"
            placeholder="Quantity Available">

        <button type="submit">
            Update
        </button>

    </form>


</body>

</html>