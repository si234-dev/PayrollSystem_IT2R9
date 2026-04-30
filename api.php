<?php
session_start();
header('Content-Type: application/json');

$db = new PDO("mysql:host=localhost;dbname=farari_db", "root", "");

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $pw = ($_POST['username'] === 'admin') ? $_POST['password'] : password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password, fName, lName, email, contactnumber, sex, address) VALUES (?,?,?,?,?,?,?,?)";
    $stmt = $db->prepare($sql);
    try {
        $stmt->execute([$_POST['username'], $pw, $_POST['fName'], $_POST['lName'], $_POST['email'], $_POST['contactnumber'], $_POST['sex'], $_POST['address']]);
        echo json_encode(['status' => 'success']);
    } catch(Exception $e) { echo json_encode(['status' => 'error', 'message' => 'User exists']); }
} 

elseif ($action === 'login') {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u && (($_POST['username']==='admin' && $_POST['password']==='admin') || password_verify($_POST['password'], $u['password']))) {
        $_SESSION['user'] = $u;
        echo json_encode(['status' => 'success']);
    } else { echo json_encode(['status' => 'error']); }
}

elseif ($action === 'check_session') {
    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        echo json_encode(['status' => 'success', 'data' => $_SESSION['user']]);
    } else { echo json_encode(['status' => 'error']); }
}

elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success']);
}


elseif ($action === 'submit_leave_request') {
    $user_id = $_SESSION['user']['id'];
    $leave_type_id = $_POST['leave_type_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];
    $attachment = null;

    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_name = time() . '_' . basename($_FILES['attachment']['name']);
        $attachment = $upload_dir . $file_name;
        move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment);
    }

    $sql = "INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, reason, attachment) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    try {
        $stmt->execute([$user_id, $leave_type_id, $start_date, $end_date, $reason, $attachment]);
        echo json_encode(['status' => 'success', 'message' => 'Leave request submitted']);
    } catch(Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit request']);
    }
}

elseif ($action === 'get_leave_requests') {
    // For Admin - Get all pending requests
    if ($_SESSION['user']['username'] !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $sql = "SELECT lr.*, u.fName, u.lName, u.username, lt.name as leave_type 
            FROM leave_requests lr
            JOIN users u ON lr.user_id = u.id
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.status = 'Pending'
            ORDER BY lr.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $requests]);
}

elseif ($action === 'get_my_leave_requests') {
    // For Employee - Get their own requests
    $user_id = $_SESSION['user']['id'];
    
    $sql = "SELECT lr.*, lt.name as leave_type 
            FROM leave_requests lr
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.user_id = ?
            ORDER BY lr.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $requests]);
}

elseif ($action === 'approve_leave_request') {
    // Admin approval
    if ($_SESSION['user']['username'] !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $request_id = $_POST['request_id'];
    $admin_id = $_SESSION['user']['id'];

    $sql = "UPDATE leave_requests 
            SET status = 'Approved', approved_by = ?, approved_date = NOW()
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    
    try {
        $stmt->execute([$admin_id, $request_id]);
        echo json_encode(['status' => 'success', 'message' => 'Leave request approved']);
    } catch(Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to approve']);
    }
}

elseif ($action === 'reject_leave_request') {
    // Admin rejection
    if ($_SESSION['user']['username'] !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $request_id = $_POST['request_id'];
    $admin_id = $_SESSION['user']['id'];

    $sql = "UPDATE leave_requests 
            SET status = 'Rejected', approved_by = ?, approved_date = NOW()
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    
    try {
        $stmt->execute([$admin_id, $request_id]);
        echo json_encode(['status' => 'success', 'message' => 'Leave request rejected']);
    } catch(Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reject']);
    }
}

elseif ($action === 'get_leave_types') {
    $sql = "SELECT * FROM leave_types";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $types]);
}
