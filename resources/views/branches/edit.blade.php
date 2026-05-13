@extends('layouts.vali')

@section('title', 'Edit Branch')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5>Update Branch: {{ $branch->name }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('branches.update', $branch->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $branch->name }}" required>
                                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Location</label>
                                <input type="text" name="location" class="form-control" value="{{ $branch->location }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Contact Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ $branch->phone }}">
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Contact Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $branch->email }}">
                            </div>
                        </div>
                    </div>

                    <div class="text-right mt-4">
                        <a href="{{ route('branches.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="fa fa-save mr-2"></i> Update Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
