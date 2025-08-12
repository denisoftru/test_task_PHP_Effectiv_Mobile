<!DOCTYPE html>
<html>
<head>
    <title>Tasks API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; }
        .endpoint { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        .method { font-weight: bold; color: blue; }
        input, textarea { width: 300px; padding: 5px; margin: 5px; }
        button { padding: 10px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer; }
        #result { background: #f9f9f9; padding: 10px; margin: 10px 0; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple Tasks API</h1>
        
        <h2>API Endpoints:</h2>
        <div class="endpoint">
            <span class="method">GET</span> /api.php/tasks - Get all tasks
        </div>
        <div class="endpoint">
            <span class="method">GET</span> /api.php/tasks/{id} - Get task by ID
        </div>
        <div class="endpoint">
            <span class="method">POST</span> /api.php/tasks - Create new task
        </div>
        <div class="endpoint">
            <span class="method">PUT</span> /api.php/tasks/{id} - Update task
        </div>
        <div class="endpoint">
            <span class="method">DELETE</span> /api.php/tasks/{id} - Delete task
        </div>
        
        <h2>Test the API:</h2>
        
        <h3>Create Task</h3>
        <input type="text" id="title" placeholder="Task title" required><br>
        <textarea id="description" placeholder="Task description"></textarea><br>
        <select id="status">
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select><br>
        <button onclick="createTask()">Create Task</button>
        
        <h3>Get All Tasks</h3>
        <button onclick="getAllTasks()">Get All Tasks</button>
        
        <h3>Get Task by ID</h3>
        <input type="number" id="taskId" placeholder="Task ID">
        <button onclick="getTask()">Get Task</button>
        
        <h3>Update Task</h3>
        <input type="number" id="updateId" placeholder="Task ID"><br>
        <input type="text" id="updateTitle" placeholder="New title"><br>
        <textarea id="updateDescription" placeholder="New description"></textarea><br>
        <select id="updateStatus">
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select><br>
        <button onclick="updateTask()">Update Task</button>
        
        <h3>Delete Task</h3>
        <input type="number" id="deleteId" placeholder="Task ID">
        <button onclick="deleteTask()">Delete Task</button>
        
        <h3>Result:</h3>
        <div id="result"></div>
    </div>

    <script>
        function showResult(data) {
            document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        function createTask() {
            const title = document.getElementById('title').value;
            const description = document.getElementById('description').value;
            const status = document.getElementById('status').value;
            
            if(!title) {
                alert('Title is required!');
                return;
            }
            
            fetch('/api.php/tasks', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({title, description, status})
            })
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({error: error.message}));
        }
        
        function getAllTasks() {
            fetch('/api.php/tasks')
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({error: error.message}));
        }
        
        function getTask() {
            const id = document.getElementById('taskId').value;
            if(!id) {
                alert('Task ID is required!');
                return;
            }
            
            fetch('/api.php/tasks/' + id)
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({error: error.message}));
        }
        
        function updateTask() {
            const id = document.getElementById('updateId').value;
            const title = document.getElementById('updateTitle').value;
            const description = document.getElementById('updateDescription').value;
            const status = document.getElementById('updateStatus').value;
            
            if(!id || !title) {
                alert('Task ID and title are required!');
                return;
            }
            
            fetch('/api.php/tasks/' + id, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({title, description, status})
            })
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({error: error.message}));
        }
        
        function deleteTask() {
            const id = document.getElementById('deleteId').value;
            if(!id) {
                alert('Task ID is required!');
                return;
            }
            
            fetch('/api.php/tasks/' + id, {method: 'DELETE'})
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({error: error.message}));
        }
    </script>
    
    <?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }

    // Database setup
    $db_file = 'tasks.db';
    try {
        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    // Get request method and parse URL
    $method = $_SERVER['REQUEST_METHOD'];
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);

    // Remove index.php from path if present
    $path = str_replace('/index.php', '', $path);
    $path = rtrim($path, '/');

    // Route the request
    if ($path == '/tasks' && $method == 'GET') {
        // GET /tasks - Get all tasks
        getAllTasks($pdo);
    } elseif (preg_match('/^\/tasks\/(\d+)$/', $path, $matches) && $method == 'GET') {
        // GET /tasks/{id} - Get single task
        getTask($pdo, $matches[1]);
    } elseif ($path == '/tasks' && $method == 'POST') {
        // POST /tasks - Create task
        createTask($pdo);
    } elseif (preg_match('/^\/tasks\/(\d+)$/', $path, $matches) && $method == 'PUT') {
        // PUT /tasks/{id} - Update task
        updateTask($pdo, $matches[1]);
    } elseif (preg_match('/^\/tasks\/(\d+)$/', $path, $matches) && $method == 'DELETE') {
        // DELETE /tasks/{id} - Delete task
        deleteTask($pdo, $matches[1]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }

    // Function to get all tasks
    function getAllTasks($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM tasks ORDER BY created_at DESC");
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $tasks]);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch tasks']);
        }
    }

    // Function to get single task
    function getTask($pdo, $id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                echo json_encode(['success' => true, 'data' => $task]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Task not found']);
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch task']);
        }
    }

    // Function to create task
    function createTask($pdo) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = validateTask($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['error' => 'Validation failed', 'details' => $errors]);
            return;
        }
        
        try {
            $title = trim($input['title']);
            $description = isset($input['description']) ? trim($input['description']) : '';
            $status = isset($input['status']) ? $input['status'] : 'pending';
            
            // Validate status
            $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
            if (!in_array($status, $valid_statuses)) {
                $status = 'pending';
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, status, created_at, updated_at) 
                VALUES (?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->execute([$title, $description, $status]);
            
            $new_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$new_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(201);
            echo json_encode(['success' => true, 'data' => $task, 'message' => 'Task created successfully']);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create task']);
        }
    }

    // Function to update task
    function updateTask($pdo, $id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Check if task exists
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        $existing_task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing_task) {
            http_response_code(404);
            echo json_encode(['error' => 'Task not found']);
            return;
        }
        
        // Validate input
        $errors = validateTask($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['error' => 'Validation failed', 'details' => $errors]);
            return;
        }
        
        try {
            $title = trim($input['title']);
            $description = isset($input['description']) ? trim($input['description']) : $existing_task['description'];
            $status = isset($input['status']) ? $input['status'] : $existing_task['status'];
            
            // Validate status
            $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
            if (!in_array($status, $valid_statuses)) {
                $status = $existing_task['status'];
            }
            
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET title = ?, description = ?, status = ?, updated_at = datetime('now') 
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $status, $id]);
            
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $task, 'message' => 'Task updated successfully']);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update task']);
        }
    }

    // Function to delete task
    function deleteTask($pdo, $id) {
        try {
            // Check if task exists
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                http_response_code(404);
                echo json_encode(['error' => 'Task not found']);
                return;
            }
            
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete task']);
        }
    }

    // Function to validate task data
    function validateTask($data) {
        $errors = [];
        
        // Check if title exists and is not empty
        if (!isset($data['title']) || empty(trim($data['title']))) {
            $errors[] = 'Title is required and cannot be empty';
        } elseif (strlen($data['title']) > 255) {
            $errors[] = 'Title cannot be longer than 255 characters';
        }
        
        // Check description length if provided
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            $errors[] = 'Description cannot be longer than 1000 characters';
        }
        
        // Check status if provided
        if (isset($data['status'])) {
            $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
            if (!in_array($data['status'], $valid_statuses)) {
                $errors[] = 'Status must be one of: ' . implode(', ', $valid_statuses);
            }
        }
        
        return $errors;
    }
    ?>
</body>
</html>
