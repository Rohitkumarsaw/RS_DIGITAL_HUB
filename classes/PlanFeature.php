<?php
class PlanFeature {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getByPlan($planName) {
        $stmt = $this->pdo->prepare("SELECT * FROM plan_features WHERE plan_name = ? ORDER BY id");
        $stmt->execute([$planName]);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM plan_features ORDER BY plan_name, id");
        return $stmt->fetchAll();
    }

    public function getPlansGrouped() {
        $features = $this->getAll();
        $grouped = [];
        foreach ($features as $f) {
            $grouped[$f['plan_name']][] = $f;
        }
        return $grouped;
    }

    public function updateFeature($id, $isEnabled, $featureValue = null) {
        if ($featureValue !== null) {
            $stmt = $this->pdo->prepare("UPDATE plan_features SET is_enabled = ?, feature_value = ? WHERE id = ?");
            $stmt->execute([$isEnabled, $featureValue, $id]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE plan_features SET is_enabled = ? WHERE id = ?");
            $stmt->execute([$isEnabled, $id]);
        }
    }

    public function isFeatureEnabled($planName, $featureName) {
        $stmt = $this->pdo->prepare("SELECT is_enabled FROM plan_features WHERE plan_name = ? AND feature_name = ?");
        $stmt->execute([$planName, $featureName]);
        $row = $stmt->fetch();
        return $row ? (bool)$row['is_enabled'] : false;
    }

    public function getFeatureLabel($featureName) {
        $labels = [
            'projects_limit' => 'Projects Limit',
            'downloads_limit' => 'Downloads/Month Limit',
            'basic_support' => 'Basic Support',
            'analytics' => 'Analytics',
            'api_access' => 'API Access',
            'white_label' => 'White Label',
            'priority_support' => 'Priority Support',
        ];
        return $labels[$featureName] ?? ucfirst(str_replace('_', ' ', $featureName));
    }

    public function getFeatureValue($planName, $featureName) {
        $stmt = $this->pdo->prepare("SELECT feature_value FROM plan_features WHERE plan_name = ? AND feature_name = ?");
        $stmt->execute([$planName, $featureName]);
        $row = $stmt->fetch();
        if ($row && $row['feature_value'] !== null) {
            return $row['feature_value'];
        }
        $labels = [
            'starter_projects_limit' => '1 Project',
            'starter_downloads_limit' => '50 Downloads/Month',
            'business_projects_limit' => '5 Projects',
            'business_downloads_limit' => '500 Downloads/Month',
            'professional_projects_limit' => 'Unlimited',
            'professional_downloads_limit' => 'Unlimited',
        ];
        $key = $planName . '_' . $featureName;
        return $labels[$key] ?? ($this->isFeatureEnabled($planName, $featureName) ? 'Yes' : 'No');
    }
}
