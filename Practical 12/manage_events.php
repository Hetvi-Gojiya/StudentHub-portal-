<?php
// manage_events.php - Practical 12: Event Management CRUD with Poster Upload and File Validation
session_start();
require_once 'db.php';

// Access Check: Admin users only (or inform user to login as admin)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

$message = "";
$messageType = "";

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// -------------------------------------------------------------
// Helper: File Upload and Validation Function
// -------------------------------------------------------------
function handlePosterUpload($fileInputName, &$errorMsg) {
    global $uploadDir;
    
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded (poster is optional)
    }

    $file = $_FILES[$fileInputName];

    // 1. Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "File upload failed with error code: " . $file['error'];
        return false;
    }

    // 2. Validate File Size (Maximum 2MB = 2 * 1024 * 1024 bytes)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $errorMsg = "File size exceeds limit! Maximum allowed poster size is 2MB.";
        return false;
    }

    // 3. Validate File Extension & MIME Type (Security validation)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions)) {
        $errorMsg = "Invalid file type (." . htmlspecialchars($fileExt) . ")! Only JPG, PNG, and WebP poster images are allowed.";
        return false;
    }

    // Verify MIME type using finfo or getimagesize
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        $errorMsg = "Security rejection: The uploaded file is not a valid image format.";
        return false;
    }

    // 4. Advanced Extension: Secure Image Renaming (prevents collisions & directory traversal)
    $uniqueFileName = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
    $destination = $uploadDir . $uniqueFileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $uniqueFileName;
    } else {
        $errorMsg = "Failed to save the uploaded image to the server directory.";
        return false;
    }
}

// -------------------------------------------------------------
// 1. Handle CREATE: Add Event with Poster
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $category = trim($_POST['category'] ?? 'Technical');
    $status = trim($_POST['status'] ?? 'open');

    if (empty($title) || empty($event_date) || empty($venue)) {
        $message = "Please fill in all required fields (Title, Date, Venue).";
        $messageType = "error";
    } else {
        $uploadError = "";
        $posterFileName = handlePosterUpload('poster', $uploadError);

        if ($posterFileName === false) {
            // Validation failed for file
            $message = $uploadError;
            $messageType = "error";
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO events (title, description, event_date, venue, category, status, poster) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($insertStmt->execute([$title, $description, $event_date, $venue, $category, $status, $posterFileName])) {
                $message = "Event '$title' successfully created with validated poster upload!";
                $messageType = "success";
            } else {
                $message = "Database Error: Could not save event.";
                $messageType = "error";
            }
        }
    }
}

// -------------------------------------------------------------
// 2. Handle UPDATE: Edit Event & Optional Poster Replacement
// -------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $category = trim($_POST['category'] ?? 'Technical');
    $status = trim($_POST['status'] ?? 'open');

    if ($id <= 0 || empty($title) || empty($event_date)) {
        $message = "Invalid event details for update.";
        $messageType = "error";
    } else {
        $uploadError = "";
        $newPoster = handlePosterUpload('poster', $uploadError);

        if ($newPoster === false) {
            $message = $uploadError;
            $messageType = "error";
        } else {
            if ($newPoster !== null) {
                // If a new poster was uploaded, update poster column
                $updateStmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, venue = ?, category = ?, status = ?, poster = ? WHERE id = ?");
                $success = $updateStmt->execute([$title, $description, $event_date, $venue, $category, $status, $newPoster, $id]);
            } else {
                // Keep existing poster
                $updateStmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, venue = ?, category = ?, status = ? WHERE id = ?");
                $success = $updateStmt->execute([$title, $description, $event_date, $venue, $category, $status, $id]);
            }

            if ($success) {
                $message = "Event #$id ('$title') updated successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to update event record.";
                $messageType = "error";
            }
        }
    }
}

// -------------------------------------------------------------
// 3. Handle DELETE: Delete Event & Remove Poster File
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        // Fetch poster filename to delete file from disk
        $fetchStmt = $pdo->prepare("SELECT poster FROM events WHERE id = ?");
        $fetchStmt->execute([$id]);
        $event = $fetchStmt->fetch();

        if ($event && !empty($event['poster'])) {
            $filePath = $uploadDir . $event['poster'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $delStmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        if ($delStmt->execute([$id])) {
            $message = "Event #$id successfully deleted from database.";
            $messageType = "success";
        } else {
            $message = "Failed to delete event.";
            $messageType = "error";
        }
    }
}

// -------------------------------------------------------------
// 4. Handle SEARCH & FILTER (Intermediate Extension: Category & Open/Closed)
// -------------------------------------------------------------
$searchKeyword = trim($_GET['search'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (title LIKE ? OR venue LIKE ?)";
    $params[] = "%$searchKeyword%";
    $params[] = "%$searchKeyword%";
}

if (!empty($filterCategory)) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
}

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY event_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
$totalEvents = count($events);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Event Management CRUD (Practical 12)</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .event-container {
            max-width: 1200px;
            margin: 25px auto;
            padding: 0 15px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn-ev {
            padding: 9px 18px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-add { background: #10b981; color: white; }
        .btn-add:hover { background: #059669; }
        .btn-reset { background: #64748b; color: white; }
        .btn-reset:hover { background: #475569; }
        .btn-search { background: #2563eb; color: white; }
        .btn-search:hover { background: #1d4ed8; }

        .filter-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }
        .filter-form input, .filter-form select {
            padding: 9px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }

        .alert-ev {
            padding: 14px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .event-poster-wrapper {
            width: 100%;
            height: 180px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .event-poster-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-poster-placeholder {
            font-size: 48px;
            color: #94a3b8;
        }

        .event-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .event-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }
        .badge-cat {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
        }
        .badge-open {
            background: #dcfce7;
            color: #15803d;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
        }
        .badge-closed {
            background: #fee2e2;
            color: #b91c1c;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        .event-title {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .event-desc {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 15px;
            flex: 1;
            line-height: 1.5;
        }

        .event-meta {
            font-size: 13px;
            color: #334155;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .event-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            border-top: 1px solid #f1f5f9;
            padding-top: 15px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .action-edit { background: #e0f2fe; color: #0284c7; }
        .action-edit:hover { background: #bae6fd; }
        .action-delete { background: #fee2e2; color: #dc2626; }
        .action-delete:hover { background: #fecaca; }

        /* Modal Styles */
        .crud-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #475569; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
            font-family: inherit;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .file-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
    </style>
</head>

<body>

    <header>
        <h1>Campus Events Management (Practical 12)</h1>
        <button id="themeBtn">Theme</button>
    </header>

    <div class="event-container">

        <!-- Top Navigation Strip -->
        <div class="header-actions">
            <div>
                <a href="admin_dashboard.php" class="btn-ev btn-reset">👑 Admin Console</a>
                <a href="dashboard.php" class="btn-ev btn-reset">🎓 Student Dashboard</a>
                <a href="view_records.php" class="btn-ev btn-reset">📋 Student Registry</a>
            </div>
            <div>
                <button onclick="openAddEventModal()" class="btn-ev btn-add">➕ Add New Event & Poster</button>
            </div>
        </div>

        <!-- Feedback Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert-ev <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Card (Intermediate Extension: Category and Open/Closed) -->
        <div class="filter-card">
            <form method="GET" action="manage_events.php" class="filter-form">
                <input type="text" name="search" placeholder="🔍 Search event title or venue..." value="<?php echo htmlspecialchars($searchKeyword); ?>" style="flex: 2; min-width: 220px;">
                
                <select name="category" style="flex: 1; min-width: 140px;">
                    <option value="">All Categories</option>
                    <option value="Technical" <?php echo $filterCategory === 'Technical' ? 'selected' : ''; ?>>Technical</option>
                    <option value="Cultural" <?php echo $filterCategory === 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
                    <option value="Sports" <?php echo $filterCategory === 'Sports' ? 'selected' : ''; ?>>Sports</option>
                    <option value="Seminar" <?php echo $filterCategory === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                </select>

                <select name="status" style="flex: 1; min-width: 130px;">
                    <option value="">All Statuses</option>
                    <option value="open" <?php echo $filterStatus === 'open' ? 'selected' : ''; ?>>Open for Registration</option>
                    <option value="closed" <?php echo $filterStatus === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>

                <button type="submit" class="btn-ev btn-search">Filter Events</button>
                <a href="manage_events.php" class="btn-ev btn-reset">Clear</a>
            </form>
            <div style="margin-top: 10px; font-size: 13px; color: #64748b;">
                Displaying <strong><?php echo $totalEvents; ?></strong> campus events from database.
            </div>
        </div>

        <!-- Events Grid (Practical 12 Event Cards with Posters) -->
        <div class="events-grid">
            <?php if (empty($events)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 12px; color: #94a3b8;">
                    No events found matching your filter criteria.
                </div>
            <?php else: ?>
                <?php foreach ($events as $ev): ?>
                    <div class="event-card">
                        <!-- Poster Preview -->
                        <div class="event-poster-wrapper">
                            <?php if (!empty($ev['poster']) && file_exists($uploadDir . $ev['poster'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($ev['poster']); ?>" alt="Poster" class="event-poster-img">
                            <?php else: ?>
                                <div class="event-poster-placeholder">
                                    <?php 
                                        $icon = '📅';
                                        if ($ev['category'] === 'Technical') $icon = '💻';
                                        elseif ($ev['category'] === 'Cultural') $icon = '🎭';
                                        elseif ($ev['category'] === 'Sports') $icon = '⚽';
                                        elseif ($ev['category'] === 'Seminar') $icon = '🎙️';
                                        echo $icon;
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Event Details -->
                        <div class="event-body">
                            <div class="event-badges">
                                <span class="badge-cat"><?php echo htmlspecialchars($ev['category']); ?></span>
                                <?php if ($ev['status'] === 'open'): ?>
                                    <span class="badge-open">🟢 Registration Open</span>
                                <?php else: ?>
                                    <span class="badge-closed">🔴 Closed</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="event-title"><?php echo htmlspecialchars($ev['title']); ?></h3>
                            <p class="event-desc"><?php echo htmlspecialchars($ev['description']); ?></p>

                            <div class="event-meta">
                                <div>📅 <strong>Date:</strong> <?php echo htmlspecialchars($ev['event_date']); ?></div>
                                <div>📍 <strong>Venue:</strong> <?php echo htmlspecialchars($ev['venue']); ?></div>
                                <?php if (!empty($ev['poster'])): ?>
                                    <div style="font-size: 11px; color: #64748b; font-family: monospace;">🖼️ <?php echo htmlspecialchars($ev['poster']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="event-actions">
                                <button onclick='openEditEventModal(<?php echo json_encode($ev); ?>)' class="action-btn action-edit">✏️ Edit</button>
                                <a href="manage_events.php?action=delete&id=<?php echo $ev['id']; ?>" class="action-btn action-delete" onclick="return confirm('Are you sure you want to delete event \'<?php echo htmlspecialchars(addslashes($ev['title'])); ?>\'? The poster image will also be removed.');">🗑️ Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- -------------------------------------------------------------
         CREATE EVENT MODAL (With File Upload & Validation)
    -------------------------------------------------------------- -->
    <div id="addEventModal" class="crud-modal">
        <div class="modal-content">
            <h3 style="margin-top: 0; color: #0f172a;">➕ Add New Campus Event</h3>
            <!-- Note enctype="multipart/form-data" for file upload -->
            <form method="POST" action="manage_events.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" name="title" required placeholder="e.g. AI Innovation Summit 2026">
                </div>

                <div class="form-group">
                    <label>Event Description</label>
                    <textarea name="description" rows="3" placeholder="Brief outline of the event..."></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Date *</label>
                        <input type="date" name="event_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Category *</label>
                        <select name="category">
                            <option value="Technical">Technical</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Sports">Sports</option>
                            <option value="Seminar">Seminar</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 2;">
                        <label>Venue *</label>
                        <input type="text" name="venue" required placeholder="e.g. Main Auditorium">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Status</label>
                        <select name="status">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>

                <!-- Practical 12: Poster Upload Input with Type/Size Hints -->
                <div class="form-group">
                    <label>🖼️ Event Poster (File Upload) *</label>
                    <input type="file" name="poster" id="add_poster" accept="image/jpeg,image/png,image/webp">
                    <div class="file-hint">
                        • <strong>Allowed file types:</strong> JPG, PNG, WebP<br>
                        • <strong>Maximum size:</strong> 2MB<br>
                        • <em>Advanced: Image will be securely sanitized & renamed automatically.</em>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeAddEventModal()" class="btn-ev btn-reset">Cancel</button>
                    <button type="submit" class="btn-ev btn-add">Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         UPDATE EVENT MODAL
    -------------------------------------------------------------- -->
    <div id="editEventModal" class="crud-modal">
        <div class="modal-content">
            <h3 style="margin-top: 0; color: #0f172a;">✏️ Edit Campus Event</h3>
            <form method="POST" action="manage_events.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_ev_id">

                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" name="title" id="edit_ev_title" required>
                </div>

                <div class="form-group">
                    <label>Event Description</label>
                    <textarea name="description" id="edit_ev_desc" rows="3"></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Date *</label>
                        <input type="date" name="event_date" id="edit_ev_date" required>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Category *</label>
                        <select name="category" id="edit_ev_category">
                            <option value="Technical">Technical</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Sports">Sports</option>
                            <option value="Seminar">Seminar</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 2;">
                        <label>Venue *</label>
                        <input type="text" name="venue" id="edit_ev_venue" required>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Status</label>
                        <select name="status" id="edit_ev_status">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Replace Poster (Optional)</label>
                    <input type="file" name="poster" accept="image/jpeg,image/png,image/webp">
                    <div class="file-hint">Leave blank to keep the current poster image.</div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditEventModal()" class="btn-ev btn-reset">Cancel</button>
                    <button type="submit" class="btn-ev btn-search">Update Event</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddEventModal() {
            document.getElementById('addEventModal').style.display = 'flex';
        }
        function closeAddEventModal() {
            document.getElementById('addEventModal').style.display = 'none';
        }

        function openEditEventModal(event) {
            document.getElementById('edit_ev_id').value = event.id;
            document.getElementById('edit_ev_title').value = event.title;
            document.getElementById('edit_ev_desc').value = event.description;
            document.getElementById('edit_ev_date').value = event.event_date;
            document.getElementById('edit_ev_category').value = event.category;
            document.getElementById('edit_ev_venue').value = event.venue;
            document.getElementById('edit_ev_status').value = event.status;
            document.getElementById('editEventModal').style.display = 'flex';
        }
        function closeEditEventModal() {
            document.getElementById('editEventModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('addEventModal')) {
                closeAddEventModal();
            }
            if (event.target === document.getElementById('editEventModal')) {
                closeEditEventModal();
            }
        };
    </script>

    <script src="script.js"></script>
</body>

</html>
