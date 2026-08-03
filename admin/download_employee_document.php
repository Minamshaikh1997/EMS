<?php
session_start();
include('admincheck_role.php');
include('../config/db.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare('SELECT file_name, original_name, mime_type, file_size FROM employee_documents WHERE id=?');
$stmt->bind_param('i', $id); $stmt->execute(); $document = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$document) { http_response_code(404); exit('Document not found.'); }
$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'employee_documents' . DIRECTORY_SEPARATOR . basename($document['file_name']);
if (!is_file($path)) { http_response_code(404); exit('Stored file not found.'); }
header('Content-Type: ' . $document['mime_type']);
header('Content-Length: ' . filesize($path));
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($document['original_name']));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
