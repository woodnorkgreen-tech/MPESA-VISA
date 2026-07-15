<!DOCTYPE html>
<html lang="en" class="bg-gray-950">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login — Game Iko On</title>
  @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-950 flex items-center justify-center p-4">

  <div class="w-full max-w-sm">

    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-black text-safaricom mb-1">Game Iko On</h1>
      <p class="text-gray-500 text-sm">Admin Access</p>
    </div>

    <!-- Error -->
    @if($errors->has('credentials'))
      <div class="bg-red-900/40 border border-red-500 text-red-300 rounded-xl px-4 py-3 mb-4 text-sm text-center">
        {{ $errors->first('credentials') }}
      </div>
    @endif

    <!-- Login form -->
    <form method="POST" action="{{ route('admin.login') }}" class="bg-gray-900 rounded-2xl p-6 space-y-4">
      @csrf

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Username</label>
        <input type="text" name="username" value="{{ old('username') }}" required autofocus
          class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-safaricom" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-300 mb-1">Password</label>
        <input type="password" name="password" required
          class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-safaricom" />
      </div>

      <button type="submit"
        class="w-full bg-safaricom hover:bg-safaricom-dark text-white font-bold py-3 rounded-xl transition text-sm">
        Sign In →
      </button>
    </form>

  </div>

</body>
</html>
