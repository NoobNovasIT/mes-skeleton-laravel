{{--
    = Areas/Warehouse/Views/MaterialReceipt/Save.cshtml - dùng chung Create + Edit.
    Mở URL trực tiếp thì là form POST bình thường; mở bằng data-mes-dialog thì mes-ui.js tự submit AJAX.
--}}
@extends(mes_layout())
@section('dialogSize', 'md')

@php
    $isReadOnly = ! \App\Http\Permissions\PagePermission::current()->canWrite();
    $formAction = $materialReceiptSaveViewModel->isCreateMode()
        ? route('warehouse.material-receipt.create')
        : route('warehouse.material-receipt.edit', $materialReceiptSaveViewModel->MaterialReceiptId);
@endphp

@section('content')
    <div class="row">
        <div class="{{ request()->isDialogRequest() ? 'col-12' : 'col-lg-7 col-xl-6' }}">
            <div class="mes-card">
                <form method="post" action="{{ $formAction }}" novalidate>
                    @csrf
                    <div class="mes-card-body">
                        @include('partials.validation-summary')

                        <div class="mb-3">
                            <label for="ReceiptCode" class="form-label">{{ localize('Receipt code') }}</label>
                            <input id="ReceiptCode" name="ReceiptCode" value="{{ $materialReceiptSaveViewModel->ReceiptCode }}" class="form-control text-uppercase" @readonly($isReadOnly) />
                            <div class="form-text">{{ localize('Example: RC-0004') }}</div>
                            @include('partials.field-error', ['field' => 'ReceiptCode'])
                        </div>

                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label for="MaterialCode" class="form-label">{{ localize('Material code') }}</label>
                                <input id="MaterialCode" name="MaterialCode" value="{{ $materialReceiptSaveViewModel->MaterialCode }}" class="form-control text-uppercase" @readonly($isReadOnly) />
                                @include('partials.field-error', ['field' => 'MaterialCode'])
                            </div>
                            <div class="col-md-7 mb-3">
                                <label for="MaterialName" class="form-label">{{ localize('Material name') }}</label>
                                <input id="MaterialName" name="MaterialName" value="{{ $materialReceiptSaveViewModel->MaterialName }}" class="form-control" @readonly($isReadOnly) />
                                @include('partials.field-error', ['field' => 'MaterialName'])
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label for="ReceivedQuantity" class="form-label">{{ localize('Received quantity') }}</label>
                                <input id="ReceivedQuantity" name="ReceivedQuantity" value="{{ $materialReceiptSaveViewModel->ReceivedQuantity }}" class="form-control" type="number" step="0.001" @readonly($isReadOnly) />
                                @include('partials.field-error', ['field' => 'ReceivedQuantity'])
                            </div>
                            <div class="col-md-7 mb-3">
                                <label for="ReceivedAt" class="form-label">{{ localize('Received at') }}</label>
                                <input id="ReceivedAt" name="ReceivedAt" value="{{ $materialReceiptSaveViewModel->ReceivedAt }}" class="form-control" type="datetime-local" @readonly($isReadOnly) />
                                @include('partials.field-error', ['field' => 'ReceivedAt'])
                            </div>
                        </div>

                        <div class="mb-1">
                            <label for="Note" class="form-label">{{ localize('Note') }}</label>
                            <textarea id="Note" name="Note" class="form-control" rows="2" @readonly($isReadOnly)>{{ $materialReceiptSaveViewModel->Note }}</textarea>
                            @include('partials.field-error', ['field' => 'Note'])
                        </div>
                    </div>
                    <div class="mes-card-footer">
                        @requireAccess('FullAccess')
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}
                            </button>
                        @endrequireAccess
                        <a class="btn btn-light" href="{{ route('warehouse.material-receipt.index') }}" data-mes-dialog-close>{{ localize('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
