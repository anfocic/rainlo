@extends('pdf.layout')

@section('title', 'Financial Summary - Rainlo')
@section('subtitle', 'Financial Summary Report')

@section('content')
    <div class="summary-grid">
        <div class="summary-item positive">
            <div class="label">Total Income</div>
            <div class="value">€{{ number_format($summary['total_incomes'], 2) }}</div>
        </div>
        <div class="summary-item negative">
            <div class="label">Total Expenses</div>
            <div class="value">€{{ number_format($summary['total_expenses'], 2) }}</div>
        </div>
        <div class="summary-item {{ $summary['net_income'] >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Net Income</div>
            <div class="value">€{{ number_format($summary['net_income'], 2) }}</div>
        </div>
    </div>

    <h3>Business vs Personal Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Business Income</th>
                <th>Personal Income</th>
                <th>Business Expenses</th>
                <th>Personal Expenses</th>
                <th>Net Result</th>
            </tr>
        </thead>
        <tbody>
            <tr class="business">
                <td><strong>Business</strong></td>
                <td class="amount positive">€{{ number_format($summary['business_incomes'], 2) }}</td>
                <td class="amount">-</td>
                <td class="amount negative">€{{ number_format($summary['business_expenses'], 2) }}</td>
                <td class="amount">-</td>
                <td class="amount {{ $summary['business_net'] >= 0 ? 'positive' : 'negative' }}">€{{ number_format($summary['business_net'], 2) }}</td>
            </tr>
            <tr class="personal">
                <td><strong>Personal</strong></td>
                <td class="amount">-</td>
                <td class="amount positive">€{{ number_format($summary['personal_incomes'], 2) }}</td>
                <td class="amount">-</td>
                <td class="amount negative">€{{ number_format($summary['personal_expenses'], 2) }}</td>
                <td class="amount {{ $summary['personal_net'] >= 0 ? 'positive' : 'negative' }}">€{{ number_format($summary['personal_net'], 2) }}</td>
            </tr>
            <tr style="border-top: 2px solid #374151; background: #f9fafb;">
                <td><strong>Total</strong></td>
                <td class="amount positive"><strong>€{{ number_format($summary['total_incomes'], 2) }}</strong></td>
                <td class="amount">-</td>
                <td class="amount negative"><strong>€{{ number_format($summary['total_expenses'], 2) }}</strong></td>
                <td class="amount">-</td>
                <td class="amount {{ $summary['net_income'] >= 0 ? 'positive' : 'negative' }}"><strong>€{{ number_format($summary['net_income'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if(count($categories['incomes']) > 0)
        <div class="page-break"></div>
        <h3>Income by Category</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th>Business Amount</th>
                    <th>Personal Amount</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories['incomes'] as $category => $data)
                    <tr>
                        <td>{{ $category ?: 'Uncategorized' }}</td>
                        <td>{{ $data['count'] }}</td>
                        <td class="amount">€{{ number_format($data['business'], 2) }}</td>
                        <td class="amount">€{{ number_format($data['personal'], 2) }}</td>
                        <td class="amount positive">€{{ number_format($data['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(count($categories['expenses']) > 0)
        <h3 class="mt-20">Expenses by Category</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th>Business Amount</th>
                    <th>Personal Amount</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories['expenses'] as $category => $data)
                    <tr>
                        <td>{{ $category ?: 'Uncategorized' }}</td>
                        <td>{{ $data['count'] }}</td>
                        <td class="amount">€{{ number_format($data['business'], 2) }}</td>
                        <td class="amount">€{{ number_format($data['personal'], 2) }}</td>
                        <td class="amount negative">€{{ number_format($data['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="mt-20" style="background: #f0f9ff; padding: 15px; border-radius: 6px;">
        <h3 style="margin: 0 0 10px 0; color: #0369a1;">Financial Health Indicators</h3>
        
        @php
            $savingsRate = $summary['total_incomes'] > 0 ? ($summary['net_income'] / $summary['total_incomes']) * 100 : 0;
            $expenseRatio = $summary['total_incomes'] > 0 ? ($summary['total_expenses'] / $summary['total_incomes']) * 100 : 0;
        @endphp
        
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%; padding-right: 10px;">
                <div><strong>Savings Rate:</strong> {{ number_format($savingsRate, 1) }}%</div>
                <div><strong>Expense Ratio:</strong> {{ number_format($expenseRatio, 1) }}%</div>
            </div>
            <div style="display: table-cell; width: 50%; padding-left: 10px;">
                <div><strong>Business Profit Margin:</strong> 
                    @if($summary['business_incomes'] > 0)
                        {{ number_format(($summary['business_net'] / $summary['business_incomes']) * 100, 1) }}%
                    @else
                        N/A
                    @endif
                </div>
                <div><strong>Monthly Average Net:</strong> €{{ number_format($summary['net_income'] / 12, 2) }}</div>
            </div>
        </div>
    </div>

    @if($summary['net_income'] < 0)
        <div class="mt-20" style="background: #fef2f2; padding: 15px; border-radius: 6px; border-left: 4px solid #dc2626;">
            <h4 style="margin: 0 0 10px 0; color: #dc2626;">⚠️ Financial Alert</h4>
            <p style="margin: 0; font-size: 11px;">
                Your expenses exceed your income by €{{ number_format(abs($summary['net_income']), 2) }}. 
                Consider reviewing your spending patterns and identifying areas for cost reduction.
            </p>
        </div>
    @elseif($savingsRate < 10)
        <div class="mt-20" style="background: #fffbeb; padding: 15px; border-radius: 6px; border-left: 4px solid #f59e0b;">
            <h4 style="margin: 0 0 10px 0; color: #f59e0b;">💡 Savings Opportunity</h4>
            <p style="margin: 0; font-size: 11px;">
                Your current savings rate is {{ number_format($savingsRate, 1) }}%. 
                Consider aiming for at least 10-20% to build a healthy financial cushion.
            </p>
        </div>
    @else
        <div class="mt-20" style="background: #f0fdf4; padding: 15px; border-radius: 6px; border-left: 4px solid #22c55e;">
            <h4 style="margin: 0 0 10px 0; color: #22c55e;">✅ Good Financial Health</h4>
            <p style="margin: 0; font-size: 11px;">
                Congratulations! You're maintaining a healthy savings rate of {{ number_format($savingsRate, 1) }}%. 
                Keep up the good financial habits.
            </p>
        </div>
    @endif
@endsection
