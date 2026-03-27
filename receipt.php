<?php
require_once 'php/auth.php';
require_once 'php/db.php';

checkLogin();
$user_id = $_SESSION['user_id'];
$ref = $_GET['ref'] ?? null;

if (!$ref) {
    die("Error: Reference number missing.");
}

function amountToWords($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'Zero',
        1                   => 'One',
        2                   => 'Two',
        3                   => 'Three',
        4                   => 'Four',
        5                   => 'Five',
        6                   => 'Six',
        7                   => 'Seven',
        8                   => 'Eight',
        9                   => 'Nine',
        10                  => 'Ten',
        11                  => 'Eleven',
        12                  => 'Twelve',
        13                  => 'Thirteen',
        14                  => 'Fourteen',
        15                  => 'Fifteen',
        16                  => 'Sixteen',
        17                  => 'Seventeen',
        18                  => 'Eighteen',
        19                  => 'Nineteen',
        20                  => 'Twenty',
        30                  => 'Thirty',
        40                  => 'Forty',
        50                  => 'Fifty',
        60                  => 'Sixty',
        70                  => 'Seventy',
        80                  => 'Eighty',
        90                  => 'Ninety',
        100                 => 'Hundred',
        1000                => 'Thousand',
        1000000             => 'Million',
        1000000000          => 'Billion',
        1000000000000       => 'Trillion',
        1000000000000000    => 'Quadrillion',
        1000000000000000000 => 'Quintillion'
    );

    if (!is_numeric($number)) return false;
    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        trigger_error('amountToWords only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING);
        return false;
    }

    if ($number < 0) return $negative . amountToWords(abs($number));

    $string = $fraction = null;
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) $string .= $hyphen . $dictionary[$units];
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) $string .= $conjunction . amountToWords($remainder);
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = amountToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= amountToWords($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= ' & ' . (int)$fraction . ' cents';
    } else {
        $string .= ' & Zero cents';
    }

    return $string;
}

try {
    // Fetch transaction by reference
    $stmt = $conn->prepare("
        SELECT t.*, 
               u.fullname as payer_name,
               DATE_FORMAT(t.created_at, '%m/%d/%Y, %h:%i:%s %p') as date,
               acc.account_number as source_acc,
               acc.currency,
               rec_acc.account_number as dest_acc,
               rec_u.fullname as receiver_name
        FROM transactions t
        LEFT JOIN accounts acc ON t.sender_account_id = acc.id
        LEFT JOIN users u ON acc.user_id = u.id
        LEFT JOIN accounts rec_acc ON t.receiver_account_id = rec_acc.id
        LEFT JOIN users rec_u ON rec_acc.user_id = rec_u.id
        WHERE t.reference_number = ? AND (acc.user_id = ? OR rec_acc.user_id = ?)
    ");
    $stmt->execute([$ref, $user_id, $user_id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$t) {
        die("Unauthorized access or transaction not found.");
    }

    $comm = $t['fee'] ?? 0;
    $vat = $comm * 0.15;
    $total_deducted = $t['amount'] + $comm + $vat;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CBE Official Receipt - <?php echo $ref; ?></title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: #f0f0f0; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .receipt-container { background: white; width: 800px; padding: 0; position: relative; border: 1px solid #ddd; }
        
        /* Purple Header */
        .purple-header { background: #800080; color: white; padding: 20px; display: flex; align-items: center; justify-content: center; position: relative; }
        .purple-header .logo-box { position: absolute; left: 100px; }
        .purple-header h1 { margin: 0; font-size: 24px; font-weight: 500; }
        .purple-header p { margin: 0; font-size: 16px; font-weight: 300; }

        .content { padding: 40px; }

        /* Top Grid Info */
        .info-sections { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .info-col h3 { font-size: 14px; margin-bottom: 15px; color: #333; font-weight: 600; }
        .info-row { display: flex; margin-bottom: 4px; font-size: 12px; }
        .label { width: 140px; color: #333; font-weight: 600; }
        .val { flex: 1; color: #444; }

        /* Payment Information Box */
        .payment-box { border: 2px solid #800080; border-radius: 12px; overflow: hidden; position: relative; }
        .payment-box h2 { background: transparent; color: #800080; text-align: center; margin: 15px 0; font-size: 20px; font-weight: 500; }
        
        .payment-table { width: 100%; border-collapse: collapse; }
        .payment-table td { border-top: 1px solid #800080; padding: 8px 20px; font-size: 13px; }
        .payment-table .row-label { width: 40%; font-weight: 500; color: #333; }
        .payment-table .row-val { width: 60%; text-align: right; font-weight: 500; }

        /* Stamp Overly */
        .stamp-overlay {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            z-index: 10;
            opacity: 0.6;
            pointer-events: none;
        }
        .stamp-circle {
            width: 140px;
            height: 140px;
            border: 4px double #003399;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #003399;
            font-weight: 700;
            font-size: 12px;
            padding: 10px;
        }

        /* Bottom Section */
        .amount-words-row { display: flex; align-items: center; justify-content: space-between; margin-top: 30px; }
        .words-box { border: 1px solid #333; padding: 15px; flex: 1; margin: 0 40px; text-align: center; font-weight: 500; }
        .qr-box img { width: 100px; }

        .footer-banner { border: 1.5px solid #800080; border-radius: 8px; margin-top: 40px; text-align: center; padding: 10px; }
        .footer-banner h4 { color: #800080; margin: 0 0 5px 0; font-size: 14px; }
        .footer-banner p { margin: 0; font-size: 12px; color: #333; }

        .no-print { position: fixed; bottom: 20px; right: 20px; }
        @media print { .no-print { display: none; } body { background: white; padding: 0; } }
    </style>
</head>
<body>

    <div class="receipt-container">
        <!-- Stamp -->
        <div class="stamp-overlay">
            <div class="stamp-circle">
                <div style="font-size: 8px; margin-bottom: 2px;">COMMERCIAL BANK OF ETHIOPIA</div>
                <div style="margin-bottom: 2px;"><i class="fas fa-university"></i></div>
                <div>VERIFIED<BR>DIGITAL<BR>SETTLEMENT</div>
                <div style="font-size: 7px; margin-top: 2px;">CBE-PROS CORE ENGINE</div>
            </div>
        </div>

        <div class="purple-header">
            <div style="text-align: center;">
                <h1>Commercial Bank of Ethiopia</h1>
                <p>VAT Invoice / Customer Receipt</p>
            </div>
        </div>

        <div class="content">
            <div class="info-sections">
                <div class="info-col">
                    <h3>Company Address & Other Information</h3>
                    <div class="info-row"><div class="label">Country:</div><div class="val">Ethiopia</div></div>
                    <div class="info-row"><div class="label">City:</div><div class="val">Addis Ababa</div></div>
                    <div class="info-row"><div class="label">Address:</div><div class="val">Ras Desta Damtew St, 01, Kirkos</div></div>
                    <div class="info-row"><div class="label">Postal code:</div><div class="val">255</div></div>
                    <div class="info-row"><div class="label">SWIFT Code:</div><div class="val">CBETETAA</div></div>
                    <div class="info-row"><div class="label">Email:</div><div class="val">info@cbe.com.et</div></div>
                    <div class="info-row"><div class="label">Tel:</div><div class="val">251-551-50-04</div></div>
                    <div class="info-row"><div class="label">VAT Receipt No:</div><div class="val">FT<?php echo substr(md5($ref), 0, 10); ?></div></div>
                </div>
                <div class="info-col">
                    <h3>Customer Information</h3>
                    <div class="info-row"><div class="label">Customer Name:</div><div class="val"><?php echo htmlspecialchars($t['payer_name']); ?></div></div>
                    <div class="info-row"><div class="label">Region:</div><div class="val">Addis Ababa</div></div>
                    <div class="info-row"><div class="label">VAT Registration No:</div><div class="val">--</div></div>
                    <div class="info-row"><div class="label">TIN (TAX ID):</div><div class="val">--</div></div>
                    <div class="info-row"><div class="label">Branch:</div><div class="val">DIGITAL BANKING</div></div>
                </div>
            </div>

            <div class="payment-box">
                <h2>Payment / Transaction Information</h2>
                <table class="payment-table">
                    <tr><td class="row-label">Payer</td><td class="row-val"><?php echo htmlspecialchars($t['payer_name']); ?></td></tr>
                    <tr><td class="row-label">Account</td><td class="row-val"><?php echo substr($t['source_acc'], 0, 1) . "****" . substr($t['source_acc'], -4); ?></td></tr>
                    <tr><td class="row-label">Receiver</td><td class="row-val"><?php echo htmlspecialchars($t['receiver_name'] ?? 'System / Utility'); ?></td></tr>
                    <tr><td class="row-label">Account</td><td class="row-val"><?php echo $t['dest_acc'] ? (substr($t['dest_acc'], 0, 1) . "****" . substr($t['dest_acc'], -4)) : "--"; ?></td></tr>
                    <tr><td class="row-label">Payment Date & Time</td><td class="row-val"><?php echo $t['date']; ?></td></tr>
                    <tr><td class="row-label">Reference No. (VAT Invoice No)</td><td class="row-val"><?php echo $ref; ?></td></tr>
                    <tr><td class="row-label">Reason / Type of service</td><td class="row-val"><?php echo $t['type']; ?>: <?php echo substr($t['description'], 0, 15); ?>...</td></tr>
                    <tr><td class="row-label">Transferred Amount</td><td class="row-val"><?php echo number_format($t['amount'], 2); ?> ETB</td></tr>
                    <tr><td class="row-label">Commission or Service Charge</td><td class="row-val"><?php echo number_format($comm, 2); ?> ETB</td></tr>
                    <tr><td class="row-label">15% VAT on Commission</td><td class="row-val"><?php echo number_format($vat, 2); ?> ETB</td></tr>
                    <tr style="background: rgba(128,0,128,0.05); font-weight: 700;"><td class="row-label">Total amount debited from customers account</td><td class="row-val"><?php echo number_format($total_deducted, 2); ?> ETB</td></tr>
                </table>
            </div>

            <div class="amount-words-row">
                <div style="font-size: 13px; font-weight: 600;">Amount in Word</div>
                <div class="words-box">ETB <?php echo amountToWords($t['amount']); ?></div>
                <div class="qr-box" id="receiptQR"></div>
            </div>

            <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px dashed #ccc; border-radius: 8px; font-size: 10px; color: #666;">
                <div style="font-weight: 700; margin-bottom: 5px; color: #800080; text-transform: uppercase;">Forensic Verification Data</div>
                <div>IP Address: <?php echo $t['ip_address'] ?? 'N/A'; ?></div>
                <div style="word-break: break-all;">Source signature: <?php echo substr($t['user_agent'], 0, 80); ?>...</div>
                <div>Session Fingerprint: <?php echo $t['session_id'] ? substr($t['session_id'], 0, 16) : 'N/A'; ?></div>
            </div>

            <div class="footer-banner">
                <h4>The Bank you can always rely on.</h4>
                <p>&copy; 2026 Commercial Bank of Ethiopia. All rights reserved.</p>
            </div>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 15px 30px; background: #800080; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: 700; box-shadow: 0 10px 20px rgba(0,0,0,0.2);">Print Official Receipt</button>
    </div>

    <!-- QR Code Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById("receiptQR"), {
            text: "<?php echo $ref; ?>",
            width: 80,
            height: 80
        });
    </script>
</body>
</html>
