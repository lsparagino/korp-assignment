<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
    </head>
    <body class="font-sans antialiased">
        <div id="app"></div>
        <!-- This is a placeholder. In a real scenario, you'd load your built client assets here -->
        <script type="module" src="http://localhost:5173/src/main.ts"></script>
    </body>
</html>
