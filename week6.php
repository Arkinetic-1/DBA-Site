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
// Borrower Filter Select
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
} else {
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
// Borrower Filter Select
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

?>


<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BORROWER'S CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
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
            <li><a href="borrower.php">Week 5</a></li>
            <li><a href="week7.php">Week 7</a></li>
            <li><a href="week8.php">Week 8</a></li>
            <li><a href="week10.php">Week 10</a></li>
        </ul>
    </nav>
    <hr>

    <div class="container-fluid">

        <div class="col-12 border border-2 rounded mx-auto p-3">

            <div class="row p-2">
                <h1>Borrowers</h1>
                <div class="col-12">
                    <form method="get" class="row g-3 align-items-end mb-4">
                        <div class="col-md-3">
                            <label for="borrower_type" class="form-label" style="font-weight: bolder;">
                                BORROWER TYPE
                            </label>
                            <select id="borrower_type" name="borrower_type" class="form-select">
                                <option value="">All types</option>
                                <?php foreach ($borrowerTypes as $type): ?>
                                    <option
                                        value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $borrowerType === $type ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="sort" class="form-label" style="font-weight: bolder;">
                                SORT BY
                            </label>
                            <select id="sort" name="sort" class="form-select">
                                <?php foreach ($sortOptions as $option): ?>
                                    <option
                                        value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $sort === $option ? 'selected' : '' ?>>
                                        <?= $option === 'borrower_count' ? 'Group size' : ucwords(str_replace('_', ' ', $option)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="direction" class="form-label" style="font-weight: bolder;">
                                ORDER BY
                            </label>
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
                            <label for="group_by" class="form-label" style="font-weight: bolder;">
                                GROUP BY
                            </label>
                            <select id="group_by" name="group_by" class="form-select">
                                <option value="">Show borrowers</option>
                                <option value="borrower_type" <?= $groupByType ? 'selected' : '' ?>>
                                    By borrower type
                                </option>
                            </select>
                        </div>

                        <div class="col-6 d-flex gap-2">
                            <button type="submit" class="btn btn-primary rounded rounded-2">Apply</button>
                            <button type="submit" class="btn btn-primary rounded rounded-2">
                                <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                                    Reset
                                </a>
                            </button>
                        </div>

                    </form>
                </div>

                <div class="container py-4" id="DataTable">
                    <div class="row">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <?php if ($groupByType): ?>
                                        <tr>
                                            <th>Borrower Type</th>
                                            <th>Borrower Count</th>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <th>Borrower ID</th>
                                            <th>Borrower Number</th>
                                            <th>Full Name</th>
                                            <th>Borrower Type</th>
                                        </tr>
                                    <?php endif; ?>
                                </thead>

                                <tbody>
                                    <?php if (empty($borrowers)): ?>
                                        <tr>
                                            <td colspan="4">No matching results found.</td>
                                        </tr>
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

            </div>
        </div><br>

        <div class="col-12 border border-2 rounded mx-auto p-3">
            <div class="row p-2">
                <h1>Equipment</h1>
                <div class="col-12">
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
                </div>
                <div class="container py-4">
                    <div class="row">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <?php if ($equipmentGroupBy !== ''): ?>
                                        <tr>
                                            <th><?= $equipmentGroupBy === 'equipment_name' ? 'Equipment Name' : 'Available Quantity' ?></th>
                                            <th>Equipment Count</th>
                                            <th>Total Available</th>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <th>Equipment ID</th>
                                            <th>Property Number</th>
                                            <th>Equipment Name</th>
                                            <th>Available Quantity</th>
                                        </tr>
                                    <?php endif; ?>
                                </thead>
                                <tbody>
                                    <?php if (empty($equipment)): ?>
                                        <tr>
                                            <td colspan="4">No matching equipment found.</td>
                                        </tr>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>