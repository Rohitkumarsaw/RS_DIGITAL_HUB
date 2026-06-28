<?php
require_once __DIR__ . '/../config.php';
requireStrictAdmin();

require_once __DIR__ . '/../classes/Guideline.php';
$guideline = new Guideline($pdo);

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');
$token = sanitize($_POST['csrf_token'] ?? $_GET['token'] ?? '');

if ($token !== ($_SESSION['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid security token.');
    redirect(ADMIN_URL . '/guidelines.php');
}

switch ($action) {
    case 'create':
        $title = sanitize($_POST['title']);
        $content = sanitizeHtml($_POST['content']);
        if (empty($title) || empty($content)) {
            setFlash('error', 'Title and content are required.');
        } else {
            $guideline->create($title, $content);
            setFlash('success', 'Guideline added successfully.');
        }
        redirect(ADMIN_URL . '/guidelines.php');
        break;

    case 'update':
        $id = (int)$_POST['id'];
        $title = sanitize($_POST['title']);
        $content = sanitizeHtml($_POST['content']);
        if ($id && !empty($title) && !empty($content)) {
            $guideline->update($id, $title, $content);
            setFlash('success', 'Guideline updated successfully.');
        } else {
            setFlash('error', 'Title and content are required.');
        }
        redirect(ADMIN_URL . '/guidelines.php');
        break;

    case 'delete':
        $id = (int)$_GET['id'];
        if ($id) {
            $guideline->delete($id);
            setFlash('success', 'Guideline deleted successfully.');
        }
        redirect(ADMIN_URL . '/guidelines.php');
        break;

    default:
        setFlash('error', 'Invalid action.');
        redirect(ADMIN_URL . '/guidelines.php');
}
