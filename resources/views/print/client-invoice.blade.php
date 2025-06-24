<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Invoice - {{ $job->job_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #fff;
            color: #333;
            line-height: 1.4;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .invoice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .invoice-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.1;
        }

        .company-info {
            position: relative;
            z-index: 2;
        }

        .company-logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .company-details {
            font-size: 14px;
            opacity: 0.9;
        }

        .invoice-title {
            text-align: right;
            position: relative;
            z-index: 2;
        }

        .invoice-title h1 {
            font-size: 36px;
            margin: 0;
            font-weight: 300;
        }

        .invoice-number {
            font-size: 18px;
            margin-top: 5px;
            opacity: 0.9;
        }

        .invoice-body {
            padding: 30px;
        }

        .billing-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .billing-info h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }

        .billing-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .job-details {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .job-details h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 18px;
        }

        .job-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .job-detail-item {
            display: flex;
            flex-direction: column;
        }

        .job-detail-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .job-detail-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .items-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .items-table tbody tr:hover {
            background-color: #f8f9ff;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .sub-account-header {
            background: #f0f2ff;
            font-weight: 600;
            color: #667eea;
        }

        .sub-item-row {
            background: #fafbff;
        }

        .sub-item-name {
            padding-left: 20px;
            font-style: italic;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            background: #f8f9ff;
            padding: 25px;
            border-radius: 8px;
            border: 2px solid #e0e6ff;
        }

        .totals-grid {
            display: grid;
            gap: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .total-row.grand-total {
            border-top: 2px solid #667eea;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }

        .budget-info {
            margin-top: 20px;
            padding: 15px;
            background: #f0f8f0;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }

        .budget-info.over-budget {
            background: #fff5f5;
            border-left-color: #dc3545;
        }

        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-open {
            background: #d4edda;
            color: #155724;
        }

        .status-closed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .page-break {
            page-break-before: always;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 2px solid #333;
        }

        .summary-table th {
            background: #333;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }

        .summary-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #333;
            font-size: 14px;
            border-right: 1px solid #333;
        }

        .summary-table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .summary-totals {
            margin-top: 20px;
            border: 2px solid #333;
        }

        .summary-totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-totals td {
            padding: 8px 12px;
            border-bottom: 1px solid #333;
            font-weight: bold;
        }

        .summary-totals .total-label {
            background: #f0f0f0;
            width: 70%;
        }

        .summary-totals .total-amount {
            text-align: right;
            width: 30%;
        }

        .summary-totals .grand-total-row {
            background: #333;
            color: white;
        }

        .detailed-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #333;
            font-size: 12px;
        }

        .detailed-table th {
            background: #333;
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #333;
        }

        .detailed-table td {
            padding: 6px;
            border: 1px solid #333;
            text-align: center;
        }

        .detailed-table .description-col {
            text-align: left;
            width: 30%;
        }

        .detailed-table .amount-col {
            text-align: right;
            width: 12%;
        }

        .category-header {
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }

        .signature-section {
            margin-top: 50px;
            text-align: center;
        }

        .signature-line {
            border-bottom: 2px solid #333;
            width: 250px;
            margin: 30px auto 10px auto;
        }

        .company-seal {
            margin: 20px 0;
            text-align: center;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                max-width: none;
            }

            .no-print {
                display: none;
            }
        }

        @page {
            margin: 1mm;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start;">
                <div class="company-info">
                    <div class="company-logo">{{ $job->organization->name }}</div>
                    <div class="company-details">
                        {{-- <p>Supplier: {{ $job->organization->name }}</p> --}}
                    </div>
                </div>
                <div class="invoice-title">
                    <h1>Invoice</h1>
                    <div style="padding: 10px; margin-top: 10px;">
                        <div><strong>DATE:</strong> {{ $job->created_at->format('M d, Y') }}</div>
                        <div><strong>INVOICE #:</strong> {{ $job->job_number }}</div>
                        <div><strong>NTN/PRA Registration No:</strong> {{ $job->organization->ntn ?? '1234567-8' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Body -->
        <div class="invoice-body">
            <!-- Client Information -->
            <div style="margin-bottom: 30px;">
                <div><strong>To:</strong></div>
                <div style="margin-top: 10px;">
                    <div><strong>{{ $job->client->name ?? 'Client Name' }}</strong></div>
                    @if($job->client && $job->client->address)
                    <div>{{ $job->client->address }}</div>
                    @endif
                    @if($job->client && $job->client->contact_number)
                    <div>NTN# {{ $job->client->contact_number }}</div>
                    @endif
                </div>
            </div>

            <!-- Summary Table (Page 1) -->
            @if($job->jobCostings->count() > 0)
            @php
            $groupedCostings = $job->jobCostings->groupBy('sub_account_id');
            $grandTotal = $job->jobCostings->sum('total_amount');
            @endphp

            <table class="summary-table">
                <thead>
                    <tr>
                        <th style="width: 70%;">DESCRIPTION</th>
                        <th style="width: 30%;">AMOUNT (RS)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedCostings as $subAccountId => $costings)
                    @php
                    $subAccount = $costings->first()->subAccount;
                    $subAccountTotal = $costings->sum('total_amount');
                    @endphp
                    <tr>
                        <td>{{ $subAccount ? $subAccount->name : 'Unknown Category' }}</td>
                        <td>{{ number_format($subAccountTotal, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals Section -->
            <div class="summary-totals">
                <table>
                    <tr>
                        <td class="total-label">TOTAL</td>
                        <td class="total-amount">{{ number_format($grandTotal, 0) }}</td>
                    </tr>
                    @if($job->gst)
                    @php
                    $gstRate = 0.16; // 16% as shown in reference
                    $gstAmount = $grandTotal * $gstRate;
                    $totalWithGst = $grandTotal + $gstAmount;
                    @endphp
                    <tr>
                        <td class="total-label">PST @ 16%</td>
                        <td class="total-amount">{{ number_format($gstAmount, 0) }}</td>
                    </tr>
                    <tr class="grand-total-row">
                        <td class="total-label">Gross Amount</td>
                        <td class="total-amount">{{ number_format($totalWithGst, 0) }}</td>
                    </tr>
                    @else
                    <tr class="grand-total-row">
                        <td class="total-label" style="color:black">Gross Amount</td>
                        <td class="total-amount">{{ number_format($grandTotal, 0) }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <div style="margin-top: 20px; font-size: 14px;">
                <strong>Note:</strong> Detailed items breakdown (Description, Quantity and rates) are attached in
                Annexure 'A'.
            </div>

            <!-- Signature Section -->
            <div class="signature-section">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 100px; margin-top: 60px;">
                    <div>
                        <div class="signature-line"></div>
                        <div><strong>Authorized Signature</strong></div>
                    </div>
                    <div class="company-seal">
                        <div
                            style="border: 2px solid #333; border-radius: 50%; width: 80px; height: 80px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                            <small>COMPANY<br>SEAL</small>
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px; padding: 10px; background: #f0f0f0; font-size: 12px;">
                {{ $job->organization->address ?? 'Company Address and Contact Information' }}
            </div>

            <!-- Page Break -->
            <div class="page-break"></div>
            <hr>
            <!-- Page 2: Detailed Breakdown (Annexure A) -->
            <div style="margin-bottom: 30px;">
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <td><strong>Client:</strong> {{ $job->client->name ?? 'Client Name' }}</td>
                        <td style="text-align: right;">
                            <div style="background: #333; color: white; padding: 10px; display: inline-block;">{{
                                $job->organization->name ?? 'COMPANY LOGO' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Event:</strong> {{ $job->campaign }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><strong>Event Date:</strong> {{ $job->created_at->format('jS M, Y') }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><strong>Venue:</strong> {{ $job->sale_by }}</td>
                        <td></td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="margin: 0; text-decoration: underline;">Annexure A</h2>
            </div>

            <table class="detailed-table">
                <thead>
                    <tr>
                        <th>Sr.No</th>
                        <th class="description-col">Description</th>
                        <th>Qty</th>
                        <th>Rate PKR</th>
                        <th class="amount-col">Amount</th>
                        <th class="amount-col">Sub Amount PKR</th>
                    </tr>
                </thead>
                <tbody>
                    @php $srNo = 1; @endphp
                    @foreach($groupedCostings as $subAccountId => $costings)
                    @php
                    $subAccount = $costings->first()->subAccount;
                    $subAccountTotal = $costings->sum('total_amount');
                    @endphp

                    <!-- Category Header -->
                    <tr>
                        <td class="category-header"><strong>{{ $srNo }}</strong></td>
                        <td class="category-header description-col"><strong>{{ $subAccount ? $subAccount->name :
                                'Unknown Category' }}</strong></td>
                        <td class="category-header"></td>
                        <td class="category-header"></td>
                        <td class="category-header"></td>
                        <td class="category-header amount-col"><strong>{{ number_format($subAccountTotal, 0) }}</strong>
                        </td>
                    </tr>

                    @php $subItemNo = 1; @endphp
                    @foreach($costings as $costing)
                    <tr>
                        <td>{{ $srNo }}.{{ $subItemNo }}</td>
                        <td class="description-col">{{ $costing->subItem ? $costing->subItem->name :
                            $costing->sub_item_name }}</td>
                        <td>{{ number_format($costing->quantity) }}</td>
                        <td>{{ number_format($costing->rate, 0) }}</td>
                        <td class="amount-col">{{ number_format($costing->total_amount, 0) }}</td>
                        <td class="amount-col"></td>
                    </tr>
                    @php $subItemNo++; @endphp
                    @endforeach
                    @php $srNo++; @endphp
                    @endforeach

                    <!-- Totals Row -->
                    <tr style="border-top: 2px solid #333;">
                        <td colspan="5" style="text-align: right; font-weight: bold; background: #f0f0f0;">Amount of
                            Deliverables</td>
                        <td class="amount-col" style="font-weight: bold; background: #f0f0f0;">{{
                            number_format($grandTotal, 0) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Final Totals -->
            <div style="margin-top: 20px;">
                <table style="width: 40%; margin-left: auto; border: 1px solid #333;">
                    @if($job->gst)
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #333;"><strong>PST @ 16 %</strong></td>
                        <td
                            style="padding: 8px; text-align: right; border-bottom: 1px solid #333; border-left: 1px solid #333;">
                            {{ number_format($gstAmount, 0) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; background: #f0f0f0;"><strong>Total Amount</strong></td>
                        <td style="padding: 8px; text-align: right; background: #f0f0f0; border-left: 1px solid #333;">
                            <strong>{{ number_format($totalWithGst, 0) }}</strong>
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td style="padding: 8px; background: #f0f0f0;"><strong>Total Amount</strong></td>
                        <td style="padding: 8px; text-align: right; background: #f0f0f0; border-left: 1px solid #333;">
                            <strong>{{ number_format($grandTotal, 0) }}</strong>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>

            <div style="text-align: center; margin-top: 40px; padding: 10px; background: #f0f0f0; font-size: 12px;">
                {{ $job->organization->address ?? 'Company Address and Contact Information' }}
            </div>

            @else
            <div style="text-align: center; padding: 40px; color: #666;">
                <p>No items found for this job booking.</p>
            </div>
            @endif
        </div>

    </div>
</body>

</html>