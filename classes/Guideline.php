<?php
class Guideline {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM guidelines ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM guidelines WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($title, $content) {
        $stmt = $this->pdo->prepare("INSERT INTO guidelines (title, content) VALUES (?, ?)");
        $stmt->execute([$title, $content]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $title, $content) {
        $stmt = $this->pdo->prepare("UPDATE guidelines SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM guidelines WHERE id = ?");
        $stmt->execute([$id]);
    }
}
