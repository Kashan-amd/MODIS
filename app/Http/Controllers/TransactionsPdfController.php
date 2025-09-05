<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Organization;
use App\Models\OpeningBalance;

class TransactionsPdfController extends Controller
{
    public function printLedger()
    {
        // Get the same data as in the Livewire component
        $ledgerData = $this->getLedgerData();

        $totalFunds = Transaction::where("transaction_type", "fund")->sum("amount");
        $totalLoans = Transaction::where("transaction_type", "loan")->sum("amount");
        $totalReturns = Transaction::where("transaction_type", "return")->sum("amount");

        // Generate PDF
        $pdf = PDF::loadView("print.transactions-print", [
            "ledgerData" => $ledgerData,
            "totalFunds" => $totalFunds,
            "totalLoans" => $totalLoans,
            "totalReturns" => $totalReturns,
        ]);

        // Set paper size to A4
        $pdf->setPaper("a4", "portrait");

        // Download the file with a proper filename
        return $pdf->download("organization-ledger-" . date("Y-m-d") . ".pdf");
    }

    private function getLedgerData()
    {
        $organizations = Organization::all();
        $ledgerData = [];

        foreach ($organizations as $organization) {
            // Get opening balance for the organization
            $openingBalance = OpeningBalance::where("organization_id", $organization->id)->first();
            $opening = [
                "amount" => $openingBalance ? $openingBalance->amount : 0,
                "type" => $openingBalance ? $openingBalance->type : null, // 'credit' or 'debit'
            ];

            $sent = [
                "fund" => Transaction::where("from_organization_id", $organization->id)->where("transaction_type", "fund")->sum("amount"),
                "loan" => Transaction::where("from_organization_id", $organization->id)->where("transaction_type", "loan")->sum("amount"),
                "return" => Transaction::where("from_organization_id", $organization->id)->where("transaction_type", "return")->sum("amount"),
                "total" => Transaction::where("from_organization_id", $organization->id)->sum("amount"),
            ];

            $received = [
                "fund" => Transaction::where("to_organization_id", $organization->id)->where("transaction_type", "fund")->sum("amount"),
                "loan" => Transaction::where("to_organization_id", $organization->id)->where("transaction_type", "loan")->sum("amount"),
                "return" => Transaction::where("to_organization_id", $organization->id)->where("transaction_type", "return")->sum("amount"),
                "total" => Transaction::where("to_organization_id", $organization->id)->sum("amount"),
            ];

            // Calculate balance including opening balance
            $transactionBalance = $received["total"] - $sent["total"];
            $finalBalance = $transactionBalance;

            // Adjust balance based on opening balance type
            if ($opening["type"] === "credit") {
                $finalBalance += $opening["amount"];
            } elseif ($opening["type"] === "debit") {
                $finalBalance -= $opening["amount"];
            }

            $ledgerData[$organization->id] = [
                "organization" => $organization,
                "opening" => $opening,
                "sent" => $sent,
                "received" => $received,
                "transaction_balance" => $transactionBalance,
                "balance" => $finalBalance,
            ];
        }

        return $ledgerData;
    }
}
