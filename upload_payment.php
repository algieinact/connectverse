<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$booking_id = $_POST['booking_id'] ?? 0;
$payment_method = $_POST['payment_method'] ?? '';
$error = '';
$success = '';

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT eb.*, e.price 
        FROM event_bookings eb
        JOIN events e ON eb.event_id = e.id
        WHERE eb.id = ? AND eb.user_id = ?
    ");
    $stmt->execute([$booking_id, $user['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Booking tidak ditemukan!');
    }

    // Handle file upload
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Bukti pembayaran harus diupload!');
    }

    $payment_proof = uploadImage($_FILES['payment_proof'], 'uploads/payments/');

    // Create transaction record
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, event_id, amount, payment_method, payment_proof)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $booking['event_id'],
        $booking['price'],
        $payment_method,
        $payment_proof
    ]);
    $transaction_id = $pdo->lastInsertId();

    // Update booking with transaction ID
    $stmt = $pdo->prepare("
        UPDATE event_bookings 
        SET transaction_id = ?, status = 'pending'
        WHERE id = ?
    ");
    $stmt->execute([$transaction_id, $booking_id]);

    $success = 'Bukti pembayaran berhasil diupload! Silakan tunggu verifikasi dari penyedia event.';
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Redirect back with message
$redirect_url = 'my_activities.php';
if ($error) {
    $redirect_url .= '?error=' . urlencode($error);
} elseif ($success) {
    $redirect_url .= '?success=' . urlencode($success);
}
header('Location: ' . $redirect_url);
exit; 