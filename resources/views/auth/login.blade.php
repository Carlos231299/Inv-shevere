<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8B0000; /* Dark Red */
            --text-color: #333;
        }
        body {
            background-image: url('{{ asset("images/login-bg.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        /* Overlay to improve text readability on background */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4); /* Darken background slightly */
            z-index: -1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95); /* High opacity white */
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 400px;
            text-align: center;
            backdrop-filter: blur(5px);
            border-top: 5px solid var(--primary-color);
        }
        
        .logo-container {
            margin-bottom: 20px;
        }
        .logo-container img {
            max-height: 80px;
        }

        h1 { 
            color: var(--primary-color); 
            margin-bottom: 10px; 
            font-weight: 700;
        }
        
        p.subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .form-group { 
            margin-bottom: 20px; 
            text-align: left; 
            position: relative;
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: var(--text-color); 
            font-weight: 500;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
        }

        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
            background: none;
            border: none;
            padding: 0;
            font-size: 1.2rem;
            user-select: none;
        }
        .toggle-password:hover {
            color: var(--primary-color);
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.1s;
            margin-top: 10px;
        }
        button:hover { 
            background-color: #660000; 
        }
        button:active {
            transform: scale(0.98);
        }

        .error { 
            color: #d32f2f; 
            font-size: 0.85em; 
            margin-top: 5px; 
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-container">
        <!-- Assuming logo.png exists, otherwise verify path -->
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} Logo">
    </div>
    <h1>Bienvenido</h1>
    <p class="subtitle">Ingresa tus credenciales para continuar</p>

    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" required autofocus value="{{ old('email') }}" placeholder="ejemplo@correo.com" autocomplete="off">
            @error('email')
                <div class="error">⚠️ {{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="off">
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>
        </div>

        <button type="submit">Ingresar al Sistema</button>
    </form>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.toggle-password');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.textContent = '🙈'; // Monkey covering eyes or slashed eye
        } else {
            passwordInput.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    }
</script>

</body>
</html>

