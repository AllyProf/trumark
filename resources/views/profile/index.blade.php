@extends('layouts.vali')

@section('title', 'My Profile')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger shadow-sm border-0 mb-4">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <!-- Profile Sidebar -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Profile Photo</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-4">
                    @if($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" class="img-radius" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #f4f7fa;">
                    @else
                        <div class="img-radius bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; font-size: 60px; font-weight: 800; color: #940000; border: 4px solid #f4f7fa;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <h4 class="f-w-600 mb-1">{{ $user->name }}</h4>
                <p class="text-muted text-uppercase mb-4" style="font-size: 11px; letter-spacing: 1px;">{{ str_replace('_', ' ', $user->role) }}</p>
                
                <form id="avatarForm" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="custom-file text-left">
                        <input type="file" name="avatar" class="custom-file-input" id="avatarInput" onchange="this.form.submit()">
                        <label class="custom-file-label" for="avatarInput">Update Photo</label>
                    </div>
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    <input type="hidden" name="phone" value="{{ $user->phone }}">
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Details -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Account Details & Security</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="mb-3 font-weight-bold text-primary">Personal Information</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Email Address</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="mb-3 font-weight-bold text-primary">Change Password</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">New Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Leave blank to stay current">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Confirm New Password</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Re-type new password">
                            </div>
                        </div>
                    </div>

                    <div class="text-right mt-4">
                        <button type="submit" class="btn btn-primary px-5 py-2 font-weight-bold">
                            <i class="fa fa-save mr-2"></i> Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
