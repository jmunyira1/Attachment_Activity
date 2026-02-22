<?php
// ===== Simple .env loader (no external packages) =====
function loadEnv(string $path = '.env'): void
{
    if (!file_exists($path)) {
        die("Critical: .env file not found at $path");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Handle quoted values
        if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
        }

        putenv("$name=" . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnv(__DIR__ . '/.env');

// ===== Database connection =====
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: die('DB_USER not set');
$pass = getenv('DB_PASS') ?: die('DB_PASS not set');
$dbname = getenv('DB_NAME') ?: die('DB_NAME not set');

$conn = new mysqli($host, $user, $pass, $dbname);


// ===== Handle new log submission =====
if (isset($_POST['add_log'])) {
    $department = $conn->real_escape_string($_POST['department']);
    $description = $conn->real_escape_string($_POST['description']);
    $skills = $conn->real_escape_string($_POST['skills']);
    $work_date = $conn->real_escape_string($_POST['work_date']);

    $sql = "INSERT INTO attachment_logs (department, description, skills, work_date)
            VALUES ('$department', '$description', '$skills', '$work_date')";

    $msg = $conn->query($sql)
        ? "<div class='alert alert-success'>✅ Log saved successfully!</div>"
        : "<div class='alert alert-danger'>❌ Error: " . $conn->error . "</div>";
}

// ===== Handle edit submission =====
if (isset($_POST['edit_log'])) {
    $id = intval($_POST['id']);
    $department = $conn->real_escape_string($_POST['department']);
    $description = $conn->real_escape_string($_POST['description']);
    $skills = $conn->real_escape_string($_POST['skills']);
    $work_date = $conn->real_escape_string($_POST['work_date']);

    $sql = "UPDATE attachment_logs 
            SET department='$department', description='$description', 
                skills='$skills', work_date='$work_date' 
            WHERE id=$id";

    $msg = $conn->query($sql)
        ? "<div class='alert alert-success'>✅ Log updated successfully!</div>"
        : "<div class='alert alert-danger'>❌ Error: " . $conn->error . "</div>";
}

// ===== Fetch all logs =====
$result = $conn->query("SELECT * FROM attachment_logs ORDER BY work_date DESC, created_at DESC");
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Attachment Logs</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4 text-center text-primary">📑 Attachment Logs System</h2>

        <!-- Submission Form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <?= isset($msg) ? $msg : "" ?>
                <form method="post">
                    <input type="hidden" name="add_log" value="1">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Date of Work</label>
                            <input type="date" name="work_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" value="ICT" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Skill(s) Acquired</label>
                            <input type="text" name="skills" class="form-control" placeholder="Skills" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description of Work Done</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe the work done"
                                  required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Log</button>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">All Attachment Logs</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead class="table-dark text-center">
                    <tr>
                        <th>ID</th>
                        <th>Date of Work</th>
                        <th>Department</th>
                        <th>Description</th>
                        <th>Skills Acquired</th>
                        <th>Logged At</th>
                        <th>Edit</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td class="text-center"><?= $row['id'] ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['work_date']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['skills'])) ?></td>
                                <td class="text-center"><?= $row['created_at'] ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $row['id'] ?>">Edit
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title">Edit Log #<?= $row['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="edit_log" value="1">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <div class="row mb-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Date of Work</label>
                                                        <input type="date" name="work_date" class="form-control"
                                                               value="<?= $row['work_date'] ?>" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Department</label>
                                                        <input type="text" name="department" class="form-control"
                                                               value="<?= htmlspecialchars($row['department']) ?>"
                                                               required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Skill(s) Acquired</label>
                                                        <input type="text" name="skills" class="form-control"
                                                               value="<?= htmlspecialchars($row['skills']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Description of Work Done</label>
                                                    <textarea name="description" class="form-control" rows="3"
                                                              required><?= htmlspecialchars($row['description']) ?></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-warning">Update Log</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php }
                    } else { ?>
                        <tr>
                            <td colspan="7" class="text-center">No logs yet.</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>

<?php $conn->close(); ?>