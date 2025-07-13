@extends('pdf.layout')

@section('title', 'Expense Report - Rainlo')
@section('subtitle', 'Expense Report')

@section('content')
    <div class="summary-grid">
        <div class="summary-item negative">
            <div class="label">Total Expenses</div>
            <div class="value">€{{ number_format($summary['total_amount'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Business Expenses</div>
            <div class="value">€{{ number_format($summary['business_expenses'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Personal Expenses</div>
            <div class="value">€{{ number_format($summary['personal_expenses'], 2) }}</div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Total Records</div>
            <div class="value">{{ $summary['total_count'] }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Business Records</div>
            <div class="value">{{ $summary['business_count'] }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Personal Records</div>
            <div class="value">{{ $summary['personal_count'] }}</div>
        </div>
    </div>

    @if($expenses->count() > 0)
        <h3>Expense Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Vendor</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $expense)
                    <tr class="{{ $expense->is_business ? 'business' : 'personal' }}">
                        <td>{{ $expense->date->format('M j, Y') }}</td>
                        <td>{{ $expense->description ?: '-' }}</td>
                        <td>{{ $expense->category ?: '-' }}</td>
                        <td>{{ $expense->vendor ?: '-' }}</td>
                        <td>{{ $expense->is_business ? 'Business' : 'Personal' }}</td>
                        <td class="amount negative">€{{ number_format($expense->amount, 2) }}</td>
                        <td class="text-center">{{ $expense->receipt_url ? '✓' : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($expenses->groupBy('category')->count() > 1)
            <div class="page-break"></div>
            <h3>Expense Summary by Category</h3>
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
                    @foreach($expenses->groupBy('category') as $category => $categoryExpenses)
                        <tr>
                            <td>{{ $category ?: 'Uncategorized' }}</td>
                            <td>{{ $categoryExpenses->count() }}</td>
                            <td class="amount">€{{ number_format($categoryExpenses->where('is_business', true)->sum('amount'), 2) }}</td>
                            <td class="amount">€{{ number_format($categoryExpenses->where('is_business', false)->sum('amount'), 2) }}</td>
                            <td class="amount negative">€{{ number_format($categoryExpenses->sum('amount'), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($expenses->where('is_business', true)->count() > 0)
            <h3 class="mt-20">Tax Deductible Business Expenses</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Amount</th>
                        <th>Tax Deductible</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses->where('is_business', true) as $expense)
                        <tr class="business">
                            <td>{{ $expense->date->format('M j, Y') }}</td>
                            <td>{{ $expense->description ?: '-' }}</td>
                            <td>{{ $expense->category ?: '-' }}</td>
                            <td>{{ $expense->vendor ?: '-' }}</td>
                            <td class="amount negative">€{{ number_format($expense->amount, 2) }}</td>
                            <td class="text-center">{{ $expense->tax_deductible ? '✓' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <div class="text-center mt-20">
            <h3>No expenses found for the selected criteria</h3>
            <p>Try adjusting your filters to see more results.</p>
        </div>
    @endif
@endsection
