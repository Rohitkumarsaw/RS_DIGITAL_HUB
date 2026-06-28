<?php
require_once __DIR__ . '/functions.php';

class InvoicePdf {
    private $pdf;
    private $settings;
    private $lm = 20;
    private $rm = 20;
    private $pw = 210;
    private $usable;
    private $adminSigStartY = 0;

    public function __construct() {
        $this->settings = [
            'gstin' => getSetting('admin_gst', ''),
            'site_name' => getSetting('site_name', 'RS Digital Hub'),
            'currency' => getSetting('currency_symbol', '₹'),
            'tax_type' => getSetting('tax_type', 'GST'),
            'signature_name' => getSetting('admin_signature_text', 'Authorized Signatory'),
            'company_address' => getSetting('company_address', ''),
            'company_phone' => getSetting('company_phone', ''),
            'company_email' => getSetting('company_email', ''),
            'bank_name' => getSetting('invoice_bank_name', ''),
            'bank_account' => getSetting('invoice_bank_account', ''),
            'bank_ifsc' => getSetting('invoice_bank_ifsc', ''),
            'footer_text' => getSetting('invoice_footer_text', 'Thank you for your business!'),
        ];
        $this->usable = $this->pw - $this->lm - $this->rm;
    }

    private function init($title) {
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

    public function generateOrderInvoice($order) {
        $this->init('Invoice - ' . $order['order_number']);
        $this->drawHeader($order['order_number'], 'Official Invoice');
        $this->drawBody($order, 'order');
        $this->drawSignature();
        $this->applyDigitalSig();
        $this->drawFooter();
        return $this->pdf;
    }

    public function generateSubscriptionInvoice($sub, $price, $taxP, $taxA, $total) {
        $inv = 'INV-' . strtoupper($sub['plan_name']) . '-' . str_pad($sub['id'], 5, '0', STR_PAD_LEFT);
        $this->init('Invoice - ' . $inv);
        $this->drawHeader($inv, 'Subscription Invoice');
        $sub['_price'] = $price;
        $sub['_taxP'] = $taxP;
        $sub['_taxA'] = $taxA;
        $sub['_total'] = $total;
        $sub['_item'] = ucfirst($sub['plan_name']) . ' Plan Subscription';
        $this->drawBody($sub, 'subscription');
        $this->drawSignature();
        $this->applyDigitalSig();
        $this->drawFooter();
        return $this->pdf;
    }

    private function drawHeader($ref, $subtitle) {
        $pdf = $this->pdf;
        // Dark header
        $pdf->SetFillColor(15, 23, 42);
        $pdf->Rect(0, 0, $this->pw, 46, 'F');
        // Decorative
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

        // Company info in header
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

        // Right side
        $rX = $this->pw - $this->rm - 80;
        $pdf->SetTextColor(129, 140, 248);
        $pdf->SetFont('freesans', 'B', 22);
        $pdf->SetXY($rX, 9);
        $pdf->Cell(80, 9, 'INVOICE', 0, 1, 'R');

        $pdf->SetTextColor(203, 213, 225);
        $pdf->SetFont('freesans', '', 8);
        $pdf->SetXY($rX, 20);
        $pdf->Cell(80, 5, $ref, 0, 1, 'R');
    }

    private function drawBody($data, $type) {
        $pdf = $this->pdf;
        $y = 54;
        $rX = $this->pw - $this->rm - 85;
        $rW = 85;

        // -- LEFT: Bill To --
        $pdf->SetFont('freesans', 'B', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY($this->lm, $y);
        $pdf->Cell(80, 5, 'BILL TO', 0, 1, 'L');

        $name = $data['user_name'] ?? $data['developer_name'] ?? 'Customer';
        $email = $data['user_email'] ?? $data['developer_email'] ?? '';

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('freesans', 'B', 11);
        $pdf->SetX($this->lm);
        $pdf->Cell(80, 7, $name, 0, 1, 'L');

        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('freesans', '', 8);
        $pdf->SetX($this->lm);
        $pdf->Cell(80, 5, $email, 0, 1, 'L');

        if (!empty($this->settings['gstin'])) {
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('freesans', 'B', 7);
            $pdf->SetX($this->lm);
            $pdf->Cell(80, 5, 'GSTIN: ' . $this->settings['gstin'], 0, 1, 'L');
        }

        // -- RIGHT: Invoice Details --
        $createdAt = $data['created_at'] ?? $data['purchase_date'] ?? date('Y-m-d H:i:s');
        $st = $data['payment_status'] ?? $data['status'] ?? '';
        $statusLabel = ($st === 'completed' || $st === 'active') ? 'Completed' : ($st === 'pending' ? 'Pending' : 'Failed');
        $orderRef = $data['order_number'] ?? $data['_item'] ?? '';

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', 'B', 8);
        $pdf->SetXY($rX, 54);
        $pdf->Cell($rW, 5, 'INVOICE DETAILS', 0, 1, 'R');

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('freesans', '', 8);
        $details = [
            ['Date:', date('M d, Y', strtotime($createdAt))],
            ['Order:', $orderRef],
            ['Status:', $statusLabel],
        ];
        $dy = 61;
        foreach ($details as $d) {
            $pdf->SetXY($rX, $dy);
            $pdf->Cell($rW, 5, $d[0] . ' ' . $d[1], 0, 1, 'R');
            $dy += 5;
        }

        // -- TABLE --
        $ty = $dy + 6;
        $tW = $this->usable;
        $c1 = $tW * 0.55;
        $c2 = $tW * 0.20;
        $c3 = $tW * 0.25;

        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', 'B', 7);
        $pdf->SetXY($this->lm, $ty);
        $pdf->Cell($c1, 9, '  DESCRIPTION', 0, 0, 'L', true);
        $pdf->Cell($c2, 9, 'QTY', 0, 0, 'C', true);
        $pdf->Cell($c3, 9, 'AMOUNT', 0, 1, 'R', true);

        $y2 = $ty + 9;
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line($this->lm, $y2, $this->lm + $tW, $y2);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetFont('freesans', '', 9);

        if ($type === 'order') {
            $items = $data['items'] ?? [];
            if (empty($items) && isset($data['id'])) {
                $stmt = $GLOBALS['pdo']->prepare("SELECT oi.*, p.title FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $stmt->execute([$data['id']]);
                $items = $stmt->fetchAll();
            }
            foreach ($items as $item) {
                $pdf->SetXY($this->lm, $y2);
                $pdf->Cell($c1, 8, '  ' . ($item['title'] ?? 'Product'), 0, 0, 'L');
                $pdf->Cell($c2, 8, '1', 0, 0, 'C');
                $pdf->Cell($c3, 8, $this->settings['currency'] . number_format($item['price'] ?? 0, 2), 0, 1, 'R');
                $y2 += 8;
            }
            $subtotal = $data['total_amount'] ?? 0;
            $taxA = $data['tax_amount'] ?? 0;
            $taxP = $data['tax_percentage'] ?? (($subtotal > 0 && $taxA > 0) ? round(($taxA / $subtotal) * 100, 2) : 0);
            $total = $data['final_amount'] ?? $subtotal;
            $discount = $data['discount_amount'] ?? 0;
        } else {
            $pdf->SetXY($this->lm, $y2);
            $pdf->Cell($c1, 8, '  ' . $data['_item'], 0, 0, 'L');
            $pdf->Cell($c2, 8, '1', 0, 0, 'C');
            $pdf->Cell($c3, 8, $this->settings['currency'] . number_format($data['_price'], 2), 0, 1, 'R');
            $y2 += 8;
            $subtotal = $data['_price'];
            $taxA = $data['_taxA'];
            $taxP = $data['_taxP'];
            $total = $data['_total'];
            $discount = 0;
        }

        // Bottom line
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line($this->lm, $y2, $this->lm + $tW, $y2);
        $y2 += 6;

        // -- TOTALS BOX (right-aligned) --
        $boxW = 90;
        $boxRows = 2;
        if ($discount > 0) $boxRows++;
        if ($taxP > 0) $boxRows++;
        $boxH = $boxRows * 7 + 4;
        $sx = $this->lm + $tW - $boxW;

        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect($sx, $y2, $boxW, $boxH, 'F');
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Rect($sx, $y2, $boxW, $boxH, 'D');

        $ix = $sx + 6;
        $iy = $y2 + 3;

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('freesans', '', 8);
        $pdf->SetXY($ix, $iy);
        $pdf->Cell($boxW - 12, 6, 'Subtotal', 0, 0, 'L');
        $pdf->Cell(0, 6, $this->settings['currency'] . number_format($subtotal, 2), 0, 1, 'R');

        if ($discount > 0) {
            $iy += 7;
            $pdf->SetTextColor(22, 163, 74);
            $pdf->SetXY($ix, $iy);
            $pdf->Cell($boxW - 12, 6, 'Discount', 0, 0, 'L');
            $pdf->Cell(0, 6, '-' . $this->settings['currency'] . number_format($discount, 2), 0, 1, 'R');
        }
        if ($taxP > 0) {
            $iy += 7;
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetXY($ix, $iy);
            $pdf->Cell($boxW - 12, 6, $this->settings['tax_type'] . ' (' . $taxP . '%)', 0, 0, 'L');
            $pdf->Cell(0, 6, $this->settings['currency'] . number_format($taxA, 2), 0, 1, 'R');
        }
        $iy += 7;
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line($sx + 4, $iy - 2, $sx + $boxW - 4, $iy - 2);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('freesans', 'B', 10);
        $pdf->SetXY($ix, $iy);
        $pdf->Cell($boxW - 12, 7, 'Total Paid', 0, 0, 'L');
        $pdf->SetTextColor(79, 70, 229);
        $pdf->Cell(0, 7, $this->settings['currency'] . number_format($total, 2), 0, 1, 'R');

        // Store end Y for signature placement
        $this->bodyEndY = $iy + 7 + 4;
    }

    private function drawSignature() {
        $pdf = $this->pdf;
        $sigName = $this->settings['signature_name'];
        $startY = max($this->bodyEndY ?? 180, 180);

        // Admin signature (handwriting style)
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

        // Apply paid stamp (invoice only)
        applyPdfPaidStamp($pdf, 155, $startY - 30, 45);
    }

    private function applyDigitalSig() {
        if ($this->adminSigStartY > 0) {
            applyDigitalSignature($this->pdf, 80, $this->adminSigStartY, 110, 22);
        }
    }

    private function drawFooter() {
        $pdf = $this->pdf;
        $sigName = $this->settings['signature_name'];
        $startY = max($this->bodyEndY ?? 180, 180);
        $fy = $startY + 28;

        // Bank details
        $bank = array_filter([
            $this->settings['bank_name'] ? 'Bank: ' . $this->settings['bank_name'] : '',
            $this->settings['bank_account'] ? 'A/c: ' . $this->settings['bank_account'] : '',
            $this->settings['bank_ifsc'] ? 'IFSC: ' . $this->settings['bank_ifsc'] : '',
        ]);
        if (!empty($bank)) {
            $pdf->SetTextColor(71, 85, 105);
            $pdf->SetFont('freesans', '', 7);
            $pdf->SetXY($this->lm, $fy);
            $pdf->Cell($this->usable, 4, implode('  |  ', $bank), 0, 1, 'L');
            $fy += 6;
        }

        // Footer line
        $fy = max($fy, 265);
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
}
