<?php
// api_events.php - PHP JSON API Endpoint for Events (Fetch API Backend)
// Supports: GET (list/single), POST (create), PUT (update), DELETE (remove)

error_reporting(E_ALL);
ini_set('display_errors', 0); // Never show errors inline — log them instead

session_start();
require_once 'db.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ---------------------------------------------------------------
// Helper: Send JSON Response
// ---------------------------------------------------------------
function sendResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    $response = [
        'status'  => $status,
        'message' => $message,
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// ---------------------------------------------------------------
// Helper: Input Validation
// ---------------------------------------------------------------
function validateEventInput($data, $requireAll = true) {
    $errors = [];

    if ($requireAll || isset($data['title'])) {
        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = "Event title is required.";
        } elseif (strlen($data['title']) < 3) {
            $errors[] = "Event title must be at least 3 characters.";
        } elseif (strlen($data['title']) > 150) {
            $errors[] = "Event title cannot exceed 150 characters.";
        }
    }

    if ($requireAll || isset($data['event_date'])) {
        if (empty($data['event_date'] ?? '')) {
            $errors[] = "Event date is required.";
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $data['event_date']);
            if (!$date) {
                $errors[] = "Invalid date format. Use YYYY-MM-DD.";
            }
        }
    }

    if ($requireAll || isset($data['category'])) {
        $allowedCategories = ['Academic', 'Cultural', 'Sports', 'Technical', 'Workshop', 'Other'];
        if (empty($data['category'] ?? '')) {
            $errors[] = "Event category is required.";
        } elseif (!in_array($data['category'], $allowedCategories)) {
            $errors[] = "Invalid category. Choose from: " . implode(', ', $allowedCategories);
        }
    }

    if (isset($data['max_participants']) && $data['max_participants'] !== '' && $data['max_participants'] !== null) {
        if (!is_numeric($data['max_participants']) || (int)$data['max_participants'] < 1) {
            $errors[] = "Max participants must be a positive number.";
        }
    }

    return $errors;
}

// ---------------------------------------------------------------
// Get HTTP method and route
// ---------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ---------------------------------------------------------------
// GET — List all events OR get single event
// ---------------------------------------------------------------
if ($method === 'GET') {

    // GET single event by ID
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            sendResponse('success', 'Event retrieved successfully.', $event);
        } else {
            sendResponse('error', 'Event not found.', null, 404);
        }
    }

    // GET all events with optional search/filter
    $search   = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $status   = isset($_GET['status']) ? trim($_GET['status']) : '';

    $conditions = [];
    $params     = [];

    if ($search !== '') {
        $conditions[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($category !== '') {
        $conditions[] = "category = ?";
        $params[] = $category;
    }

    if ($status !== '') {
        $conditions[] = "status = ?";
        $params[] = $status;
    }

    $where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";
    $sql   = "SELECT * FROM events $where ORDER BY event_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse('success', count($events) . ' event(s) found.', [
        'count'  => count($events),
        'events' => $events
    ]);
}

// ---------------------------------------------------------------
// POST — Create new event
// ---------------------------------------------------------------
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input    = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        sendResponse('error', 'Invalid JSON body. Make sure Content-Type is application/json. Got: ' . substr($rawInput, 0, 100), null, 400);
    }

    // Validate input
    $errors = validateEventInput($input, true);
    if (!empty($errors)) {
        sendResponse('error', 'Validation failed.', ['validation_errors' => $errors], 422);
    }

    $title            = trim($input['title']);
    $description      = trim($input['description'] ?? '');
    $event_date       = $input['event_date'];
    $location         = trim($input['location'] ?? '');
    $category         = $input['category'];
    $max_participants = (isset($input['max_participants']) && $input['max_participants'] !== '' && $input['max_participants'] !== null)
                        ? (int)$input['max_participants'] : null;
    $status           = in_array($input['status'] ?? '', ['upcoming', 'ongoing', 'completed', 'cancelled'])
                        ? $input['status'] : 'upcoming';

    $stmt = $pdo->prepare("
        INSERT INTO events (title, description, event_date, location, category, max_participants, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$title, $description, $event_date, $location, $category, $max_participants, $status]);
    $newId = $pdo->lastInsertId();

    // Fetch and return the newly created event
    $fetchNew = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $fetchNew->execute([$newId]);

    sendResponse('success', 'Event created successfully.', $fetchNew->fetch(PDO::FETCH_ASSOC), 201);
}

// ---------------------------------------------------------------
// PUT — Update existing event
// ---------------------------------------------------------------
if ($method === 'PUT') {
    if (!$id) {
        sendResponse('error', 'Event ID is required for update. Use ?id=X', null, 400);
    }

    // Check event exists
    $check = $pdo->prepare("SELECT id FROM events WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendResponse('error', "Event with ID $id not found.", null, 404);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        sendResponse('error', 'Invalid JSON body.', null, 400);
    }

    // Validate (partial update allowed)
    $errors = validateEventInput($input, false);
    if (!empty($errors)) {
        sendResponse('error', 'Validation failed.', ['validation_errors' => $errors], 422);
    }

    // Build dynamic SET clause
    $fields = [];
    $params = [];

    if (isset($input['title']))            { $fields[] = "title = ?";            $params[] = trim($input['title']); }
    if (isset($input['description']))      { $fields[] = "description = ?";      $params[] = trim($input['description']); }
    if (isset($input['event_date']))       { $fields[] = "event_date = ?";       $params[] = $input['event_date']; }
    if (isset($input['location']))         { $fields[] = "location = ?";         $params[] = trim($input['location']); }
    if (isset($input['category']))         { $fields[] = "category = ?";         $params[] = $input['category']; }
    if (isset($input['max_participants'])) { $fields[] = "max_participants = ?"; $params[] = ($input['max_participants'] !== '') ? (int)$input['max_participants'] : null; }
    if (isset($input['status']))           { $fields[] = "status = ?";           $params[] = $input['status']; }

    if (empty($fields)) {
        sendResponse('error', 'No fields provided to update.', null, 400);
    }

    $params[] = $id;
    $sql      = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt     = $pdo->prepare($sql);
    $stmt->execute($params);

    // Fetch and return updated event
    $updated = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $updated->execute([$id]);

    sendResponse('success', 'Event updated successfully.', $updated->fetch(PDO::FETCH_ASSOC));
}

// ---------------------------------------------------------------
// DELETE — Delete event by ID
// ---------------------------------------------------------------
if ($method === 'DELETE') {
    if (!$id) {
        sendResponse('error', 'Event ID is required for delete. Use ?id=X', null, 400);
    }

    $check = $pdo->prepare("SELECT id, title FROM events WHERE id = ?");
    $check->execute([$id]);
    $event = $check->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        sendResponse('error', "Event with ID $id not found.", null, 404);
    }

    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);

    sendResponse('success', "Event '{$event['title']}' deleted successfully.", ['deleted_id' => $id]);
}

// If method not matched
sendResponse('error', "Method '$method' not supported.", null, 405);
