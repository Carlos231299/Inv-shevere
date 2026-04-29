@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card" style="border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: none;">
                <div class="card-header" style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); color: white; border-radius: 20px 20px 0 0; padding: 20px 25px;">
                    <h4 class="mb-0" style="font-weight: 500;">Mi Perfil</h4>
                </div>

                <div class="card-body" style="padding: 30px;">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px;">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="name" class="form-label text-muted" style="font-size: 0.9rem;">Nombre Completo</label>
                                    <input id="name" type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" required autocomplete="off" autofocus style="border-radius: 10px; padding: 12px; background-color: #f8f9fa; border: 1px solid #e9ecef;">
                                    @error('name')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="email" class="form-label text-muted" style="font-size: 0.9rem;">Correo Electrónico</label>
                                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required autocomplete="off" style="border-radius: 10px; padding: 12px; background-color: #f8f9fa; border: 1px solid #e9ecef;">
                                    @error('email')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4" style="border-top: 1px solid #f0f0f0;">
                        <h5 class="mb-4 text-primary" style="font-weight: 600;">Cambiar Contraseña</h5>

                        <div class="form-group mb-3">
                            <label for="current_password" class="form-label text-muted" style="font-size: 0.9rem;">Contraseña Actual</label>
                            <input id="current_password" type="password" class="form-control" name="current_password" autocomplete="off" style="border-radius: 10px; padding: 12px;">
                            <small class="text-muted d-block mt-1">Necesaria para autorizar el cambio.</small>
                            @error('current_password')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="password" class="form-label text-muted" style="font-size: 0.9rem;">Nueva Contraseña</label>
                                    <input id="password" type="password" class="form-control" name="password" autocomplete="off" style="border-radius: 10px; padding: 12px;">
                                    @error('password')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="password-confirm" class="form-label text-muted" style="font-size: 0.9rem;">Confirmar Contraseña</label>
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" autocomplete="off" style="border-radius: 10px; padding: 12px;">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0 text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 30px; font-weight: 600; box-shadow: 0 4px 6px rgba(211, 47, 47, 0.2);">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>


        </div>
    </div>
</div>
@endsection
