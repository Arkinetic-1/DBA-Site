
<?php

$host = 'localhost';
$port = '5432';
$dbname = 'projectdb';
$user = 'postgres';
$password = 'REGULATORdriver999';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

$errorMessage = '';

// ----------------------------------------------------
// CREATE BORROWER
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO borrowers (borrower_no, full_name, borrower_type)
             VALUES (:borrower_no, :full_name, :borrower_type)'
        );

        $stmt->execute([
            ':borrower_no' => trim($_POST['borrower_no'] ?? ''),
            ':full_name' => trim($_POST['full_name'] ?? ''),
            ':borrower_type' => $_POST['borrower_type'] ?? 'Student'
        ]);

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $errorMessage = 'Create Error: ' . $e->getMessage();
    }
}

// ----------------------------------------------------
// UPDATE BORROWER
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    try {
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
            ':borrower_type' => $_POST['borrower_type'] ?? 'Student',
            ':borrower_id' => (int) ($_POST['borrower_id'] ?? 0)
        ]);

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $errorMessage = 'Update Error: ' . $e->getMessage();
    }
}

// ----------------------------------------------------
// DELETE BORROWER
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        $stmt = $pdo->prepare('DELETE FROM borrowers WHERE borrower_id = :borrower_id');
        $stmt->execute([':borrower_id' => (int) ($_POST['borrower_id'] ?? 0)]);

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        // Triggers if the borrower has records in borrow_transactions (RESTRICT constraint)
        $errorMessage = 'Cannot delete: this borrower has associated transaction history.';
    }
}

// ----------------------------------------------------
// READ BORROWERS
// ----------------------------------------------------
$stmt = $pdo->query(
    'SELECT borrower_id, borrower_no, full_name, borrower_type
     FROM borrowers
     ORDER BY borrower_id ASC'
);
$borrowers = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Borrowers Management</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 30px auto; padding: 0 15px; }
        form { margin-bottom: 20px; }
        input, select, button { padding: 8px; margin: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .error { color: red; background: #ffe6e6; padding: 10px; margin-bottom: 15px; border: 1px solid red; }
    </style>
</head>
<body>

    <h1>Borrowers Management</h1>

    <?php if (!empty($errorMessage)): ?>
            <div class="error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <h2>Add Borrower</h2>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <input type="text" name="borrower_no" placeholder="Borrower / ID No." required>
        <input type="text" name="full_name" placeholder="Full Name" required>
        
        <select name="borrower_type" required>
            <option value="Student">Student</option>
            <option value="Faculty">Faculty</option>
            <option value="Staff">Staff</option>
            <option value="Researcher">Researcher</option>
        </select>

        <button type="submit">Add Borrower</button>
    </form>

    <h2>Borrower Records</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Borrower No.</th>
                <th>Full Name</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($borrowers)): ?>
                <tr><td colspan="5">No borrower records found.</td></tr>
        <?php else: ?>
                <?php foreach ($borrowers as $b): ?>
                        <tr>
                            <td><?= (int) $b['borrower_id'] ?></td>
                            <td><?= htmlspecialchars($b['borrower_no'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($b['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($b['borrower_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this borrower?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="borrower_id" value="<?= (int) $b['borrower_id'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <h2>Update Borrower</h2>
    <form method="post">
        <input type="hidden" name="action" value="update">
        <input type="number" name="borrower_id" placeholder="Borrower ID" required>
        <input type="text" name="borrower_no" placeholder="Borrower / ID No." required>
        <input type="text" name="full_name" placeholder="Full Name" required>

        <select name="borrower_type" required>
            <option value="Student">Student</option>
            <option value="Faculty">Faculty</option>
            <option value="Staff">Staff</option>
            <option value="Researcher">Researcher</option>
        </select>

        <button type="submit">Update Borrower</button>
    </form>

</body>
</html>