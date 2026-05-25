@extends('layouts.vali')

@section('title', 'Register New Supplier')

@section('page_icon', 'fa-truck')

@section('subtitle')
Fill in the supplier details below to add them to the system
@endsection

@section('styles')
<style>
    .tile {
        border-radius: 12px !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.04) !important;
        border: 0 !important;
        margin-bottom: 25px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 col-12">
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h3 class="tile-title mb-0 font-weight-bold" style="font-size: 18px;">
                    <i class="fa fa-truck text-primary mr-2"></i> SUPPLIER REGISTRATION FORM
                </h3>
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left mr-1"></i> Back to Suppliers
                </a>
            </div>

            <div class="p-4 bg-white">
                <form action="{{ route('suppliers.store') }}" method="POST" id="supplierForm">
                    @csrf

                    <div class="row">
                        {{-- Left Column --}}
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    Supplier Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}"
                                       placeholder="e.g. Kilimanjaro Supplies Ltd."
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Phone Number</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+255</span>
                                    </div>
                                    <input type="text"
                                           name="phone"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone') }}"
                                           placeholder="7XX XXX XXX"
                                           maxlength="9"
                                           oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,9)">
                                </div>
                                <small class="text-muted">Enter 9 digits. The +255 country code is added automatically.</small>
                                @error('phone')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Email Address</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                    </div>
                                    <input type="email"
                                           name="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}"
                                           placeholder="supplier@example.com">
                                </div>
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                        </div>

                        {{-- Right Column --}}
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="font-weight-bold">Service Offered</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-briefcase"></i></span>
                                    </div>
                                    <input type="text"
                                           name="service_offered"
                                           class="form-control @error('service_offered') is-invalid @enderror"
                                           value="{{ old('service_offered') }}"
                                           placeholder="e.g. Stationery & Office Equipment">
                                </div>
                                @error('service_offered')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Location / Address</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-map-marker"></i></span>
                                    </div>
                                    <input type="text"
                                           name="location"
                                           class="form-control @error('location') is-invalid @enderror"
                                           value="{{ old('location') }}"
                                           placeholder="e.g. Dar es Salaam, Tanzania">
                                </div>
                                @error('location')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Note / Additional Information</label>
                                <textarea name="note"
                                          id="supplier_note"
                                          class="form-control @error('note') is-invalid @enderror"
                                          rows="4"
                                          maxlength="2000"
                                          placeholder="Payment terms, contact preferences, delivery conditions, etc.">{{ old('note') }}</textarea>
                                <small class="text-muted"><span id="noteCount">0</span> / 2000 characters</small>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="alert alert-info mt-2">
                        <i class="fa fa-info-circle mr-2"></i>
                        <strong>Note:</strong> Fields marked with <span class="text-danger">*</span> are required.
                        All other fields are optional but recommended for complete supplier records.
                    </div>

                    <div class="text-right mt-3">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary mr-2">
                            <i class="fa fa-times mr-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-5" id="submitBtn">
                            <i class="fa fa-save mr-2"></i> Register Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Character counter for note
    const noteField = document.getElementById('supplier_note');
    const noteCount = document.getElementById('noteCount');
    if (noteField) {
        noteCount.textContent = noteField.value.length;
        noteField.addEventListener('input', function() {
            noteCount.textContent = this.value.length;
        });
    }

    // Prevent double submission
    document.getElementById('supplierForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> Saving...';
    });
</script>
@endsection
