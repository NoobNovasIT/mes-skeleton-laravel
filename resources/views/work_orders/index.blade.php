<!DOCTYPE html>
<html>
<head>
    <title>Work Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h3>Quản lý Work Order</h3>

    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <form method="POST" action="{{ route('work-orders.store') }}" class="row g-2 mb-4">
        @csrf
        <div class="col"><input name="wo_no" class="form-control" placeholder="WO No" value="{{ old('wo_no') }}"></div>
        <div class="col"><input name="model" class="form-control" placeholder="Model" value="{{ old('model') }}"></div>
        <div class="col"><input name="qty" type="number" class="form-control" placeholder="Qty" value="{{ old('qty') }}"></div>
        <div class="col"><button class="btn btn-primary">Thêm</button></div>
    </form>

    @foreach ($errors->all() as $err)
        <div class="text-danger">{{ $err }}</div>
    @endforeach

    <table class="table table-bordered">
        <tr><th>WO No</th><th>Model</th><th>Qty</th><th>Status</th><th>Ngày tạo</th><th></th></tr>
        @foreach ($orders as $o)
            <tr>
                <td>{{ $o->wo_no }}</td>
                <td>{{ $o->model }}</td>
                <td>{{ $o->qty }}</td>
                <td>{{ $o->status }}</td>
                <td>{{ $o->created_at }}</td>
                <td>
                    <form method="POST" action="{{ route('work-orders.destroy', $o) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
</body>
</html>
