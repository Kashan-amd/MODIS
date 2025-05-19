<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Organization Ledger Summary</title>
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
            text-align: center;
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

        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 30px;
        }

        td.amount {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Organization Ledger Summary</h1>
            <p>Generated on: {{ date('F d, Y h:i A') }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Organization</th>
                    <th>Opening Balance</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalDebit = 0;
                $totalCredit = 0;
                @endphp

                @foreach ($ledgerData as $data)
                @php
                // Keep original values for debit and credit
                $debitAmount = $data['sent']['total'];
                $creditAmount = $data['received']['total'];

                // Calculate balance including opening balance
                $balance = $creditAmount - $debitAmount;

                // Adjust balance based on opening balance
                if ($data['opening']['amount'] > 0) {
                if ($data['opening']['type'] === 'debit') {
                $balance -= $data['opening']['amount']; // Debit decreases balance
                } else {
                $balance += $data['opening']['amount']; // Credit increases balance
                }
                }

                // Track totals for grand total row
                $totalDebit += $debitAmount;
                $totalCredit += $creditAmount;
                @endphp

                <tr>
                    <td>{{ $data['organization']->name }}</td>
                    <td class="amount">
                        @if ($data['opening']['amount'] > 0)
                        @if ($data['opening']['type'] === 'debit')
                        {{ number_format($data['opening']['amount'], 2) }} (Dr)
                        @else
                        {{ number_format($data['opening']['amount'], 2) }} (Cr)
                        @endif
                        @else
                        0.00
                        @endif
                    </td>
                    <td class="amount">{{ number_format($data['sent']['total'], 2) }}</td>
                    <td class="amount">{{ number_format($data['received']['total'], 2) }}</td>
                    <td class="amount {{ $balance >= 0 ? 'positive' : 'negative' }}">
                        @if ($balance >= 0)
                        ({{ number_format(abs($balance), 2) }})
                        @else
                        {{ number_format(abs($balance), 2) }} (Dr)
                        @endif
                    </td>
                </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="2"><strong>Grand Total</strong></td>
                    <td class="amount">{{ number_format($totalDebit, 2) }}</td>
                    <td class="amount"> {{ number_format($totalCredit, 2) }}</td>
                    <td class="amount {{ ($totalCredit - $totalDebit) >= 0 ? 'positive' : 'negative' }}">
                        @if (($totalCredit - $totalDebit) >= 0)
                        ({{ number_format(abs($totalCredit - $totalDebit), 2) }})
                        @else
                        {{ number_format(abs($totalCredit - $totalDebit), 2) }} (Dr)
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
        {{--
        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
        </div> --}}
    </div>
</body>

</html>