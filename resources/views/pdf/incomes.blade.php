@extends('pdf.layout')

@section('title', 'Income Report - Rainlo')
@section('subtitle', 'Income Report')

@section('content')
    <div class="summary-grid">
        <div class="summary-item positive">
            <div class="label">Total Income</div>
            <div class="value">€{{ number_format($summary['total_amount'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Business Income</div>
            <div class="value">€{{ number_format($summary['business_incomes'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Personal Income</div>
            <div class="value">€{{ number_format($summary['personal_incomes'], 2) }}</div>
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

    @if($incomes->count() > 0)
        <h3>Income Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Source</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Recurring</th>
                </tr>
            </thead>
            <tbody>
                @foreach($incomes as $income)
                    <tr class="{{ $income->is_business ? 'business' : 'personal' }}">
                        <td>{{ $income->date->format('M j, Y') }}</td>
                        <td>{{ $income->description ?: '-' }}</td>
                        <td>{{ $income->category ?: '-' }}</td>
                        <td>{{ $income->source ?: '-' }}</td>
                        <td>{{ $income->is_business ? 'Business' : 'Personal' }}</td>
                        <td class="amount positive">€{{ number_format($income->amount, 2) }}</td>
                        <td class="text-center">{{ $income->recurring ? '✓' : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($incomes->groupBy('category')->count() > 1)
            <div class="page-break"></div>
            <h3>Income Summary by Category</h3>
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
                    @foreach($incomes->groupBy('category') as $category => $categoryIncomes)
                        <tr>
                            <td>{{ $category ?: 'Uncategorized' }}</td>
                            <td>{{ $categoryIncomes->count() }}</td>
                            <td class="amount">€{{ number_format($categoryIncomes->where('is_business', true)->sum('amount'), 2) }}</td>
                            <td class="amount">€{{ number_format($categoryIncomes->where('is_business', false)->sum('amount'), 2) }}</td>
                            <td class="amount positive">€{{ number_format($categoryIncomes->sum('amount'), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($incomes->where('recurring', true)->count() > 0)
            <h3 class="mt-20">Recurring Income Sources</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Source</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Last Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incomes->where('recurring', true)->groupBy('description') as $description => $recurringIncomes)
                        @php
                            $latest = $recurringIncomes->sortByDesc('date')->first();
                        @endphp
                        <tr class="{{ $latest->is_business ? 'business' : 'personal' }}">
                            <td>{{ $description ?: 'Recurring Income' }}</td>
                            <td>{{ $latest->category ?: '-' }}</td>
                            <td>{{ $latest->source ?: '-' }}</td>
                            <td>{{ $latest->is_business ? 'Business' : 'Personal' }}</td>
                            <td class="amount positive">€{{ number_format($latest->amount, 2) }}</td>
                            <td>{{ $latest->date->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <div class="text-center mt-20">
            <h3>No income found for the selected criteria</h3>
            <p>Try adjusting your filters to see more results.</p>
        </div>
    @endif
@endsection
