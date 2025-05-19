<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Organization Ledger Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }

        .header p {
            font-size: 12px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .organization-name {
            font-weight: bold;
            background-color: #f8f8f8;
        }

        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .positive {
            color: #28a745;
        }

        .negative {
            color: #dc3545;
        }

        .opening-balance {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Organization Ledger Report</h1>
            <p>Generated on: {{ date('F d, Y h:i A') }}</p>
        </div>

        @foreach ($ledgerData as $data)
        <table>
            <thead>
                <tr>
                    <th colspan="5" class="organization-name">{{ $data['organization']->name }}</th>
                </tr>
                @if ($data['opening']['amount'] > 0)
                <tr>
                    <td colspan="5" class="opening-balance">
                        Opening Balance: PKR {{ number_format($data['opening']['amount'], 2) }}
                        ({{ $data['opening']['type'] === 'credit' ? 'Credit' : 'Debit' }})
                    </td>
                </tr>
                @endif
                <tr>
                    <th>Type</th>
                    <th>Debit (Sent)</th>
                    <th>Credit (Received)</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Fund</td>
                    <td>PKR {{ number_format($data['sent']['fund'], 2) }}</td>
                    <td>PKR {{ number_format($data['received']['fund'], 2) }}</td>
                    <td
                        class="{{ ($data['received']['fund'] - $data['sent']['fund']) >= 0 ? 'positive' : 'negative' }}">
                        PKR {{ number_format($data['received']['fund'] - $data['sent']['fund'], 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Loan</td>
                    <td>PKR {{ number_format($data['sent']['loan'], 2) }}</td>
                    <td>PKR {{ number_format($data['received']['loan'], 2) }}</td>
                    <td
                        class="{{ ($data['received']['loan'] - $data['sent']['loan']) >= 0 ? 'positive' : 'negative' }}">
                        PKR {{ number_format($data['received']['loan'] - $data['sent']['loan'], 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Return</td>
                    <td>PKR {{ number_format($data['sent']['return'], 2) }}</td>
                    <td>PKR {{ number_format($data['received']['return'], 2) }}</td>
                    <td
                        class="{{ ($data['received']['return'] - $data['sent']['return']) >= 0 ? 'positive' : 'negative' }}">
                        PKR {{ number_format($data['received']['return'] - $data['sent']['return'], 2) }}
                    </td>
                </tr>
                <tr class="total-row">
                    <td>Total</td>
                    <td>PKR {{ number_format($data['sent']['total'], 2) }}</td>
                    <td>PKR {{ number_format($data['received']['total'], 2) }}</td>
                    <td class="{{ $data['balance'] >= 0 ? 'positive' : 'negative' }}">
                        PKR {{ number_format($data['balance'], 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
        @endforeach

        <table>
            <thead>
                <tr>
                    <th colspan="4">Grand Total (All Organizations)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="total-row">
                    <td>All Transactions</td>
                    <td>PKR {{ number_format($totalFunds + $totalLoans + $totalReturns, 2) }}</td>
                    <td>PKR {{ number_format($totalFunds + $totalLoans + $totalReturns, 2) }}</td>
                    <td>PKR 0.00</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
        </div>
    </div>
</body>

</html>