<?php
$host = '127.0.0.1';
$port = '5432';
$dbname = 'db_borrow';
$user = 'postgres';
$password = '1111';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// ----------------------------------------------------
// BORROWER: FILTER, SORT, AND GROUP BORROWERS
// ----------------------------------------------------

$borrowerType = trim((string) ($_GET['borrower_type'] ?? ''));
$groupByType = (($_GET['group_by'] ?? '') === 'borrower_type');
$direction = strtoupper((string) ($_GET['direction'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
$sortOptions = $groupByType
    ? ['borrower_type', 'borrower_count']
    : ['borrower_id', 'borrower_no', 'full_name', 'borrower_type'];


$sort = (string) ($_GET['sort'] ?? ($groupByType ? 'borrower_type' : 'borrower_id'));

    if (!in_array($sort, $sortOptions, true)) {
        $sort = $groupByType ? 'borrower_type' : 'borrower_id';
    }


$typeStmt = $pdo->query(
    'SELECT DISTINCT borrower_type AS borrower_category
     FROM borrowers
     WHERE borrower_type IS NOT NULL
     ORDER BY borrower_category'
);


$borrowerTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);
    if ($borrowerType !== '' && !in_array($borrowerType, $borrowerTypes, true)) {
        $borrowerType = '';
    }

$where = [];
$params = [];
    if ($borrowerType !== '') {
        $where[] = 'borrower_type = :borrower_type';
        $params['borrower_type'] = $borrowerType;
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';


    if ($groupByType) {
        $sql = 'SELECT borrower_type AS borrower_category, COUNT(*) AS borrower_count
                FROM borrowers' . $whereSql . '
                GROUP BY borrower_type
                ORDER BY ' . $sort . ' ' . $direction . ', borrower_category ASC';
    } 
    
    else {
        $secondarySort = $sort === 'borrower_id' ? 'borrower_no' : 'borrower_id';
        $sql = 'SELECT borrower_id AS id,
                    borrower_no AS borrower_number,
                    full_name AS borrower_name,
                    borrower_type AS borrower_category
                FROM borrowers' . $whereSql . '
                ORDER BY ' . $sort . ' ' . $direction . ', ' . $secondarySort . ' ASC';
    }

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$borrowers = $stmt->fetchAll();

// ----------------------------------------------------
// EQUIPMENT: FILTER, SORT, AND GROUP EQUIPMENT
// ----------------------------------------------------
$equipmentName = trim((string) ($_GET['equipment_name'] ?? ''));
$equipmentGroupBy = (string) ($_GET['equipment_group_by'] ?? '');
if (!in_array($equipmentGroupBy, ['equipment_name', 'quantity_available'], true)) {
    $equipmentGroupBy = '';
}
$equipmentDirection = strtoupper((string) ($_GET['equipment_direction'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
$equipmentSortOptions = $equipmentGroupBy !== ''
    ? ['grouped_value', 'equipment_count', 'total_available']
    : ['equipment_id', 'property_no', 'equipment_name', 'quantity_available'];
$equipmentSort = (string) ($_GET['equipment_sort'] ?? ($equipmentGroupBy !== '' ? 'grouped_value' : 'equipment_id'));
if (!in_array($equipmentSort, $equipmentSortOptions, true)) {
    $equipmentSort = $equipmentGroupBy !== '' ? 'grouped_value' : 'equipment_id';
}

$equipmentNameStmt = $pdo->query(
    'SELECT DISTINCT equipment_name
     FROM equipment
     WHERE equipment_name IS NOT NULL
     ORDER BY equipment_name'
);
$equipmentNames = $equipmentNameStmt->fetchAll(PDO::FETCH_COLUMN);
if ($equipmentName !== '' && !in_array($equipmentName, $equipmentNames, true)) {
    $equipmentName = '';
}

$equipmentWhere = [];
$equipmentParams = [];
if ($equipmentName !== '') {
    $equipmentWhere[] = 'equipment_name = :equipment_name';
    $equipmentParams['equipment_name'] = $equipmentName;
}
$equipmentWhereSql = $equipmentWhere ? ' WHERE ' . implode(' AND ', $equipmentWhere) : '';

if ($equipmentGroupBy !== '') {
    $equipmentSql = 'SELECT ' . $equipmentGroupBy . ' AS grouped_value,
                            COUNT(*) AS equipment_count,
                            SUM(quantity_available) AS total_available
                     FROM equipment' . $equipmentWhereSql . '
                     GROUP BY ' . $equipmentGroupBy . '
                     ORDER BY ' . $equipmentSort . ' ' . $equipmentDirection;
} else {
    $equipmentSecondarySort = $equipmentSort === 'equipment_id' ? 'property_no' : 'equipment_id';
    $equipmentSql = 'SELECT equipment_id, property_no, equipment_name, quantity_available
                     FROM equipment' . $equipmentWhereSql . '
                     ORDER BY ' . $equipmentSort . ' ' . $equipmentDirection . ', '
                     . $equipmentSecondarySort . ' ASC';
}
$equipmentStmt = $pdo->prepare($equipmentSql);
$equipmentStmt->execute($equipmentParams);
$equipment = $equipmentStmt->fetchAll();

// ----------------------------------------------------
// TRANSACTION: FILTER, SORT, AND GROUP TRANSACTIONS
// ----------------------------------------------------
$transactionStatus = trim((string) ($_GET['transaction_status'] ?? ''));
$transactionDateFrom = trim((string) ($_GET['transaction_date_from'] ?? ''));
$transactionDateTo = trim((string) ($_GET['transaction_date_to'] ?? ''));
$transactionGroupBy = (string) ($_GET['transaction_group_by'] ?? '');
if (!in_array($transactionGroupBy, ['status', 'borrower_id'], true)) {
    $transactionGroupBy = '';
}
$transactionDirection = strtoupper((string) ($_GET['transaction_direction'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
$transactionSortOptions = $transactionGroupBy !== ''
    ? ['grouped_value', 'transaction_count']
    : ['borrow_id', 'borrower_id', 'borrow_date', 'due_date', 'status'];
$transactionSort = (string) ($_GET['transaction_sort'] ?? ($transactionGroupBy !== '' ? 'grouped_value' : 'borrow_id'));
if (!in_array($transactionSort, $transactionSortOptions, true)) {
    $transactionSort = $transactionGroupBy !== '' ? 'grouped_value' : 'borrow_id';
}

$transactionStatusStmt = $pdo->query(
    'SELECT DISTINCT status
     FROM borrow_transactions
     WHERE status IS NOT NULL
     ORDER BY status'
);
$transactionStatuses = $transactionStatusStmt->fetchAll(PDO::FETCH_COLUMN);
if ($transactionStatus !== '' && !in_array($transactionStatus, $transactionStatuses, true)) {
    $transactionStatus = '';
}

$transactionWhere = [];
$transactionParams = [];
if ($transactionStatus !== '') {
    $transactionWhere[] = 'status = :transaction_status';
    $transactionParams['transaction_status'] = $transactionStatus;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $transactionDateFrom)) {
    $transactionWhere[] = 'borrow_date >= :transaction_date_from';
    $transactionParams['transaction_date_from'] = $transactionDateFrom;
} else {
    $transactionDateFrom = '';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $transactionDateTo)) {
    $transactionWhere[] = 'borrow_date <= :transaction_date_to';
    $transactionParams['transaction_date_to'] = $transactionDateTo;
} else {
    $transactionDateTo = '';
}
$transactionWhereSql = $transactionWhere ? ' WHERE ' . implode(' AND ', $transactionWhere) : '';

if ($transactionGroupBy !== '') {
    $transactionSql = 'SELECT ' . $transactionGroupBy . ' AS grouped_value,
                              COUNT(*) AS transaction_count
                       FROM borrow_transactions' . $transactionWhereSql . '
                       GROUP BY ' . $transactionGroupBy . '
  
                       ORDER BY ' . $transactionSort . ' ' . $transactionDirection;
} else {
    $transactionSecondarySort = $transactionSort === 'borrow_id' ? 'borrower_id' : 'borrow_id';
    $transactionSql = 'SELECT borrow_id, borrower_id, borrow_date, due_date, status
                        FROM borrow_transactions' . $transactionWhereSql . '
                        ORDER BY ' . $transactionSort . ' ' . $transactionDirection . ', '
                        . $transactionSecondarySort . ' ASC';
}
$transactionStmt = $pdo->prepare($transactionSql);
$transactionStmt->execute($transactionParams);
$borrow_transactions = $transactionStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Week 6 : Data Retrieval, Filtering, Sorting, and Grouping with SQL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" 
    rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" 
    crossorigin="anonymous">

    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 30px auto; padding: 0 15px; }
        form { margin-bottom: 20px; }
        input, select, button { padding: 8px; margin: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .error { color: red; background: #ffe6e6; padding: 10px; margin-bottom: 15px; border: 1px solid red; }
        h2 { margin-top: 40px; }
    </style>

</head>

<body>
    <!--Start of Navigation Bar-->
    <!--A Navbar that connects the other pages-->
    <section id="NavBar">
        <nav class="navbar navbar-expand-lg justify-content-end gap-5 mb-5 py-4">
            <a class= "navbar-brand" href="crud_demo.php">Week 5</a>
            <a class= "navbar-brand" href="WEEK6_.php">Week 6</a>
            <a class= "navbar-brand" href="WEEK7_.php">Week 7</a>
            <a class= "navbar-brand" href="WEEK8_.php">Week 8</a>
            <a class= "navbar-brand" href="WEEK10_.php">Week 10</a>
        </nav>
    </section>

    <!--Borrower Data Section-->
    <section id="Borrower Data">
        <div class="card px-5 py-5">
            <h1 class="text-center fw-bold my-5">Borrower Data</h1>
            <form method="get" class="row g-3 align-items-end mb-4">
                <div class="col-md-3">
                    <label for="borrower_type" class="form-label" style="font-weight: bolder;">BORROWER TYPE</label>
                    <select id="borrower_type" name="borrower_type" class="form-select">
                        <option value="">All types</option>
                        <?php foreach ($borrowerTypes as $type): ?>
                            <option
                                value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $borrowerType === $type ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort" class="form-label" style="font-weight: bolder;">SORT BY</label>
                    <select id="sort" name="sort" class="form-select">
                        <?php foreach ($sortOptions as $option): ?>
                            <option
                                value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $sort === $option ? 'selected' : '' ?>
                            >
                                <?= $option === 'borrower_count' ? 'Group size' : ucwords(str_replace('_', ' ', $option)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="direction" class="form-label" style="font-weight: bolder;">DIRECTION</label>
                    <select id="direction" name="direction" class="form-select">
                        <option value="ASC" <?= $direction === 'ASC' ? 'selected' : '' ?>>
                            Ascending
                        </option>
                        <option value="DESC" <?= $direction === 'DESC' ? 'selected' : '' ?>>
                            Descending
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="group_by" class="form-label" style="font-weight: bolder;">GROUP BY</label>
                    <select id="group_by" name="group_by" class="form-select">
                        <option value="">Show borrowers</option>
                        <option value="borrower_type" <?= $groupByType ? 'selected' : '' ?>>
                            By borrower type
                        </option>
                    </select>
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>

            <!--Table of Data-->
            <!--Chore: A table that displays the data from the database-->
            <section id="DataTable" class="container py-4">
                <div class="container">
                <div class="row">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <!--Title of the table-->
                            <thead>
                                <?php if ($groupByType): ?>
                                    <tr><th>Borrower Type</th><th>Borrower Count</th></tr>
                                <?php else: ?>
                                    <tr><th>Borrower ID</th><th>Borrower Number</th><th>Full Name</th><th>Borrower Type</th></tr>
                                <?php endif; ?>
                            </thead>

                            <!--Body of the Table-->
                            <tbody>
                                <?php if (empty($borrowers)): ?>
                                    <tr><td colspan="4">No matching results found.</td></tr>
                                <?php elseif ($groupByType): ?>
                                    <?php foreach ($borrowers as $b): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($b['borrower_category'] ?? 'Unspecified', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= (int) $b['borrower_count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($borrowers as $b): ?>
                                        <tr>
                                            <td><?= (int) $b['id'] ?></td>
                                            <td><?= htmlspecialchars($b['borrower_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($b['borrower_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($b['borrower_category'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </section>
        </div>
    </section>

    <!--Equipment Data Section-->
    <section id="Equipment Data" class="card px-5 py-5 mt-5">
        <h2 class="text-center fw-bold my-4">Equipment Data</h2>
        <form method="get" class="row g-3 align-items-end mb-4">
            <div class="col-md-3">
                <label for="equipment_name" class="form-label fw-bold">EQUIPMENT NAME</label>
                <select id="equipment_name" name="equipment_name" class="form-select">
                    <option value="">All equipment</option>
                    <?php foreach ($equipmentNames as $name): ?>
                        <option value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $equipmentName === $name ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="equipment_sort" class="form-label fw-bold">SORT BY</label>
                <select id="equipment_sort" name="equipment_sort" class="form-select">
                    <?php foreach ($equipmentSortOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $equipmentSort === $option ? 'selected' : '' ?>>
                            <?= ucwords(str_replace('_', ' ', $option)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="equipment_direction" class="form-label fw-bold">DIRECTION</label>
                <select id="equipment_direction" name="equipment_direction" class="form-select">
                    <option value="ASC" <?= $equipmentDirection === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                    <option value="DESC" <?= $equipmentDirection === 'DESC' ? 'selected' : '' ?>>Descending</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="equipment_group_by" class="form-label fw-bold">GROUP BY</label>
                <select id="equipment_group_by" name="equipment_group_by" class="form-select">
                    <option value="">Show equipment</option>
                    <option value="equipment_name" <?= $equipmentGroupBy === 'equipment_name' ? 'selected' : '' ?>>Equipment name</option>
                    <option value="quantity_available" <?= $equipmentGroupBy === 'quantity_available' ? 'selected' : '' ?>>Available quantity</option>
                </select>
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?'), ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <?php if ($equipmentGroupBy !== ''): ?>
                        <tr><th><?= $equipmentGroupBy === 'equipment_name' ? 'Equipment Name' : 'Available Quantity' ?></th>
                            <th>Equipment Count</th><th>Total Available</th></tr>
                    <?php else: ?>
                        <tr><th>Equipment ID</th><th>Property Number</th><th>Equipment Name</th><th>Available Quantity</th></tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if (empty($equipment)): ?>
                        <tr><td colspan="4">No matching equipment found.</td></tr>
                    <?php elseif ($equipmentGroupBy !== ''): ?>
                        <?php foreach ($equipment as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($item['grouped_value'] ?? 'Unspecified'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $item['equipment_count'] ?></td>
                                <td><?= (int) $item['total_available'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($equipment as $item): ?>
                            <tr>
                                <td><?= (int) $item['equipment_id'] ?></td>
                                <td><?= htmlspecialchars($item['property_no'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['equipment_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $item['quantity_available'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section id="Transaction Data" class="card px-5 py-5 mt-5">
        <h2 class="text-center fw-bold my-4">Transaction Data</h2>
        <form method="get" class="row g-3 align-items-end mb-4">
            <div class="col-md-3">
                <label for="transaction_status" class="form-label fw-bold">STATUS</label>
                <select id="transaction_status" name="transaction_status" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach ($transactionStatuses as $status): ?>
                        <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $transactionStatus === $status ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="transaction_date_from" class="form-label fw-bold">BORROWED FROM</label>
                <input id="transaction_date_from" name="transaction_date_from" type="date" class="form-control"
                    value="<?= htmlspecialchars($transactionDateFrom, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-2">
                <label for="transaction_date_to" class="form-label fw-bold">BORROWED TO</label>
                <input id="transaction_date_to" name="transaction_date_to" type="date" class="form-control"
                    value="<?= htmlspecialchars($transactionDateTo, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3">
                <label for="transaction_sort" class="form-label fw-bold">SORT BY</label>
                <select id="transaction_sort" name="transaction_sort" class="form-select">
                    <?php foreach ($transactionSortOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $transactionSort === $option ? 'selected' : '' ?>>
                            <?= ucwords(str_replace('_', ' ', $option)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="transaction_direction" class="form-label fw-bold">DIRECTION</label>
                <select id="transaction_direction" name="transaction_direction" class="form-select">
                    <option value="ASC" <?= $transactionDirection === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                    <option value="DESC" <?= $transactionDirection === 'DESC' ? 'selected' : '' ?>>Descending</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="transaction_group_by" class="form-label fw-bold">GROUP BY</label>
                <select id="transaction_group_by" name="transaction_group_by" class="form-select">
                    <option value="">Show transactions</option>
                    <option value="status" <?= $transactionGroupBy === 'status' ? 'selected' : '' ?>>Status</option>
                    <option value="borrower_id" <?= $transactionGroupBy === 'borrower_id' ? 'selected' : '' ?>>Borrower</option>
                </select>
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?'), ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <?php if ($transactionGroupBy !== ''): ?>
                        <tr><th><?= $transactionGroupBy === 'status' ? 'Status' : 'Borrower ID' ?></th>
                            <th>Transaction Count</th></tr>
                    <?php else: ?>
                        <tr><th>Transaction ID</th><th>Borrower ID</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if (empty($borrow_transactions)): ?>
                        <tr><td colspan="5">No matching transactions found.</td></tr>
                    <?php elseif ($transactionGroupBy !== ''): ?>
                        <?php foreach ($borrow_transactions as $transaction): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($transaction['grouped_value'] ?? 'Unspecified'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $transaction['transaction_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($borrow_transactions as $transaction): ?>
                            <tr>
                                <td><?= (int) $transaction['borrow_id'] ?></td>
                                <td><?= (int) $transaction['borrower_id'] ?></td>
                                <td><?= htmlspecialchars($transaction['borrow_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($transaction['due_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($transaction['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    
</body>
</html>