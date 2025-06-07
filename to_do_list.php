<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tdl";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add new task
if (isset($_POST['add_task'])) {
    $task = $_POST['task'];
    $username = $_SESSION['username'];
    $category = $_POST['category'] ?? 'General';
    $priority = $_POST['priority'] ?? 'Medium';
    $due_date = $_POST['due_date'] ?? null;
    
    $sql = "INSERT INTO tasks (username, task, category, priority, due_date, completed) 
            VALUES (?, ?, ?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $task, $category, $priority, $due_date);
    $stmt->execute();
}

// Update task
if (isset($_POST['update_task'])) {
    $id = $_POST['task_id'];
    $task = $_POST['task'];
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $username = $_SESSION['username'];
    
    $sql = "UPDATE tasks SET task=?, category=?, priority=?, due_date=? 
            WHERE id=? AND username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssis", $task, $category, $priority, $due_date, $id, $username);
    $stmt->execute();
}

// Delete task
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $username = $_SESSION['username'];
    
    $sql = "DELETE FROM tasks WHERE id=? AND username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id, $username);
    $stmt->execute();
}

// Toggle task completion
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $username = $_SESSION['username'];
    
    $sql = "SELECT completed FROM tasks WHERE id=? AND username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $new_status = $row['completed'] ? 0 : 1;
    
    $sql = "UPDATE tasks SET completed=? WHERE id=? AND username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $new_status, $id, $username);
    $stmt->execute();
}

// Filter tasks
$filter = $_GET['filter'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$where_clause = "WHERE username=?";
$params = [$_SESSION['username']];
$types = "s";

if ($filter !== 'all') {
    $where_clause .= " AND completed=?";
    $params[] = $filter === 'completed' ? 1 : 0;
    $types .= "i";
}
if ($category_filter !== 'all') {
    $where_clause .= " AND category=?";
    $params[] = $category_filter;
    $types .= "s";
}

// Get all tasks for the current user
$sql = "SELECT * FROM tasks $where_clause ORDER BY due_date ASC, priority DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get task statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(completed) as completed,
    COUNT(CASE WHEN due_date <= CURDATE() AND completed = 0 THEN 1 END) as overdue
    FROM tasks WHERE username=?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Calculate completion percentage
$completion_percentage = $stats['total'] > 0 ? ($stats['completed'] / $stats['total']) * 100 : 0;

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskly</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="image/wn_2twoogo.jpg">
    <style>
        :root {
            --primary-color: #4287f5;
            --secondary-color: #a5b4fc;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --bg-color: #f0f9ff;
            --card-bg: rgba(255, 255, 255, 0.9);
            --accent-color: #ec4899;
        }

        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-color), #e0f2fe);
            position: relative;
            overflow-x: hidden;
        }

        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: radial-gradient(circle at 20% 30%, rgba(79, 70, 229, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(236, 72, 153, 0.1) 0%, transparent 50%);
            animation: pulseBackground 10s ease-in-out infinite alternate;
        }

        @keyframes pulseBackground {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.1); opacity: 1; }
        }

        .sparkle {
            position: absolute;
            width: 12px;
            height: 12px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.8), transparent);
            border-radius: 50%;
            animation: sparkle 2s ease-in-out infinite;
            pointer-events: none;
            z-index: 2;
        }

        @keyframes sparkle {
            0% { transform: scale(0.5); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.5); opacity: 0; }
        }

        .container-fluid {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .dashboard-container {
            width: 100%;
            max-width: 1000px;
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 2;
            transition: transform 0.3s ease;
        }

        .dashboard-container:hover {
            transform: translateY(-5px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .header h2 {
            font-weight: 700;
            color: var(--primary-color);
            position: relative;
            display: inline-block;
        }

        .header h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50%;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .stats-panel {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(79, 70, 229, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card h4 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.8rem;
        }

        .progress-bar-container {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .progress-bar {
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            transition: width 0.5s ease;
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.2) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.2) 75%, transparent 75%, transparent);
            background-size: 20px 20px;
            animation: moveStripes 1s linear infinite;
        }

        @keyframes moveStripes {
            0% { background-position: 0 0; }
            100% { background-position: 20px 0; }
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .form-select, .form-control, .btn {
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.2);
        }

        .task-form {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        @keyframes rotateGradient {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .task-item {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .task-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .task-item.completed {
            background: #ecfdf5;
            opacity: 0.9;
        }

        .task-details {
            flex-grow: 1;
        }

        .task-text {
            font-weight: 500;
            font-size: 1.1rem;
        }

        .task-text.completed {
            text-decoration: line-through;
            color: #6b7280;
        }

        .task-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .priority-high { color: var(--danger-color); font-weight: 600; }
        .priority-medium { color: var(--warning-color); font-weight: 600; }
        .priority-low { color: var(--success-color); font-weight: 600; }
        .category-work { background: #e0e7ff; }
        .category-personal { background: #d1fae5; }
        .category-urgent { background: #fee2e2; }
        .overdue { color: var(--danger-color); font-weight: 600; }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.3s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }

        .btn-icon {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .btn-icon:hover {
            color: var(--accent-color);
            transform: scale(1.2);
        }

        .edit-form {
            display: none;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-top: 0.5rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .no-tasks {
            text-align: center;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .stats-panel {
                grid-template-columns: 1fr;
            }
            .dashboard-container {
                padding: 1.5rem;
                width: 95%;
            }
            .task-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .task-actions {
                align-self: flex-end;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 1rem;
                width: 98%;
            }
            .task-form {
                padding: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    <div class="sparkle" style="top: 20%; left: 15%; animation-delay: 0s;"></div>
    <div class="sparkle" style="top: 50%; left: 80%; animation-delay: 1s;"></div>
    <div class="sparkle" style="top: 75%; left: 40%; animation-delay: 2s;"></div>
    <div class="container-fluid">
        <div class="dashboard-container">
            <div class="header">
                <h2>Taskly</h2>
                <div>
                    <a href="?logout=1" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <div class="stats-panel">
                <div class="stat-card">
                    <h4><?php echo $stats['total']; ?></h4>
                    <p>Total Quests</p>
                </div>
                <div class="stat-card">
                    <h4><?php echo $stats['completed']; ?></h4>
                    <p>Conquered</p>
                </div>
                <div class="stat-card">
                    <h4><?php echo $stats['overdue']; ?></h4>
                    <p>Urgent</p>
                </div>
            </div>

            <div class="progress-bar-container">
                <p>Quest Completion: <?php echo round($completion_percentage, 1); ?>%</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $completion_percentage; ?>%;"></div>
                </div>
            </div>

            <div class="filter-controls">
                <select class="form-select" onchange="window.location.href='?filter='+this.value">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Quests</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Active Quests</option>
                    <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Conquered Quests</option>
                </select>
                <select class="form-select" onchange="window.location.href='?category='+this.value">
                    <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Realms</option>
                    <option value="Work" <?php echo $category_filter === 'Work' ? 'selected' : ''; ?>>Work</option>
                    <option value="Personal" <?php echo $category_filter === 'Personal' ? 'selected' : ''; ?>>Personal</option>
                    <option value="Urgent" <?php echo $category_filter === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
            </div>

            <div class="task-form">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input class="form-control" type="text" name="task" placeholder="New Quest" required>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="category">
                                <option value="Work">Work</option>
                                <option value="Personal">Personal</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="priority">
                                <option value="High">High</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input class="form-control" type="date" name="due_date">
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-primary w-100" type="submit" name="add_task">Embark on Quest</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="task-list">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="task-item category-<?php echo strtolower($row['category']); ?> <?php echo $row['completed'] ? 'completed' : ''; ?>">
                        <button class="btn-icon" onclick="window.location.href='?toggle=<?php echo $row['id']; ?>'">
                            <i class="fas fa-<?php echo $row['completed'] ? 'check-circle' : 'circle'; ?>"></i>
                        </button>
                        <div class="task-details">
                            <div class="task-text <?php echo $row['completed'] ? 'completed' : ''; ?>">
                                <?php echo htmlspecialchars($row['task']); ?>
                            </div>
                            <div class="task-meta">
                                <span>Realm: <?php echo $row['category']; ?></span>
                                <span class="priority-<?php echo strtolower($row['priority']); ?>">
                                    Priority: <?php echo $row['priority']; ?>
                                </span>
                                <?php if ($row['due_date']): ?>
                                    <span class="<?php echo strtotime($row['due_date']) < time() && !$row['completed'] ? 'overdue' : ''; ?>">
                                        Deadline: <?php echo date('M d, Y', strtotime($row['due_date'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="edit-form" id="edit-form-<?php echo $row['id']; ?>">
                                <form method="POST" action="">
                                    <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <input class="form-control" type="text" name="task" 
                                                   value="<?php echo htmlspecialchars($row['task']); ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="category">
                                                <option value="Work" <?php echo $row['category'] === 'Work' ? 'selected' : ''; ?>>Work</option>
                                                <option value="Personal" <?php echo $row['category'] === 'Personal' ? 'selected' : ''; ?>>Personal</option>
                                                <option value="Urgent" <?php echo $row['category'] === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="priority">
                                                <option value="High" <?php echo $row['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                                                <option value="Medium" <?php echo $row['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="Low" <?php echo $row['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <input class="form-control" type="date" name="due_date" 
                                                   value="<?php echo $row['due_date']; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <button class="btn btn-primary btn-sm" type="submit" name="update_task">Update Quest</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="task-actions">
                            <button class="btn-icon" onclick="toggleEditForm(<?php echo $row['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon" onclick="window.location.href='?delete=<?php echo $row['id']; ?>'">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if ($result->num_rows == 0): ?>
                    <div class="no-tasks">
                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                        <p>No quests found. Embark on a new adventure above!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleEditForm(taskId) {
            const form = document.getElementById(`edit-form-${taskId}`);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }

        // Add sparkle effect on task completion
        document.querySelectorAll('.btn-icon').forEach(button => {
            button.addEventListener('click', (e) => {
                if (e.target.classList.contains('fa-check-circle') || e.target.classList.contains('fa-circle')) {
                    const sparkle = document.createElement('div');
                    sparkle.className = 'sparkle';
                    sparkle.style.left = `${e.clientX}px`;
                    sparkle.style.top = `${e.clientY}px`;
                    document.body.appendChild(sparkle);
                    setTimeout(() => sparkle.remove(), 2000);
                }
            });
        });
    </script>
</body>
</html>
