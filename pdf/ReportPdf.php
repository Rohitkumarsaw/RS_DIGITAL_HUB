<?php
require_once __DIR__ . '/../includes/functions.php';

class ReportPdf {
    protected $pdf;
    protected $settings;
    protected $lm = 15;
    protected $rm = 15;
    protected $tm = 10;
    protected $pw = 210;
    protected $usable;
    protected $pageNum = 1;
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
        $this->pdf->SetMargins($this->lm, $this->tm, $this->rm);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->AddPage();
    }

    public function generateReport($title, $subtitle, $headings, $rows, $summaryData = [], $extraHtml = '', $colWidths = []) {
        $this->init($title);
        $this->drawHeader($title, $subtitle);
        $this->drawReportInfo();
        $this->drawTable($headings, $rows, $colWidths);
        if (!empty($summaryData)) {
            $this->drawSummary($summaryData);
        }
        if (!empty($extraHtml)) {
            $this->drawExtra($extraHtml);
        }
        $this->drawSignature();
        $this->drawFooter();
        return $this->pdf;
    }

    public function generateRegistrationSlip($user) {
        $this->init('Registration Slip - ' . $user['name']);
        $this->drawHeader('Registration Slip', 'Member Registration Certificate');
        $this->drawReportInfo();
        $this->drawRegistrationDetails($user);
        $this->drawSignature();
        $this->drawFooter();
        return $this->pdf;
    }

    protected function drawHeader($title, $subtitle) {
        $pdf = $this->pdf;
        $pw = $this->pw;
        $lm = $this->lm;

        // Dark gradient-style header
        $pdf->SetFillColor(15, 23, 42);
        $pdf->Rect(0, 0, $pw, 48, 'F');

        // Decorative circles
        $pdf->SetAlpha(0.06);
        $pdf->SetFillColor(99, 102, 241);
        $pdf->Circle($pw + 10, -20, 120, '', 'F');
        $pdf->SetFillColor(16, 185, 129);
        $pdf->Circle(-15, 120, 70, '', 'F');
        $pdf->SetAlpha(1);

        // Bottom accent line
        $pdf->SetFillColor(99, 102, 241);
        $pdf->Rect(0, 46, $pw, 2, 'F');

        // Left: Site name + subtitle
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('freesans', 'B', 15);
        $pdf->SetXY($lm, 7);
        $pdf->Cell(120, 8, $this->settings['site_name'], 0, 1, 'L');

        $pdf->SetTextColor(165, 180, 252);
        $pdf->SetFont('freesans', '', 7.5);
        $pdf->SetX($lm);
        $pdf->Cell(120, 4, $subtitle, 0, 1, 'L');

        // Company info
        $infoY = 26;
        $parts = array_filter([$this->settings['company_address'], $this->settings['company_phone']]);
        if (!empty($parts)) {
            $pdf->SetTextColor(148, 163, 184);
            $pdf->SetFont('freesans', '', 6.5);
            $pdf->SetXY($lm, $infoY);
            $pdf->Cell(130, 3, implode(' | ', $parts), 0, 1, 'L');
        }
        if ($this->settings['company_email']) {
            $pdf->SetTextColor(148, 163, 184);
            $pdf->SetFont('freesans', '', 6.5);
            $pdf->SetXY($lm, $infoY + 4);
            $pdf->Cell(130, 3, $this->settings['company_email'], 0, 1, 'L');
        }

        // Right: Title + Report ID
        $rX = $pw - $this->rm - 80;
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('freesans', 'B', 18);
        $pdf->SetXY($rX, 7);
        $pdf->Cell(80, 8, strtoupper($title), 0, 1, 'R');

        $pdf->SetTextColor(165, 180, 252);
        $pdf->SetFont('freesans', '', 6.5);
        $pdf->SetXY($rX, 18);
        $pdf->Cell(80, 4, 'ID: ' . strtoupper(substr(md5(uniqid()), 0, 12)), 0, 1, 'R');

        // Generated date on right
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetFont('freesans', '', 6.5);
        $pdf->SetXY($rX, 24);
        $pdf->Cell(80, 4, date('M d, Y h:i A'), 0, 1, 'R');

        $this->headerEndY = 54;
    }

    protected function drawReportInfo() {
        $pdf = $this->pdf;
        $this->bodyStartY = $this->headerEndY + 4;
    }

    protected function drawTable($headings, $rows, $colWidths = []) {
        $pdf = $this->pdf;
        $y = $this->bodyStartY;
        $tW = $this->usable;
        $lm = $this->lm;
        $colCount = count($headings);
        $colW = empty($colWidths) ? $tW / $colCount : 0;
        if (empty($colWidths)) $colWidths = array_fill(0, $colCount, $colW);

        // --- Premium header row ---
        $pdf->SetFillColor(79, 70, 229);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('freesans', 'B', 7.5);
        $hY = $y;
        $hx = $lm;
        foreach ($headings as $i => $h) {
            $cw = $colWidths[$i];
            $align = ($i === $colCount - 1) ? 'R' : 'L';
            $pad = $i === 0 ? 4 : ($i === $colCount - 1 ? 0 : 2);
            $pdf->SetXY($hx, $hY);
            $pdf->Cell($cw, 9, str_repeat(' ', $pad) . strtoupper($h), 0, 0, $align, true);
            $hx += $cw;
        }
        // Bottom accent line under header
        $pdf->SetFillColor(99, 102, 241);
        $pdf->Rect($lm, $hY + 9, $tW, 1.5, 'F');
        $y = $hY + 11;

        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetFont('freesans', '', 7.5);
        $rowH = 8;
        $fill = false;

        foreach ($rows as $row) {
            // Page break check
            if ($y + $rowH > 260) {
                $this->drawPageFooter();
                $pdf->AddPage();
                $this->pageNum++;
                $y = 15;
                // Re-draw header on new page
                $pdf->SetFillColor(79, 70, 229);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('freesans', 'B', 7.5);
                $hx = $lm;
                foreach ($headings as $i => $h) {
                    $cw = $colWidths[$i];
                    $align = ($i === $colCount - 1) ? 'R' : 'L';
                    $pad = $i === 0 ? 4 : ($i === $colCount - 1 ? 0 : 2);
                    $pdf->SetXY($hx, $y);
                    $pdf->Cell($cw, 9, str_repeat(' ', $pad) . strtoupper($h), 0, 0, $align, true);
                    $hx += $cw;
                }
                $pdf->SetFillColor(99, 102, 241);
                $pdf->Rect($lm, $y + 9, $tW, 1.5, 'F');
                $y += 11;
                $pdf->SetTextColor(51, 65, 85);
                $pdf->SetFont('freesans', '', 7.5);
            }

            // Row background
            $bgColor = $fill ? [248, 250, 252] : [255, 255, 255];
            $pdf->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);

            // Subtle left accent on alternating rows
            if ($fill) {
                $pdf->SetFillColor(99, 102, 241);
                $pdf->Rect($lm, $y, 1.5, $rowH, 'F');
                $pdf->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
            }

            $xStart = $lm;
            foreach ($row as $i => $cell) {
                $cw = $colWidths[$i];
                $align = ($i === $colCount - 1) ? 'R' : 'L';
                $pdf->SetXY($xStart, $y);
                $dispCell = $i === 0 ? ' ' . $cell : ($i === $colCount - 1 ? $cell . ' ' : ' ' . $cell);
                $pdf->Cell($cw, $rowH, $dispCell, 0, 0, $align, true);
                $xStart += $cw;
            }
            $y += $rowH;
            $fill = !$fill;
        }

        // Bottom border
        $pdf->SetDrawColor(199, 210, 254);
        $pdf->SetLineWidth(0.5);
        $pdf->Line($lm, $y, $lm + $tW, $y);
        $pdf->SetLineWidth(0.2);
        $y += 5;
        $this->bodyEndY = $y;
    }

    protected function drawSummary($data) {
        $pdf = $this->pdf;
        $y = $this->bodyEndY + 4;
        $boxW = 95;
        $sx = $this->lm + $this->usable - $boxW;
        $rows = count($data);
        $boxH = $rows * 9 + 10;
        $lm = $this->lm;

        // Premium summary box with accent top bar
        $pdf->SetFillColor(238, 242, 255);
        $pdf->RoundedRect($sx, $y, $boxW, $boxH, 3, '1111', 'F');
        $pdf->SetDrawColor(199, 210, 254);
        $pdf->RoundedRect($sx, $y, $boxW, $boxH, 3, '1111', 'D');
        // Top accent stripe
        $pdf->SetFillColor(79, 70, 229);
        $pdf->Rect($sx, $y, $boxW, 3, 'F');

        // Title
        $pdf->SetTextColor(79, 70, 229);
        $pdf->SetFont('freesans', 'B', 8);
        $pdf->SetXY($sx + 8, $y + 5);
        $pdf->Cell($boxW - 16, 5, 'SUMMARY', 0, 1, 'L');

        $ix = $sx + 8;
        $iy = $y + 11;
        $i = 0;
        foreach ($data as $label => $value) {
            $pdf->SetTextColor(71, 85, 105);
            $pdf->SetFont('freesans', '', 7.5);
            $pdf->SetXY($ix, $iy);
            $pdf->Cell($boxW - 16, 7, $label, 0, 0, 'L');
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('freesans', 'B', 9);
            $pdf->Cell(0, 7, $value, 0, 1, 'R');
            $iy += 9;
            $i++;
            // Separator line between items
            if ($i < $rows) {
                $pdf->SetDrawColor(226, 232, 240);
                $pdf->SetLineWidth(0.2);
                $pdf->Line($sx + 6, $iy - 1, $sx + $boxW - 6, $iy - 1);
            }
        }

        $this->bodyEndY = $iy + 4;
    }

    protected function drawExtra($html) {
        $pdf = $this->pdf;
        $y = $this->bodyEndY + 4;
        $pdf->SetY($y);
        $pdf->writeHTML($html, true, false, true, false, '');
        $this->bodyEndY = $pdf->GetY();
    }

    protected function drawRegistrationDetails($user) {
        $pdf = $this->pdf;
        $y = $this->bodyStartY;
        $lm = $this->lm;
        $usable = $this->usable;

        // --- Decorative accent bar ---
        $pdf->SetFillColor(99, 102, 241);
        $pdf->Rect($lm, $y, $usable, 3, 'F');
        $y += 7;

        // --- Certificate-style header ---
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('freesans', 'B', 13);
        $pdf->SetXY($lm, $y);
        $pdf->Cell($usable, 7, 'MEMBERSHIP CERTIFICATE', 0, 1, 'C');
        $y += 7;

        $pdf->SetDrawColor(99, 102, 241);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($lm + 30, $y, $lm + $usable - 30, $y);
        $y += 6;

        // --- Registration badge ---
        $badgeW = 70;
        $badgeH = 12;
        $badgeX = $lm + ($usable - $badgeW) / 2;
        $pdf->SetFillColor(99, 102, 241);
        $pdf->RoundedRect($badgeX, $y, $badgeW, $badgeH, 3, '1111', 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('freesans', 'B', 9);
        $pdf->SetXY($badgeX, $y + 2);
        $pdf->Cell($badgeW, 8, 'REG #: ' . ($user['registration_no'] ?? 'N/A'), 0, 1, 'C');
        $y += $badgeH + 8;

        // --- Two-column premium info grid ---
        $fields = [
            ['Full Name', $user['name'] ?? '', 'user'],
            ['Account Type', ucfirst($user['role'] ?? 'User'), 'shield'],
            ['Father Name', $user['father_name'] ?? 'N/A', 'user'],
            ['Date of Birth', $user['dob'] ? date('M d, Y', strtotime($user['dob'])) : 'N/A', 'calendar'],
            ['Mobile', $user['mobile'] ?? 'N/A', 'phone'],
            ['Gender', ucfirst($user['gender'] ?? 'N/A'), 'user'],
            ['Email', $user['email'] ?? '', 'mail'],
            ['Nationality', $user['nationality'] ?? 'N/A', 'globe'],
            ['Address', $user['address'] ?? 'N/A', 'map-pin'],
            ['Occupation', $user['occupation'] ?? 'N/A', 'briefcase'],
            ['ID Proof', $user['id_proof_file'] ?? 'N/A', 'file'],
        ];

        $colW = ($usable - 6) / 2;
        $rowH = 14;
        $gap = 3;

        $pdf->SetDrawColor(226, 232, 240);

        foreach ($fields as $idx => $f) {
            if ($y + $rowH > 260) {
                $pdf->AddPage();
                $this->pageNum++;
                $y = 15;
            }

            $col = $idx % 2;
            $x = $lm + ($col * ($colW + $gap));

            // Card background
            $pdf->SetFillColor(249, 250, 251);
            $pdf->RoundedRect($x, $y, $colW, $rowH, 2, '1111', 'F');
            $pdf->Rect($x, $y, $colW, $rowH, 'D');

            // Left accent stripe
            $pdf->SetFillColor(99, 102, 241);
            $pdf->Rect($x, $y, 2.5, $rowH, 'F');

            // Label
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('freesans', '', 6.5);
            $pdf->SetXY($x + 5, $y + 1.5);
            $pdf->Cell($colW - 7, 4, strtoupper($f[0]), 0, 1, 'L');

            // Value
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('freesans', 'B', 8.5);
            $pdf->SetXY($x + 5, $y + 5.5);
            $pdf->Cell($colW - 7, 6, $f[1], 0, 1, 'L');

            // Move to next row when both columns are done
            if ($col === 1) {
                $y += $rowH + $gap;
            }
        }
        // Ensure we move past the last row if odd number of fields
        $y += $rowH + $gap + 2;

        // --- Dates section ---
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($lm, $y, $lm + $usable, $y);
        $y += 5;

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', '', 7);
        $regDate = $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';
        $compDate = $user['profile_completed_at'] ? date('M d, Y', strtotime($user['profile_completed_at'])) : 'N/A';
        $pdf->SetXY($lm, $y);
        $pdf->Cell($usable / 2, 4, 'Registered: ' . $regDate, 0, 0, 'L');
        $pdf->Cell($usable / 2, 4, 'Profile Completed: ' . $compDate, 0, 1, 'L');
        $y += 6;

        $this->bodyEndY = $y;
    }

    protected function drawSignature() {
        $pdf = $this->pdf;
        $sigName = $this->settings['signature_name'];
        $startY = max($this->bodyEndY ?? 180, 180);

        if ($startY > 255) {
            $pdf->AddPage();
            $this->pageNum++;
            $startY = 15;
        }

        // Admin signature (exactly matching invoice style)
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', '', 8);
        $pdf->SetXY(80, $startY);
        $pdf->Cell(110, 5, 'Authorized Signature', 0, 1, 'R');

        $pdf->SetFont('times', 'I', 18);
        $pdf->SetTextColor(22, 163, 74);
        $pdf->SetXY(80, $startY + 5);
        $pdf->Cell(110, 10, $sigName, 0, 1, 'R');

        $pdf->SetDrawColor(22, 163, 74);
        $pdf->SetLineWidth(0.8);
        $x1 = 175 - (strlen($sigName) * 3.5);
        if ($x1 < 80) $x1 = 80;
        $pdf->Line($x1, $startY + 17, 192, $startY + 17);
        $pdf->SetLineWidth(0.2);
        $this->adminSigStartY = $startY;

        // Apply company stamp
        applyPdfStamp($pdf, 28, $startY - 5, 28);

        $this->bodyEndY = $startY + 27;
    }

    protected function drawFooter() {
        $pdf = $this->pdf;
        $lm = $this->lm;
        $usable = $this->usable;
        $fy = max($this->bodyEndY ?? 180, 180) + 4;

        if ($fy > 262) {
            $pdf->AddPage();
            $this->pageNum++;
            $fy = 260;
        }
        $fy = max($fy, 258);

        // Accent line
        $pdf->SetDrawColor(199, 210, 254);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($lm, $fy, $lm + $usable, $fy);
        $pdf->SetLineWidth(0.2);
        $fy += 5;

        $ft = $this->settings['footer_text'];
        if (!empty($ft)) {
            $pdf->SetTextColor(148, 163, 184);
            $pdf->SetFont('freesans', '', 6.5);
            $pdf->SetXY($lm, $fy);
            $pdf->Cell($usable, 4, $ft, 0, 1, 'C');
            $fy += 4;
        }

        $siteN = $this->settings['site_name'];
        $siteE = !empty($this->settings['company_email']) ? $this->settings['company_email'] : 'support@' . strtolower(str_replace(' ', '', $siteN)) . '.com';
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetFont('freesans', '', 6.5);
        $pdf->SetXY($lm, $fy);
        $pdf->Cell($usable, 4, $siteN . ' | ' . $siteE, 0, 1, 'C');
        $fy += 4;

        $pdf->SetTextColor(165, 180, 252);
        $pdf->SetFont('freesans', 'B', 6.5);
        $pdf->SetXY($lm, $fy);
        $pdf->Cell($usable, 4, 'Page ' . $this->pageNum, 0, 1, 'C');
    }

    protected function drawPageFooter() {
        $pdf = $this->pdf;
        $lm = $this->lm;
        $usable = $this->usable;
        $fy = 285;

        $pdf->SetDrawColor(199, 210, 254);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($lm, $fy, $lm + $usable, $fy);
        $pdf->SetLineWidth(0.2);
        $pdf->SetTextColor(165, 180, 252);
        $pdf->SetFont('freesans', 'B', 6);
        $pdf->SetXY($lm, $fy + 2);
        $pdf->Cell($usable, 4, 'Page ' . $this->pageNum, 0, 1, 'C');
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
