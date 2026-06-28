<?php
class Subscription {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT s.*, u.name as developer_name, u.email as developer_email FROM subscriptions s JOIN users u ON s.developer_id = u.id WHERE s.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByDeveloper($developerId) {
        $stmt = $this->pdo->prepare("SELECT * FROM subscriptions WHERE developer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$developerId]);
        return $stmt->fetchAll();
    }

    public function getActiveByDeveloper($developerId) {
        $stmt = $this->pdo->prepare("SELECT * FROM subscriptions WHERE developer_id = ? AND status = 'active' AND expiry_date > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$developerId]);
        return $stmt->fetch();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT s.*, u.name as developer_name, u.email as developer_email FROM subscriptions s JOIN users u ON s.developer_id = u.id ORDER BY s.created_at DESC");
        return $stmt->fetchAll();
    }

    public function create($developerId, $planName, $expiryDate) {
        $stmt = $this->pdo->prepare("INSERT INTO subscriptions (developer_id, plan_name, purchase_date, expiry_date, status) VALUES (?, ?, NOW(), ?, 'pending')");
        $stmt->execute([$developerId, $planName, $expiryDate]);
        return $this->pdo->lastInsertId();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE subscriptions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public function changePlan($id, $newPlan, $expiryDate) {
        $stmt = $this->pdo->prepare("UPDATE subscriptions SET plan_name = ?, purchase_date = NOW(), expiry_date = ?, status = 'active' WHERE id = ?");
        $stmt->execute([$newPlan, $expiryDate, $id]);
    }

    public function getDaysRemaining($expiryDate) {
        $now = new DateTime();
        $expiry = new DateTime($expiryDate);
        $diff = $now->diff($expiry);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    public function getPlanPrice($planName) {
        $prices = getPlanPrices();
        return $prices[$planName] ?? 0;
    }

    public function getPlanDuration($planName) {
        return getPlanDuration($planName);
    }

    public function isDeveloperRestricted($developerId) {
        $active = $this->getActiveByDeveloper($developerId);
        if ($active) return false;
        return true;
    }

    public function getPendingByDeveloper($developerId) {
        $stmt = $this->pdo->prepare("SELECT * FROM subscriptions WHERE developer_id = ? AND status = 'pending' ORDER BY created_at DESC");
        $stmt->execute([$developerId]);
        return $stmt->fetchAll();
    }

    public function deleteById($id) {
        $stmt = $this->pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
        $stmt->execute([$id]);
    }
}
