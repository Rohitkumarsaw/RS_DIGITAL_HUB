<?php
if (!isset($invoiceData)) {
    $invoiceData = [];
}
$data = $invoiceData;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo sanitize($data['invoiceNumber'] ?? ''); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800;900&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@600;700&family=Mea+Culpa&family=Great+Vibes&display=swap" rel="stylesheet">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; color: #1e293b; background: #e8e6e1; padding: 28px; }

        .premium-wrap { max-width: 850px; margin: 0 auto; background: #fffbf6; border-radius: 18px; box-shadow: 0 12px 48px rgba(0,0,0,0.10); overflow: hidden; position: relative; }

        .p-top-bar { background: linear-gradient(135deg, #0c0f15 0%, #1a1f2e 50%, #2a2f3e 100%); padding: 24px 50px; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden; }
        .p-top-bar::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, #d4a853, #f0c860, #d4a853, transparent); }
        .p-top-bar .p-brand { display: flex; align-items: center; gap: 12px; }
        .p-top-bar .p-brand-icon { width: 42px; height: 42px; background: linear-gradient(135deg, #d4a853, #f0c860); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 800; color: #0c0f15; }
        .p-top-bar .p-brand-name { color: #e8e6e1; font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
        .p-top-bar .p-brand-name span { color: #d4a853; }
        .p-top-bar .p-meta { text-align: right; }
        .p-top-bar .p-meta-label { color: #8a8a8a; font-size: 10px; text-transform: uppercase; letter-spacing: 2.5px; font-weight: 500; }
        .p-top-bar .p-meta-num { color: #f0e6d3; font-size: 24px; font-weight: 800; letter-spacing: -0.3px; margin-top: 2px; }

        .p-body { padding: 44px 50px 36px; }

        .p-title-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #e8e0d0; }
        .p-title-row h1 { font-family: 'Playfair Display', Georgia, serif; font-size: 44px; font-weight: 800; color: #0c0f15; letter-spacing: -1px; line-height: 1; }
        .p-title-row .p-title-accent { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 14px; font-weight: 400; font-style: italic; color: #8a7a5a; letter-spacing: 0.5px; margin-top: 4px; }
        .p-title-row .p-status { display: inline-block; padding: 6px 20px; border-radius: 100px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: linear-gradient(135deg, #d4a853, #f0c860); color: #0c0f15; border: none; }

        .p-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 36px; }
        .p-card { background: #faf6ee; border-radius: 12px; padding: 18px 20px; border: 1px solid #e8e0d0; position: relative; }
        .p-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #d4a853, transparent); border-radius: 12px 12px 0 0; }
        .p-card h3 { font-size: 9px; text-transform: uppercase; letter-spacing: 2px; color: #8a7a5a; font-weight: 600; margin-bottom: 10px; }
        .p-card p { font-size: 13px; color: #1e293b; line-height: 1.7; }
        .p-card .name { font-weight: 700; font-size: 15px; color: #0c0f15; display: block; margin-bottom: 3px; }
        .p-card .gstin { display: inline-block; margin-top: 4px; padding: 2px 10px; background: #e8e0d0; border-radius: 4px; font-size: 11px; font-weight: 600; color: #5a4a3a; letter-spacing: 0.3px; }

        .p-divider { height: 1px; background: linear-gradient(to right, transparent, #e8e0d0, #e8e0d0, #e8e0d0, transparent); margin: 0 0 28px 0; }

        .p-table-wrap { margin-bottom: 24px; overflow-x: auto; }
        .p-table { width: 100%; border-collapse: separate; border-spacing: 0; border-radius: 12px; overflow: hidden; }
        .p-table thead th { background: #0c0f15; color: #f0e6d3; padding: 14px 20px; font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 600; text-align: left; }
        .p-table thead th:last-child { text-align: right; }
        .p-table tbody td { padding: 15px 20px; border-bottom: 1px solid #e8e0d0; font-size: 14px; color: #334155; }
        .p-table tbody td:last-child { text-align: right; font-weight: 600; color: #0c0f15; font-size: 15px; }
        .p-table tbody tr:last-child td { border-bottom: none; }

        .p-totals { display: flex; justify-content: flex-end; }
        .p-totals table { width: 280px; border-collapse: collapse; }
        .p-totals td { padding: 7px 0; font-size: 13px; }
        .p-totals .pt-label { color: #8a7a5a; text-align: left; }
        .p-totals .pt-value { text-align: right; font-weight: 600; color: #1e293b; }
        .p-totals .pt-grand td { padding: 14px 0 0; font-size: 20px; font-weight: 800; color: #0c0f15; border-top: 2px solid #0c0f15; }
        .p-totals .pt-grand .pt-label { color: #0c0f15; }
        .p-totals .pt-grand .pt-value { color: #0c0f15; font-size: 22px; }

        .p-amount-words { margin: 18px 0; padding: 16px 22px; background: #faf6ee; border-radius: 10px; font-size: 13px; color: #5a4a3a; border: 1px solid #e8e0d0; font-style: italic; }
        .p-amount-words strong { font-style: normal; color: #0c0f15; font-weight: 600; }

        .p-stamp-section { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 30px; padding-top: 26px; border-top: 1px solid #e8e0d0; gap: 20px; flex-wrap: wrap; }
        .p-stamp-group { display: flex; gap: 24px; align-items: flex-end; }
        .p-stamp-box { flex-shrink: 0; line-height: 0; }
        .p-stamp-box svg { display: block; width: 130px; height: 130px; transform: rotate(-1.5deg); filter: drop-shadow(1px 2px 6px rgba(0,0,0,0.05)); }

        .p-signature { text-align: right; max-width: 260px; }
        .p-signature img { max-height: 50px; display: block; margin-left: auto; margin-bottom: 6px; }
        .p-sig-cursive { font-family: 'Mea Culpa','Great Vibes',cursive; font-size: 34px; font-weight: 400; color: #8a7a5a; line-height: 1; display: block; text-align: right; margin-bottom: 2px; transform: rotate(-2deg); letter-spacing: 0.5px; }
        .p-signature .p-sig-line { width: 170px; height: 1.5px; background: linear-gradient(to right, transparent, #1e293b); margin: 6px 0 4px auto; }
        .p-signature .p-sig-name { font-weight: 700; font-size: 13px; color: #0c0f15; }
        .p-signature .p-sig-title { font-size: 9px; color: #8a7a5a; text-transform: uppercase; letter-spacing: 0.8px; }

        .p-footer { margin-top: 24px; padding: 20px 50px; background: #faf6ee; text-align: center; font-size: 11px; color: #8a7a5a; line-height: 1.8; border-top: 1px solid #e8e0d0; letter-spacing: 0.2px; }
        .p-footer strong { color: #5a4a3a; }

        .p-print { text-align: center; padding: 20px 0; }
        .p-print button { padding: 14px 44px; background: linear-gradient(135deg, #0c0f15, #1a1f2e); color: #f0e6d3; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600; letter-spacing: 0.3px; transition: all 0.2s; box-shadow: 0 4px 16px rgba(12,15,21,0.25); }
        .p-print button:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(12,15,21,0.35); }

        @media print {
            body { background: #fff; padding: 0; }
            .premium-wrap { box-shadow: none; border-radius: 0; max-width: 100%; }
            .p-print { display: none; }
            .p-top-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .p-table thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .p-stamp-box svg { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .p-status { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .p-brand-icon { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media (max-width: 700px) {
            body { padding: 12px; }
            .p-body { padding: 24px 20px; }
            .p-top-bar { padding: 16px 20px; flex-direction: column; text-align: center; gap: 8px; }
            .p-top-bar .p-meta { text-align: center; }
            .p-grid { grid-template-columns: 1fr; }
            .p-stamp-section { justify-content: center; }
            .p-stamp-group { flex-wrap: wrap; justify-content: center; }
            .p-signature { text-align: center; max-width: 100%; }
            .p-signature img { margin: 0 auto 6px; }
            .p-sig-cursive { text-align: center; }
            .p-signature .p-sig-line { margin: 6px auto 4px; }
            .p-totals table { width: 100%; }
            .p-footer { padding: 14px 20px; }
            .p-title-row { flex-direction: column; gap: 12px; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="premium-wrap">
    <div class="p-top-bar">
        <div class="p-brand">
            <div class="p-brand-icon">RS</div>
            <div class="p-brand-name"><?php echo sanitize($data['siteName'] ?? 'RS Digital Hub'); ?> <span>✦</span></div>
        </div>
        <div class="p-meta">
            <div class="p-meta-label">Invoice</div>
            <div class="p-meta-num"><?php echo sanitize($data['invoiceNumber'] ?? ''); ?></div>
        </div>
    </div>

    <div class="p-body">
        <div class="p-title-row">
            <div>
                <h1><?php echo ($data['type'] ?? '') === 'subscription' ? 'Subscription Receipt' : 'Order Receipt'; ?></h1>
                <div class="p-title-accent">Official tax invoice / <?php echo date('d M Y', strtotime($data['dateStr'] ?? 'now')); ?></div>
            </div>
            <span class="p-status">&#10003; <?php echo ($data['type'] ?? '') === 'subscription' ? 'Paid' : 'Completed'; ?></span>
        </div>

        <div class="p-grid">
            <div class="p-card">
                <h3>Invoice Details</h3>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($data['dateStr'] ?? 'now')); ?><br>
                <strong>Invoice No:</strong> <?php echo sanitize($data['invoiceNumber'] ?? ''); ?><br>
                <strong>Status:</strong> Paid</p>
            </div>
            <div class="p-card">
                <h3>Bill From</h3>
                <p><span class="name"><?php echo sanitize($data['sellerName'] ?? ''); ?></span>
                <?php echo sanitize($data['sellerEmail'] ?? ''); ?>
                <?php if (!empty($data['sellerGst'])): ?><br><span class="gstin">GSTIN: <?php echo $data['sellerGst']; ?></span><?php endif; ?></p>
            </div>
            <div class="p-card">
                <h3>Bill To</h3>
                <p><span class="name"><?php echo sanitize($data['buyerName'] ?? ''); ?></span>
                <?php echo sanitize($data['buyerEmail'] ?? ''); ?></p>
            </div>
        </div>

        <div class="p-divider"></div>

        <div class="p-table-wrap">
            <table class="p-table">
                <thead><tr><th>#</th><th>Description</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php $i = 1; foreach ($data['items'] ?? [] as $item): ?>
                    <tr><td><?php echo $i++; ?></td><td><?php echo sanitize($item['description']); ?></td><td><?php echo $item['amount_formatted'] ?? formatINR($item['amount']); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-totals">
            <table>
                <?php if (($data['type'] ?? '') === 'order' && ($data['discountAmount'] ?? 0) > 0): ?>
                <tr><td class="pt-label">Subtotal</td><td class="pt-value"><?php echo formatINR($data['totalAmount'] ?? 0); ?></td></tr>
                <tr><td class="pt-label">Discount</td><td class="pt-value" style="color:#059669">-<?php echo formatINR($data['discountAmount'] ?? 0); ?></td></tr>
                <?php endif; ?>
                <?php if (($data['type'] ?? '') === 'order' && ($data['taxAmount'] ?? 0) > 0): ?>
                <tr><td class="pt-label">Tax (GSTIN)</td><td class="pt-value"><?php echo formatINR($data['taxAmount'] ?? 0); ?></td></tr>
                <?php endif; ?>
                <tr class="pt-grand"><td class="pt-label">Total Amount</td><td class="pt-value"><?php echo $data['totalFormatted'] ?? formatINR($data['totalAmount'] ?? 0); ?></td></tr>
            </table>
        </div>

        <?php if (!empty($data['amountWords'])): ?>
        <div class="p-amount-words"><strong>Amount in words:</strong> <?php echo $data['amountWords']; ?></div>
        <?php endif; ?>

        <div class="p-stamp-section">
            <div class="p-stamp-group">
                <div class="p-stamp-box">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="300 100 600 600">
                      <defs>
                        <filter id="gA"><feTurbulence type="fractalNoise" baseFrequency="0.7" numOctaves="3" stitchTiles="stitch"/><feColorMatrix type="saturate" values="0"/><feBlend mode="multiply"/><feComponentTransfer><feFuncA type="table" tableValues="0 0.08"/></feComponentTransfer></filter>
                        <filter id="rA" x="-10%" y="-10%" width="120%" height="120%"><feTurbulence baseFrequency="0.8" numOctaves="2" result="turb"/><feDisplacementMap in="SourceGraphic" in2="turb" scale="3" xChannelSelector="R" yChannelSelector="G"/></filter>
                        <mask id="mA"><rect x="0" y="0" width="1200" height="1000" fill="white"/><g filter="url(#gA)" opacity="0.08"><rect x="-200" y="-200" width="1600" height="1400" fill="black"/></g></mask>
                      </defs>
                      <g mask="url(#mA)">
                        <circle cx="600" cy="400" r="280" stroke="#d4a853" stroke-width="4" fill="none" opacity="0.92" filter="url(#rA)"/>
                        <circle cx="600" cy="400" r="252" stroke="#d4a853" stroke-width="14" fill="none" opacity="0.88" filter="url(#rA)"/>
                      </g>
                      <g mask="url(#mA)" fill="#d4a853" opacity="0.75">
                        <g transform="translate(600,400)">
                          <polygon points="0,-16 5,-5 16,-5 8,3 10,13 0,8 -10,13 -8,3 -16,-5 -5,-5" transform="translate(-110,0)"/>
                          <polygon points="0,-16 5,-5 16,-5 8,3 10,13 0,8 -10,13 -8,3 -16,-5 -5,-5" transform="translate(110,0)"/>
                        </g>
                      </g>
                      <g mask="url(#mA)">
                        <circle cx="600" cy="400" r="185" fill="none" stroke="#d4a853" stroke-width="5" opacity="0.95" filter="url(#rA)"/>
                        <text x="600" y="420" text-anchor="middle" font-family="Oswald,'Arial Black','Impact',sans-serif" font-size="88" font-weight="700" fill="#d4a853" opacity="0.97">APPROVED</text>
                      </g>
                      <g opacity="0.045" filter="url(#gA)"><rect x="-100" y="-100" width="1400" height="1100" fill="#000"/></g>
                    </svg>
                </div>
                <div class="p-stamp-box">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="300 100 600 600">
                      <defs>
                        <filter id="gP"><feTurbulence type="fractalNoise" baseFrequency="0.7" numOctaves="3" stitchTiles="stitch"/><feColorMatrix type="saturate" values="0"/><feBlend mode="multiply"/><feComponentTransfer><feFuncA type="table" tableValues="0 0.08"/></feComponentTransfer></filter>
                        <filter id="rP" x="-10%" y="-10%" width="120%" height="120%"><feTurbulence baseFrequency="0.8" numOctaves="2" result="turb"/><feDisplacementMap in="SourceGraphic" in2="turb" scale="3" xChannelSelector="R" yChannelSelector="G"/></filter>
                        <mask id="mP"><rect x="0" y="0" width="1200" height="1000" fill="white"/><g filter="url(#gP)" opacity="0.08"><rect x="-200" y="-200" width="1600" height="1400" fill="black"/></g></mask>
                      </defs>
                      <g mask="url(#mP)">
                        <circle cx="600" cy="400" r="275" stroke="#d4a853" stroke-width="10" fill="none" opacity="0.90" filter="url(#rP)"/>
                        <circle cx="600" cy="400" r="250" stroke="#d4a853" stroke-width="2" fill="none" opacity="0.60" filter="url(#rP)"/>
                      </g>
                      <g mask="url(#mP)" fill="#d4a853" opacity="0.75">
                        <g transform="translate(600,400)">
                          <polygon points="0,-16 5,-5 16,-5 8,3 10,13 0,8 -10,13 -8,3 -16,-5 -5,-5" transform="translate(-120,0)"/>
                          <polygon points="0,-16 5,-5 16,-5 8,3 10,13 0,8 -10,13 -8,3 -16,-5 -5,-5" transform="translate(120,0)"/>
                        </g>
                      </g>
                      <g mask="url(#mP)">
                        <circle cx="600" cy="400" r="188" fill="none" stroke="#d4a853" stroke-width="4" opacity="0.70" filter="url(#rP)"/>
                        <text x="600" y="415" text-anchor="middle" font-family="Oswald,'Arial Black','Impact',sans-serif" font-size="95" font-weight="700" fill="#d4a853" opacity="0.97">PAID</text>
                      </g>
                      <g opacity="0.045" filter="url(#gP)"><rect x="-100" y="-100" width="1400" height="1100" fill="#000"/></g>
                    </svg>
                </div>
            </div>
            <div class="p-signature">
                <?php if (!empty($data['signaturePath']) && file_exists(__DIR__ . '/../../' . $data['signaturePath'])): ?>
                <img src="<?php echo SITE_URL . '/' . $data['signaturePath']; ?>" alt="Signature">
                <?php elseif (!empty($data['signatureText'])): ?>
                <span class="p-sig-cursive"><?php echo sanitize($data['signatureText']); ?></span>
                <?php endif; ?>
                <div class="p-sig-line"></div>
                <div class="p-sig-name"><?php echo sanitize($data['signatureName'] ?? 'Rohit Kumar'); ?></div>
                <div class="p-sig-title">Authorized Signatory</div>
            </div>
        </div>
    </div>

    <div class="p-footer">
        <strong><?php echo sanitize($data['siteName'] ?? 'RS Digital Hub'); ?></strong> &mdash; Thank you for your trust and business!
    </div>
</div>

<div class="p-print">
    <button onclick="window.print()">&#128424; Print / Save PDF</button>
</div>
</body>
</html>
