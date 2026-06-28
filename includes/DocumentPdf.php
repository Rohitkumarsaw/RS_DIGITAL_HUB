<?php
require_once __DIR__ . '/functions.php';

class DocumentPdf {
    protected $pdf;
    protected $settings;
    protected $lm = 20;
    protected $rm = 20;
    protected $pw = 210;
    protected $usable;
    protected $adminSigStartY = 0;

    public function __construct() {
        $this->settings = [
            'site_name' => getSetting('site_name', 'RS Digital Hub'),
            'currency' => getSetting('currency_symbol', '₹'),
            'signature_name' => getSetting('admin_signature_text', 'Authorized Signatory'),
            'company_address' => getSetting('company_address', ''),
            'company_phone' => getSetting('company_phone', ''),
            'company_email' => getSetting('company_email', ''),
            'footer_text' => getSetting('invoice_footer_text', 'Thank you for your business!'),
        ];
        $this->usable = $this->pw - $this->lm - $this->rm;
    }

    protected function init($title) {
        defined('K_TCPDF_EXTERNAL_CONFIG') || define('K_TCPDF_EXTERNAL_CONFIG', true);
        require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->pdf->SetCreator($this->settings['site_name']);
        $this->pdf->SetAuthor($this->settings['site_name']);
        $this->pdf->SetTitle($title);
        $this->pdf->SetMargins($this->lm, 0, $this->rm);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->AddPage();
    }

    public function generateAgreement($developer, $templateContent, $planName = '', $joinDate = '') {
        $this->init('Agreement - ' . $developer['name']);
        $this->drawHeader($developer['name'], 'Developer Agreement');
        $this->drawDocumentBody($templateContent);
        $this->drawAgreementSignature($developer, $joinDate);
        $this->drawFooter();
        return $this->pdf;
    }

    public function generateJoiningLetter($developer, $templateContent, $planName = '', $joinDate = '') {
        $this->init('Joining Letter - ' . $developer['name']);
        $this->drawHeader($developer['name'], 'Joining Letter');
        $this->drawDocumentBody($templateContent);
        $this->drawJoiningLetterSignature($developer, $joinDate);
        $this->drawFooter();
        return $this->pdf;
    }

    protected function drawHeader($name, $subtitle) {
        $pdf = $this->pdf;
        $pdf->SetFillColor(15, 23, 42);
        $pdf->Rect(0, 0, $this->pw, 46, 'F');
        $pdf->SetAlpha(0.07);
        $pdf->SetFillColor(99, 102, 241);
        $pdf->Circle(190, -15, 100, '', 'F');
        $pdf->SetFillColor(16, 185, 129);
        $pdf->Circle(-10, 115, 60, '', 'F');
        $pdf->SetAlpha(1);

        $pdf->SetTextColor(129, 140, 248);
        $pdf->SetFont('freesans', 'B', 17);
        $pdf->SetXY($this->lm, 9);
        $pdf->Cell(120, 9, $this->settings['site_name'], 0, 1, 'L');

        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetFont('freesans', '', 8);
        $pdf->SetX($this->lm);
        $pdf->Cell(120, 5, $subtitle, 0, 1, 'L');

        $infoY = 28;
        $parts = [];
        if ($this->settings['company_address']) $parts[] = $this->settings['company_address'];
        if ($this->settings['company_phone']) $parts[] = $this->settings['company_phone'];
        if (!empty($parts)) {
            $pdf->SetFont('freesans', '', 7);
            $pdf->SetTextColor(148, 163, 184);
            $pdf->SetXY($this->lm, $infoY);
            $pdf->Cell(120, 3, implode(' | ', $parts), 0, 1, 'L');
        }
        if ($this->settings['company_email']) {
            $pdf->SetFont('freesans', '', 7);
            $pdf->SetTextColor(148, 163, 184);
            $pdf->SetXY($this->lm, $infoY + 4);
            $pdf->Cell(120, 3, 'Email: ' . $this->settings['company_email'], 0, 1, 'L');
        }

        $rX = $this->pw - $this->rm - 80;
        $pdf->SetTextColor(129, 140, 248);
        $pdf->SetFont('freesans', 'B', 22);
        $pdf->SetXY($rX, 9);
        $docType = (strpos($subtitle, 'Agreement') !== false) ? 'AGREEMENT' : 'JOINING LETTER';
        $pdf->Cell(80, 9, $docType, 0, 1, 'R');

        $pdf->SetTextColor(203, 213, 225);
        $pdf->SetFont('freesans', '', 8);
        $pdf->SetXY($rX, 20);
        $pdf->Cell(80, 5, $name, 0, 1, 'R');
    }

    protected function drawDocumentBody($content) {
        $pdf = $this->pdf;
        $y = 54;

        $pdf->SetDrawColor(99, 102, 241);
        $pdf->SetLineWidth(0.6);
        $pdf->Line($this->lm, $y, $this->lm + $this->usable, $y);
        $pdf->SetLineWidth(0.2);
        $y += 4;

        $html = '<div style="font-family:freesans;font-size:10pt;color:#1e293b;line-height:1.6">' . $content . '</div>';
        $pdf->SetY($y);
        $pdf->writeHTML($html, true, false, true, false, '');

        $this->bodyEndY = $pdf->GetY() + 18;
    }

    protected function drawAgreementSignature($developer, $joinDate) {
        $pdf = $this->pdf;
        $startY = max($this->bodyEndY ?? 175, 175);
        if ($startY > 252) {
            $pdf->AddPage();
            $startY = 25;
        }
        $startY = min($startY, 220);

        // Admin signature (exactly matching invoice style)
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', '', 8);
        $pdf->SetXY(80, $startY);
        $pdf->Cell(110, 5, 'Authorized Signature', 0, 1, 'R');

        $pdf->SetFont('times', 'I', 18);
        $pdf->SetTextColor(22, 163, 74);
        $pdf->SetXY(80, $startY + 5);
        $pdf->Cell(110, 10, $this->settings['signature_name'], 0, 1, 'R');

        $pdf->SetDrawColor(22, 163, 74);
        $pdf->SetLineWidth(0.8);
        $sn = $this->settings['signature_name'];
        $x1 = 175 - (strlen($sn) * 3.5);
        if ($x1 < 80) $x1 = 80;
        $pdf->Line($x1, $startY + 17, 192, $startY + 17);
        $pdf->SetLineWidth(0.2);
        $this->adminSigStartY = $startY;

        // Developer signature (no box — transparent, matching admin style)
        $devY = $startY + 27;
        $devBoxW = 100;

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', '', 6.5);
        $pdf->SetXY($this->lm, $devY);
        $pdf->Cell($devBoxW, 4, 'DEVELOPER SIGNATURE', 0, 1, 'L');

        $pdf->SetFont('times', 'I', 16);
        $pdf->SetTextColor(22, 163, 74);
        $pdf->SetXY($this->lm, $devY + 5);
        $pdf->Cell($devBoxW, 10, $developer['name'] ?? 'Developer', 0, 1, 'L');

        $pdf->SetDrawColor(22, 163, 74);
        $pdf->SetLineWidth(0.8);
        $dName = $developer['name'] ?? 'Developer';
        $dx1 = $this->lm;
        $dx2 = $this->lm + min($devBoxW, strlen($dName) * 3.5);
        $pdf->Line($dx1, $devY + 17, $dx2, $devY + 17);
        $pdf->SetLineWidth(0.2);

        $devLabelY = $devY + 19;
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', '', 6);
        $pdf->SetXY($this->lm, $devLabelY);
        $pdf->Cell($devBoxW, 4, $developer['name'] ?? 'Developer', 0, 1, 'L');

        // Apply stamp
        applyPdfStamp($pdf, 28, $startY - 5, 28);

        $this->bodyEndY = $devLabelY + 6;
    }

    protected function drawJoiningLetterSignature($developer, $joinDate) {
        $pdf = $this->pdf;
        $startY = max($this->bodyEndY ?? 175, 175);

        if ($startY > 260) {
            $pdf->AddPage();
            $startY = 25;
        }
        $startY = min($startY, 240);

        // Admin signature (exactly matching invoice style)
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', '', 8);
        $pdf->SetXY(80, $startY);
        $pdf->Cell(110, 5, 'Authorized Signature', 0, 1, 'R');

        $pdf->SetFont('times', 'I', 18);
        $pdf->SetTextColor(22, 163, 74);
        $pdf->SetXY(80, $startY + 5);
        $pdf->Cell(110, 10, $this->settings['signature_name'], 0, 1, 'R');

        $pdf->SetDrawColor(22, 163, 74);
        $pdf->SetLineWidth(0.8);
        $sn = $this->settings['signature_name'];
        $x1 = 175 - (strlen($sn) * 3.5);
        if ($x1 < 80) $x1 = 80;
        $pdf->Line($x1, $startY + 17, 192, $startY + 17);
        $pdf->SetLineWidth(0.2);
        $this->adminSigStartY = $startY;

        // Apply stamp
        applyPdfStamp($pdf, 28, $startY - 5, 28);

        $this->bodyEndY = $startY + 27;
    }

    protected function drawFooter() {
        $pdf = $this->pdf;
        $fy = max($this->bodyEndY ?? 180, 180) + 4;
        $fy = max($fy, 260);

        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line($this->lm, $fy, $this->lm + $this->usable, $fy);
        $fy += 4;

        $ft = $this->settings['footer_text'];
        if (!empty($ft)) {
            $pdf->SetTextColor(148, 163, 184);
            $pdf->SetFont('freesans', '', 8);
            $pdf->SetXY($this->lm, $fy);
            $pdf->Cell($this->usable, 5, $ft, 0, 1, 'C');
            $fy += 5;
        }

        $siteN = $this->settings['site_name'];
        $siteE = !empty($this->settings['company_email']) ? $this->settings['company_email'] : 'support@' . strtolower(str_replace(' ', '', $siteN)) . '.com';
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetFont('freesans', '', 7);
        $pdf->SetXY($this->lm, $fy);
        $pdf->Cell($this->usable, 4, $siteN . ' | ' . $siteE, 0, 1, 'C');
    }

    public function output($filename) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        if ($this->adminSigStartY > 0) {
            applyDigitalSignature($this->pdf, 80, $this->adminSigStartY, 110, 22);
        }
        $tmp = rtrim(sys_get_temp_dir(), '\\/');
        $filePath = $tmp . DIRECTORY_SEPARATOR . $filename;
        $this->pdf->Output($filePath, 'F');
        if (!file_exists($filePath)) {
            header('Content-Type: text/plain; charset=utf-8');
            die('PDF file was not created.');
        }
        $data = file_get_contents($filePath);
        @unlink($filePath);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        echo $data;
        exit;
    }
}
