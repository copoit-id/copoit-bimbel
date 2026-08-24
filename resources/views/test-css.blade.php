<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Test</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    @vite('resources/css/app.css')
    <style>
        /* Debug: Override untuk testing */
        :root {
            --client-color-primary: #ff0000;
        }
    </style>
</head>
<body class="p-10 space-y-4">
    <h1 class="text-2xl font-bold mb-6">CSS Test - Dynamic Colors</h1>
    
    <div class="space-y-4">
        <div class="bg-primary text-white p-4 rounded">
            bg-primary dengan text-white (harus merah karena --client-color-primary: #ff0000)
        </div>
        
        <div class="bg-blue-500 text-white p-4 rounded">
            bg-blue-500 (Tailwind default - harus biru)
        </div>
        
        <div class="bg-green text-white p-4 rounded">
            bg-green (custom color)
        </div>
        
        <div class="bg-red text-white p-4 rounded">
            bg-red (custom color)
        </div>
        
        <div class="text-primary p-4 border border-primary rounded">
            text-primary dengan border-primary (harus merah)
        </div>
    </div>
    
    <div class="mt-8 p-4 bg-gray-100 rounded">
        <h2 class="font-bold mb-2">Debug Info:</h2>
        <pre id="debug"></pre>
    </div>
    
    <script>
        // Debug CSS variables
        const style = getComputedStyle(document.documentElement);
        const debug = document.getElementById('debug');
        debug.textContent = `
--color-primary: ${style.getPropertyValue('--color-primary')}
--client-color-primary: ${style.getPropertyValue('--client-color-primary')}
--color-green: ${style.getPropertyValue('--color-green')}
--color-red: ${style.getPropertyValue('--color-red')}
        `.trim();
    </script>
</body>
</html>
