@extends('layouts.admin')

@section('title', 'My Profile')
@section('page_title', 'User Profile & Signature')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profile Information</h3>
                </div>

                <div class="card-body">

                    <table class="table table-sm">
                        <tr>
                            <th style="width: 200px;">Name:</th>
                            <td>{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <th>Username:</th>
                            <td>{{ $user->username }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <th>Role:</th>
                            <td>
                                @php
                                    $roles = $user->roleList();
                                    $roleStyles = [
                                        'super_admin' => 'badge-dark',
                                        'admin' => 'badge-danger',
                                        'director' => 'badge-dark',
                                        'manager' => 'badge-warning',
                                        'purchasing' => 'badge-info',
                                        'warehouse' => 'badge-warning',
                                        'staff' => 'badge-primary',
                                        'audit' => 'badge-success',
                                    ];
                                @endphp
                                @if (empty($roles))
                                    <span class="badge badge-secondary">No Role</span>
                                @else
                                    @foreach ($roles as $role)
                                        <span class="badge {{ $roleStyles[$role] ?? 'badge-secondary' }} mr-1">
                                            {{ ucwords(str_replace('_', ' ', $role)) }}
                                        </span>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lock mr-1"></i> Change Password</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.changePassword') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                placeholder="Enter current password" autocomplete="current-password">
                            @error('current_password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" name="password" id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Minimum 8 characters" autocomplete="new-password">
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control" placeholder="Repeat new password" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key mr-1"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Digital Signature</h3>
                </div>

                <div class="card-body">
                    <p class="text-muted">Upload a signature image (PNG or JPG) that will be automatically applied to your
                        approved documentation.</p>

                    @if ($user->signature_path && Storage::disk('public')->exists($user->signature_path))
                        <div class="mb-3">
                            <label class="d-block mb-2"><strong>Current Signature:</strong></label>
                            <div class="border p-3" style="max-width: 400px;">
                                <img src="{{ route('users.signature', $user) }}?v={{ optional($user->updated_at)->timestamp }}"
                                    alt="User Signature" style="max-width: 100%; max-height: 150px;">
                            </div>
                        </div>
                    @else
                        <p class="text-danger mb-3"><i class="fas fa-exclamation-circle"></i> No signature uploaded yet.
                            Please upload a signature to proceed with approvals.</p>
                    @endif

                    <form action="{{ route('users.updateSignature') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="signature">Upload Signature Image</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-image"></i></span>
                                </div>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('signature') is-invalid @enderror"
                                        id="signature" name="signature" accept="image/png,image/jpeg" required>
                                    <label class="custom-file-label" for="signature">Choose Image (PNG or JPG, Max
                                        5MB)</label>
                                </div>
                            </div>
                            <small class="form-text text-muted">Recommended: Transparent PNG background for best appearance
                                on documents</small>
                            @error('signature')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Signature
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Help</h3>
                </div>
                <div class="card-body">
                    <p><strong>What is a Digital Signature?</strong></p>
                    <p>A digital signature is an image that will be automatically applied to PPB/PPJ and purchase orders
                        when you create or approve them.</p>

                    <p><strong>Tips:</strong></p>
                    <ul>
                        <li>Use a transparent PNG for best results</li>
                        <li>Keep image size under 5MB</li>
                        <li>Recommended dimensions: 200x100 pixels</li>
                        <li>You can update it anytime</li>
                    </ul>

                    <p><strong>When is it used?</strong></p>
                    <ul>
                        <li><strong>PR:</strong> On creation and approval</li>
                        <li><strong>PO:</strong> On creation and approval</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle file input label update
        document.getElementById('signature').addEventListener('change', function(e) {
            const label = this.nextElementSibling;
            label.textContent = e.target.files[0]?.name || 'Choose Image (PNG or JPG, Max 5MB)';
        });
    </script>
@endsection
