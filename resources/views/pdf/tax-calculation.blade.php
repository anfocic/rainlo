@extends('pdf.layout')

@section('title', 'Tax Calculation Report - Rainlo')
@section('subtitle', 'Ireland Tax Calculation for 2025')

@section('content')
    @php
        $annual = $calculation['annual'];
        $monthly = $calculation['monthly'];
    @endphp

    <div class="user-info">
        <h3>Tax Calculation Details</h3>
        <div><strong>Annual Income:</strong> €{{ number_format($annual['annual_income'], 2) }}</div>
        <div><strong>Marital Status:</strong> {{ ucfirst(str_replace('_', ' ', $annual['marital_status'])) }}</div>
        <div><strong>Has Children:</strong> {{ $annual['has_children'] ? 'Yes' : 'No' }}</div>
        @if($annual['spouse_income'])
            <div><strong>Spouse Income:</strong> €{{ number_format($annual['spouse_income'], 2) }}</div>
        @endif
    </div>

    <div class="summary-grid">
        <div class="summary-item positive">
            <div class="label">Annual Net Income</div>
            <div class="value">€{{ number_format($annual['net_income'], 2) }}</div>
        </div>
        <div class="summary-item negative">
            <div class="label">Total Tax</div>
            <div class="value">€{{ number_format($annual['breakdown']['net_tax'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Effective Tax Rate</div>
            <div class="value">{{ $annual['effective_tax_rate'] }}%</div>
        </div>
    </div>

    <h3>Annual Tax Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Tax Component</th>
                <th>Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Income Tax (PAYE)</td>
                <td class="amount negative">€{{ number_format($annual['breakdown']['income_tax'], 2) }}</td>
                <td>Standard rate (20%) and higher rate (40%)</td>
            </tr>
            <tr>
                <td>Universal Social Charge (USC)</td>
                <td class="amount negative">€{{ number_format($annual['breakdown']['usc'], 2) }}</td>
                <td>Progressive rates: 0.5%, 2%, 3%, 8%</td>
            </tr>
            <tr>
                <td>PRSI (Pay Related Social Insurance)</td>
                <td class="amount negative">€{{ number_format($annual['breakdown']['prsi'], 2) }}</td>
                <td>4.2% for Class A1 employees</td>
            </tr>
            <tr style="border-top: 2px solid #374151;">
                <td><strong>Gross Tax</strong></td>
                <td class="amount negative"><strong>€{{ number_format($annual['breakdown']['gross_tax'], 2) }}</strong></td>
                <td>Total before tax credits</td>
            </tr>
            <tr>
                <td>Tax Credits</td>
                <td class="amount positive">-€{{ number_format($annual['breakdown']['tax_credits'], 2) }}</td>
                <td>Personal and employee PAYE credits</td>
            </tr>
            <tr style="border-top: 2px solid #374151; background: #f9fafb;">
                <td><strong>Net Tax Payable</strong></td>
                <td class="amount negative"><strong>€{{ number_format($annual['breakdown']['net_tax'], 2) }}</strong></td>
                <td>Final tax liability</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h3>Monthly Breakdown</h3>
    <div class="summary-grid">
        <div class="summary-item positive">
            <div class="label">Monthly Gross Income</div>
            <div class="value">€{{ number_format($monthly['monthly_gross_income'], 2) }}</div>
        </div>
        <div class="summary-item negative">
            <div class="label">Monthly Tax</div>
            <div class="value">€{{ number_format($monthly['monthly_breakdown']['net_tax'], 2) }}</div>
        </div>
        <div class="summary-item positive">
            <div class="label">Monthly Net Income</div>
            <div class="value">€{{ number_format($monthly['monthly_net_income'], 2) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tax Component</th>
                <th>Monthly Amount</th>
                <th>Annual Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Income Tax (PAYE)</td>
                <td class="amount negative">€{{ number_format($monthly['monthly_breakdown']['income_tax'], 2) }}</td>
                <td class="amount negative">€{{ number_format($annual['breakdown']['income_tax'], 2) }}</td>
            </tr>
            <tr>
                <td>Universal Social Charge (USC)</td>
                <td class="amount negative">€{{ number_format($monthly['monthly_breakdown']['usc'], 2) }}</td>
                <td class="amount negative">€{{ number_format($annual['breakdown']['usc'], 2) }}</td>
            </tr>
            <tr>
                <td>PRSI</td>
                <td class="amount negative">€{{ number_format($monthly['monthly_breakdown']['prsi'], 2) }}</td>
                <td class="amount negative">€{{ number_format($annual['breakdown']['prsi'], 2) }}</td>
            </tr>
            <tr>
                <td>Tax Credits</td>
                <td class="amount positive">-€{{ number_format($monthly['monthly_breakdown']['tax_credits'], 2) }}</td>
                <td class="amount positive">-€{{ number_format($annual['breakdown']['tax_credits'], 2) }}</td>
            </tr>
            <tr style="border-top: 2px solid #374151; background: #f9fafb;">
                <td><strong>Net Tax</strong></td>
                <td class="amount negative"><strong>€{{ number_format($monthly['monthly_breakdown']['net_tax'], 2) }}</strong></td>
                <td class="amount negative"><strong>€{{ number_format($annual['breakdown']['net_tax'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <h3 class="mt-20">Tax Rate Information</h3>
    <table>
        <thead>
            <tr>
                <th>Rate Type</th>
                <th>Percentage</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Effective Tax Rate</td>
                <td class="text-right">{{ $annual['effective_tax_rate'] }}%</td>
                <td>Overall percentage of income paid in tax</td>
            </tr>
            <tr>
                <td>Marginal Tax Rate</td>
                <td class="text-right">{{ $annual['marginal_tax_rate'] }}%</td>
                <td>Tax rate on the next euro earned</td>
            </tr>
        </tbody>
    </table>

    <div class="filters mt-20">
        <strong>Tax Year:</strong> {{ $metadata['tax_year'] ?? '2025' }}<br>
        <strong>Calculation Date:</strong> {{ $metadata['calculation_date']->format('F j, Y \a\t g:i A') }}<br>
        <strong>Based on:</strong> Ireland Revenue.ie tax rates and bands for 2025
    </div>

    <div class="mt-20" style="background: #fef3c7; padding: 15px; border-radius: 6px; font-size: 11px;">
        <strong>Disclaimer:</strong> This calculation is for informational purposes only and is based on standard tax rates for 2025. 
        Actual tax liability may vary based on individual circumstances, additional allowances, reliefs, and other factors. 
        Please consult with a qualified tax advisor or the Revenue Commissioners for official tax advice.
    </div>
@endsection
