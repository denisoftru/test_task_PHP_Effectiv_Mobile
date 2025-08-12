<?php

require_once 'config.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove /api.php from path
$path = str_replace('/api.php', '', $path);

// Get database connection
$db = getDatabase();

// Handle different HTTP methods
switch($method) {
    case 'GET':
        if($path == '/tasks') {
            getAllTasks($db);
        } elseif(preg_match('/\/tasks\/(\d+)/', $path, $matches)) {
            getTask($db, $matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        break;
        
    case 'POST':
        if($path == '/tasks') {
            createTask($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        break;
        
    case 'PUT':
        if(preg_match('/\/tasks\/(\d+)/', $path, $matches)) {
            updateTask($db, $matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        break;
        
    case 'DELETE':
        if(preg_match('/\/tasks\/(\d+)/', $path, $matches)) {
            deleteTask($db, $matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all tasks
function getAllTasks($db) {
    $stmt = $db->query("SELECT * FROM tasks ORDER BY id DESC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tasks);
}

// Get single task
function getTask($db, $id) {
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($task) {
        echo json_encode($task);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
    }
}

// Create new task
function createTask($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Basic validation
    if(empty($input['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required']);
        return;
    }
    
    $title = $input['title'];
    $description = isset($input['description']) ? $input['description'] : '';
    $status = isset($input['status']) ? $input['status'] : 'pending';
    
    // Check if status is valid
    $validStatuses = ['pending', 'in_progress', 'completed'];
    if(!in_array($status, $validStatuses)) {
        $status = 'pending';
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $status]);
        
        $newId = $db->lastInsertId();
        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$newId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode($task);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create task']);
    }
}

// Update task
function updateTask($db, $id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if task exists
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$task) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
        return;
    }
    
    // Basic validation
    if(empty($input['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required']);
        return;
    }
    
    $title = $input['title'];
    $description = isset($input['description']) ? $input['description'] : $task['description'];
    $status = isset($input['status']) ? $input['status'] : $task['status'];
    
    // Check if status is valid
    $validStatuses = ['pending', 'in_progress', 'completed'];
    if(!in_array($status, $validStatuses)) {
        $status = $task['status'];
    }
    
    try {
        $stmt = $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ?");
        $stmt->execute([$title, $description, $status, $id]);
        
        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        $updatedTask = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode($updatedTask);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update task']);
    }
}

// Delete task
function deleteTask($db, $id) {
    // Check if task exists
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$task) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['message' => 'Task deleted successfully']);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete task']);
    }
}

?>
