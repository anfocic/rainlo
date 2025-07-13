<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Rainlo Financial Report')</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #4f46e5;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        
        .header .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin: 5px 0;
        }
        
        .user-info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .user-info h3 {
            margin: 0 0 10px 0;
            color: #374151;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .summary-item {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .summary-item:first-child {
            border-radius: 8px 0 0 8px;
        }
        
        .summary-item:last-child {
            border-radius: 0 8px 8px 0;
        }
        
        .summary-item .label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .summary-item .value {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
        
        .summary-item.positive .value {
            color: #059669;
        }
        
        .summary-item.negative .value {
            color: #dc2626;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        td {
            font-size: 11px;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .amount.positive {
            color: #059669;
        }
        
        .amount.negative {
            color: #dc2626;
        }
        
        .business {
            background: #dbeafe;
        }
        
        .personal {
            background: #fef3c7;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
        
        .filters {
            background: #f0f9ff;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .filters strong {
            color: #0369a1;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .mb-10 {
            margin-bottom: 10px;
        }
        
        .mb-20 {
            margin-bottom: 20px;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rainlo</h1>
        <div class="subtitle">@yield('subtitle', 'Financial Report')</div>
        <div class="subtitle">Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</div>
    </div>

    <div class="user-info">
        <h3>Report for: {{ $user->name }}</h3>
        <div>Email: {{ $user->email }}</div>
        @if(isset($period))
            <div>Period: {{ $period }}</div>
        @endif
    </div>

    @if(isset($filters) && array_filter($filters))
        <div class="filters">
            <strong>Applied Filters:</strong>
            @if($filters['date_from'])
                From: {{ \Carbon\Carbon::parse($filters['date_from'])->format('M j, Y') }}
            @endif
            @if($filters['date_to'])
                To: {{ \Carbon\Carbon::parse($filters['date_to'])->format('M j, Y') }}
            @endif
            @if($filters['category'])
                Category: {{ $filters['category'] }}
            @endif
            @if(isset($filters['is_business']) && $filters['is_business'] !== null)
                Type: {{ $filters['is_business'] ? 'Business Only' : 'Personal Only' }}
            @endif
        </div>
    @endif

    @yield('content')

    <div class="footer">
        <div>This report was generated by Rainlo Financial Management System</div>
        <div>{{ url('/') }} | Generated at {{ now()->format('Y-m-d H:i:s T') }}</div>
    </div>
</body>
</html>
