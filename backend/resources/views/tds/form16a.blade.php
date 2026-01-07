<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 16A - TDS Certificate</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11px;
            margin: 2px 0;
        }
        .certificate-number {
            text-align: right;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background: #e0e0e0;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
            border: 1px solid #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.info-table td {
            padding: 6px;
            border: 1px solid #666;
            font-size: 10px;
        }
        table.info-table td.label {
            font-weight: bold;
            width: 40%;
            background: #f5f5f5;
        }
        table.deductions-table th {
            background: #333;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9px;
            border: 1px solid #000;
        }
        table.deductions-table td {
            padding: 6px;
            border: 1px solid #666;
            font-size: 9px;
        }
        table.deductions-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        table.deductions-table tfoot td {
            font-weight: bold;
            background: #e0e0e0;
            border-top: 2px solid #000;
        }
        .summary-box {
            background: #fff9e6;
            border: 2px solid #ffa500;
            padding: 15px;
            margin: 20px 0;
        }
        .summary-box h3 {
            font-size: 12px;
            margin-bottom: 10px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 11px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #000;
            font-size: 9px;
            color: #555;
        }
        .signature-section {
            margin-top: 40px;
        }
        .signature-box {
            width: 200px;
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
            text-align: center;
            font-size: 9px;
        }
        .note {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border-left: 3px solid #333;
            font-size: 8px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FORM NO. 16A</h1>
        <p>[See section 203 and rule 31(1)(a)]</p>
        <p>Certificate under section 203 of the Income-tax Act, 1961</p>
        <p>for tax deducted at source on income other than salary</p>
    </div>

    <div class="certificate-number">
        Certificate Number: <span style="color: #0066cc;">{{ $certificate_number }}</span>
    </div>

    <div class="section">
        <div class="section-title">PART A - Details of the Deductor</div>
        <table class="info-table">
            <tr>
                <td class="label">Name of Deductor</td>
                <td>PREIPOPSIP INVESTMENT PLATFORM</td>
            </tr>
            <tr>
                <td class="label">TAN of Deductor</td>
                <td>{{ config('app.company_tan', 'ABCD12345E') }}</td>
            </tr>
            <tr>
                <td class="label">PAN of Deductor</td>
                <td>{{ config('app.company_pan', 'ABCDE1234F') }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td>{{ config('app.company_address', 'PreIPOsip Platform, India') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">PART B - Details of the Deductee</div>
        <table class="info-table">
            <tr>
                <td class="label">Name of Deductee</td>
                <td>{{ strtoupper($user->name) }}</td>
            </tr>
            <tr>
                <td class="label">PAN of Deductee</td>
                <td>{{ $user->pan_number ?? 'NOT AVAILABLE' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <td class="label">Financial Year</td>
                <td>{{ $financial_year }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">PART C - Details of Tax Deducted</div>
        @if(!empty($user->pan_number))
        <div class="note">
            Note: PAN is available. TDS deducted as per applicable rates under respective sections.
        </div>
        @else
        <div class="note" style="background: #ffe6e6; border-left-color: #cc0000;">
            <strong>WARNING:</strong> PAN not available. TDS deducted at 20% as per Section 206AA.
        </div>
        @endif

        <table class="deductions-table">
            <thead>
                <tr>
                    <th>Qtr</th>
                    <th>Receipt No.</th>
                    <th>Date of Deduction</th>
                    <th>Section</th>
                    <th>Transaction Type</th>
                    <th>Gross Amount (₹)</th>
                    <th>Rate (%)</th>
                    <th>TDS (₹)</th>
                    <th>Net Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deductions as $deduction)
                <tr>
                    <td>Q{{ $deduction->quarter }}</td>
                    <td>{{ $deduction->certificate_number ?? 'N/A' }}</td>
                    <td>{{ $deduction->deduction_date->format('d-M-Y') }}</td>
                    <td>{{ $deduction->section_code }}</td>
                    <td>{{ ucfirst($deduction->transaction_type) }}</td>
                    <td style="text-align: right;">{{ number_format($deduction->gross_amount, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($deduction->tds_rate, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($deduction->tds_amount, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($deduction->net_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right;">TOTAL:</td>
                    <td style="text-align: right;">₹ {{ number_format($total_gross, 2) }}</td>
                    <td></td>
                    <td style="text-align: right;">₹ {{ number_format($total_tds, 2) }}</td>
                    <td style="text-align: right;">₹ {{ number_format($total_gross - $total_tds, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="summary-box">
        <h3>Summary of Tax Deducted and Deposited</h3>
        <div class="summary-item">
            <span>Total Amount Paid:</span>
            <span>₹ {{ number_format($total_gross, 2) }}</span>
        </div>
        <div class="summary-item">
            <span>Total TDS Deducted:</span>
            <span>₹ {{ number_format($total_tds, 2) }}</span>
        </div>
        <div class="summary-item">
            <span>Total Net Amount Credited:</span>
            <span>₹ {{ number_format($total_gross - $total_tds, 2) }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">PART D - Tax Deposit Details</div>
        <table class="info-table">
            @php
                $groupedByDeposit = $deductions->groupBy('challan_number');
            @endphp
            @foreach($groupedByDeposit as $challan => $group)
            <tr>
                <td class="label">Challan Number</td>
                <td>{{ $challan ?? 'Pending Deposit' }}</td>
            </tr>
            <tr>
                <td class="label">BSR Code</td>
                <td>{{ $group->first()->bsr_code ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Date of Deposit</td>
                <td>{{ $group->first()->deposit_date ? $group->first()->deposit_date->format('d-M-Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Amount Deposited</td>
                <td>₹ {{ number_format($group->sum('tds_amount'), 2) }}</td>
            </tr>
            @endforeach
        </table>
    </div>

    <div class="signature-section">
        <p><strong>Verification:</strong></p>
        <p style="margin: 10px 0;">I, {{ config('app.company_authorized_signatory', 'Authorized Signatory') }}, do hereby declare that the information given above is true, complete and correct.</p>

        <div class="signature-box">
            Signature of Authorized Signatory<br>
            <strong>PreIPOsip Investment Platform</strong><br>
            Date: {{ $generated_at->format('d-M-Y') }}
        </div>
    </div>

    <div class="footer">
        <p><strong>Important Notes:</strong></p>
        <ul style="margin-left: 20px; margin-top: 5px;">
            <li>This is a system-generated certificate and does not require physical signature.</li>
            <li>Please verify the details with Form 26AS available on TRACES portal.</li>
            <li>In case of any discrepancy, please contact support@preiposip.com</li>
            <li>This certificate is valid for the purpose of claiming TDS credit in your Income Tax Return.</li>
        </ul>
        <p style="margin-top: 15px; text-align: center;">
            Generated on: {{ $generated_at->format('d M Y, h:i A') }}<br>
            © {{ now()->year }} PreIPOsip Investment Platform | www.preiposip.com
        </p>
    </div>
</body>
</html>
