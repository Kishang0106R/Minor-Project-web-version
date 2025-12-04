<?php
function check_session_timeout($redirect_page = 'login.php') {
    $timeout = 1800; // 30 minutes in seconds

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session expired
        session_unset();
        session_destroy();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // AJAX request, return JSON
            echo json_encode(['success' => false, 'message' => 'Session expired', 'redirect' => true]);
            exit();
        } else {
            // Normal request, redirect
            header("Location: $redirect_page");
            exit();
        }
    }

    $_SESSION['last_activity'] = time();
}
?>
