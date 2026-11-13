<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #1a1a1a;
    background: #fff;
}

/* ===== HEADER ===== */
.header {
    background: #1a1a1a;
    color: #fff;
    padding: 22px 30px;
    margin-bottom: 0;
}

.header-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.shop-name {
    font-size: 26px;
    font-weight: 900;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #fff;
}

.shop-tagline {
    font-size: 9px;
    letter-spacing: 2px;
    color: #aaa;
    margin-top: 3px;
    text-transform: uppercase;
}

.report-meta {
    text-align: right;
}

.report-title {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.report-date {
    font-size: 9px;
    color: #bbb;
    margin-top: 4px;
}

/* ===== ACCENT BAR ===== */
.accent-bar {
    background: linear-gradient(to right, #e0b44a, #c8962e);
    height: 4px;
}

/* ===== SUMMARY CARDS ===== */
.summary-section {
    padding: 18px 30px;
    background: #f9f9f9;
    border-bottom: 1px solid #e5e5e5;
}

.summary-title {
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #999;
    margin-bottom: 12px;
    font-weight: 700;
}

.cards {
    display: flex;
    gap: 12px;
}

.card {
    flex: 1;
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    padding: 12px 14px;
    text-align: center;
    border-top: 3px solid #1a1a1a;
}

.card.gold  { border-top-color: #c8962e; }
.card.green { border-top-color: #27ae60; }
.card.red   { border-top-color: #c0392b; }
.card.amber { border-top-color: #e67e22; }

.card-value {
    font-size: 20px;
    font-weight: 900;
    color: #1a1a1a;
    line-height: 1;
}

.card-label {
    font-size: 8px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #999;
    margin-top: 5px;
}

/* ===== TABLE SECTION ===== */
.table-section {
    padding: 20px 30px 30px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.section-title {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    font-weight: 700;
    color: #1a1a1a;
}

.section-count {
    font-size: 9px;
    color: #999;
    background: #f0f0f0;
    padding: 3px 10px;
    border-radius: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}

thead tr {
    background: #1a1a1a;
    color: #fff;
}

thead th {
    padding: 9px 10px;
    text-align: left;
    font-size: 8px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 700;
}

tbody tr {
    border-bottom: 1px solid #f0f0f0;
}

tbody tr:nth-child(even) {
    background: #fafafa;
}

tbody tr:hover {
    background: #f5f5f5;
}

tbody td {
    padding: 8px 10px;
    color: #333;
    vertical-align: middle;
}

/* Status badges */
.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.badge-approved  { background: #d4edda; color: #155724; }
.badge-pending   { background: #fff3cd; color: #856404; }
.badge-cancelled { background: #f8d7da; color: #721c24; }

/* Payment badge */
.pay-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 3px;
    font-size: 8px;
    font-weight: 600;
    background: #e8e8e8;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ID column */
.order-id {
    font-weight: 700;
    color: #1a1a1a;
}

/* Amount column */
.amount {
    font-weight: 700;
    color: #1a1a1a;
}

/* ===== FOOTER ===== */
.footer {
    background: #1a1a1a;
    color: #888;
    padding: 12px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 8px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.footer-brand {
    color: #fff;
    font-weight: 700;
    letter-spacing: 2px;
}

</style>
</head>
<body>

{{-- ===== HEADER ===== --}}
<div class="header">
    <div class="header-inner">
        <div>
            <div class="shop-name">Sheerlong Shop</div>
            <div class="shop-tagline">Premium Tech &amp; Computer Store</div>
        </div>
        <div class="report-meta">
            <div class="report-title">Order Report</div>
            <div class="report-date">Generated: {{ date('d M Y, H:i') }}</div>
        </div>
    </div>
</div>
<div class="accent-bar"></div>

{{-- ===== SUMMARY CARDS ===== --}}
<div class="summary-section">
    <div class="summary-title">Report Summary</div>
    <div class="cards">
        <div class="card">
            <div class="card-value">{{ $totalOrders }}</div>
            <div class="card-label">Total Orders</div>
        </div>
        <div class="card gold">
            <div class="card-value">${{ number_format($totalRevenue, 2) }}</div>
            <div class="card-label">Total Revenue</div>
        </div>
        <div class="card green">
            <div class="card-value">{{ $orders->where('status','approved')->count() }}</div>
            <div class="card-label">Approved</div>
        </div>
        <div class="card amber">
            <div class="card-value">{{ $orders->where('status','pending')->count() }}</div>
            <div class="card-label">Pending</div>
        </div>
        <div class="card red">
            <div class="card-value">{{ $orders->where('status','cancelled')->count() }}</div>
            <div class="card-label">Cancelled</div>
        </div>
    </div>
</div>

{{-- ===== ORDER TABLE ===== --}}
<div class="table-section">
    <div class="section-header">
        <div class="section-title">Order Details</div>
        <div class="section-count">{{ $totalOrders }} records</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Buyer Name</th>
                <th>Buyer Phone</th>
                <th>Address</th>
                <th>Payment</th>
                <th>Total</th>
                <th>Delivery Fee</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $o)
            <tr>
                <td><span class="order-id">#{{ $o->id }}</span></td>
                <td>{{ $o->buyer_name }}</td>
                <td>{{ $o->buyer_phone }}</td>
                <td>{{ $o->address }}</td>
                <td><span class="pay-badge">{{ $o->payment_method }}</span></td>
                <td><span class="amount">${{ number_format($o->total, 2) }}</span></td>
                <td>${{ number_format($o->delivery_fee ?? 0, 2) }}</td>
                <td>
                    @if($o->status == 'approved')
                        <span class="badge badge-approved">Approved</span>
                    @elseif($o->status == 'pending')
                        <span class="badge badge-pending">Pending</span>
                    @elseif($o->status == 'cancelled')
                        <span class="badge badge-cancelled">Cancelled</span>
                    @else
                        <span class="badge">{{ $o->status }}</span>
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($o->created_at)->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ===== FOOTER ===== --}}
<div class="footer">
    <div class="footer-brand">Sheerlong Shop</div>
    <div>Confidential &mdash; Internal Use Only</div>
    <div>{{ date('Y') }} &copy; All Rights Reserved</div>
</div>

</body>
</html>