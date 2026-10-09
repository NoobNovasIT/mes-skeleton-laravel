{{--
    = Areas/Warehouse/Views/MaterialReceipt/Index.cshtml. $materialReceipts: list<MaterialReceiptDto>
    Vùng data-mes-refresh-region tự tải lại sau khi lưu / xóa thành công, không reload cả trang.
--}}
@extends('layouts.app')

@section('content')
    <div data-mes-refresh-region="material-receipt-list">
        <div class="mes-card">
            <div class="mes-card-header">
                <h3 class="mes-card-title">{{ localize('Material receipts') }}</h3>
                <div class="mes-card-actions">
                    @requireAccess('FullAccess')
                        <a class="btn btn-primary" href="{{ route('warehouse.material-receipt.create') }}" data-mes-dialog>
                            <i class="fa-solid fa-plus"></i> {{ localize('Create') }}
                        </a>
                    @endrequireAccess
                </div>
            </div>
            <div class="mes-card-body">
                @if ($materialReceipts === [])
                    <div class="mes-state">
                        <div class="mes-state-icon"><i class="fa-solid fa-inbox"></i></div>
                        <p class="text-body-secondary">{{ localize('No data yet.') }}</p>
                    </div>
                @else
                    <table class="table-hover table" data-mes-datatable>
                        <thead>
                            <tr>
                                <th>{{ localize('Receipt code') }}</th>
                                <th>{{ localize('Material code') }}</th>
                                <th>{{ localize('Material name') }}</th>
                                <th class="text-end">{{ localize('Received quantity') }}</th>
                                <th>{{ localize('Received at') }}</th>
                                <th>{{ localize('Note') }}</th>
                                <th data-orderable="false"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materialReceipts as $materialReceipt)
                                <tr>
                                    <td class="fw-medium"><code>{{ $materialReceipt->receiptCode }}</code></td>
                                    <td>{{ $materialReceipt->materialCode }}</td>
                                    <td>{{ $materialReceipt->materialName }}</td>
                                    <td class="text-end">{{ number_format((float) $materialReceipt->receivedQuantity, 3) }}</td>
                                    <td>{{ mes_date($materialReceipt->receivedAt) }}</td>
                                    <td class="text-body-secondary">{{ $materialReceipt->note }}</td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-light btn-sm" href="{{ route('warehouse.material-receipt.edit', $materialReceipt->materialReceiptId) }}"
                                           data-mes-dialog title="{{ localize('Edit') }}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        @requireAccess('FullAccess')
                                            <form method="post" action="{{ route('warehouse.material-receipt.delete', $materialReceipt->materialReceiptId) }}" class="d-inline"
                                                  data-mes-ajax-form
                                                  data-mes-confirm="{{ localize('Delete receipt {0}?', $materialReceipt->receiptCode) }}" data-mes-danger>
                                                @csrf
                                                <button type="submit" class="btn btn-light btn-sm text-danger" title="{{ localize('Delete') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endrequireAccess
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
