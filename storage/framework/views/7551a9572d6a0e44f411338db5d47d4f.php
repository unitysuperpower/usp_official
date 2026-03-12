<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Diagnostics</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Chat System Diagnostics</h1>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">WebSocket Connection Test</h2>
            <div id="connection-status" class="mb-4">
                <span class="text-gray-600">Checking connection...</span>
            </div>
            <button onclick="testConnection()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Test Connection
            </button>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Broadcasting Configuration</h2>
            <pre class="bg-gray-100 p-4 rounded overflow-x-auto"><code><?php echo e(json_encode([
                'connection' => config('broadcasting.default'),
                'reverb_key' => config('broadcasting.connections.reverb.key'),
                'reverb_host' => config('broadcasting.connections.reverb.options.host'),
                'reverb_port' => config('broadcasting.connections.reverb.options.port'),
                'reverb_scheme' => config('broadcasting.connections.reverb.options.scheme'),
            ], JSON_PRETTY_PRINT)); ?></code></pre>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Environment Variables</h2>
            <pre class="bg-gray-100 p-4 rounded overflow-x-auto"><code><?php echo e(json_encode([
                'BROADCAST_CONNECTION' => env('BROADCAST_CONNECTION'),
                'REVERB_APP_ID' => env('REVERB_APP_ID'),
                'REVERB_APP_KEY' => env('REVERB_APP_KEY'),
                'REVERB_HOST' => env('REVERB_HOST'),
                'REVERB_PORT' => env('REVERB_PORT'),
                'REVERB_SCHEME' => env('REVERB_SCHEME'),
            ], JSON_PRETTY_PRINT)); ?></code></pre>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Browser Console</h2>
            <div id="console-log" class="bg-gray-900 text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto">
                <!-- Console output will appear here -->
            </div>
        </div>
    </div>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>
    
    <script>
        // Intercept console.log
        const consoleLog = document.getElementById('console-log');
        const originalLog = console.log;
        const originalError = console.error;
        
        function addToConsole(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'text-red-400' : 'text-green-400';
            const entry = document.createElement('div');
            entry.className = color;
            entry.textContent = `[${timestamp}] ${message}`;
            consoleLog.appendChild(entry);
            consoleLog.scrollTop = consoleLog.scrollHeight;
        }
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            addToConsole(args.join(' '), 'log');
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            addToConsole(args.join(' '), 'error');
        };

        function testConnection() {
            const statusDiv = document.getElementById('connection-status');
            statusDiv.innerHTML = '<span class="text-yellow-600">Testing connection...</span>';
            
            console.log('Testing WebSocket connection...');
            console.log('Echo available:', typeof window.Echo !== 'undefined');
            
            if (window.Echo) {
                console.log('Echo configuration:', {
                    broadcaster: 'reverb',
                    key: '<?php echo e(config("broadcasting.connections.reverb.key")); ?>',
                    host: '<?php echo e(config("broadcasting.connections.reverb.options.host")); ?>',
                    port: <?php echo e(config("broadcasting.connections.reverb.options.port")); ?>,
                });
                
                // Try to connect to a test channel
                const testChannel = window.Echo.private('test-channel');
                
                testChannel.subscribed(() => {
                    console.log('✓ Successfully connected to test channel');
                    statusDiv.innerHTML = '<span class="text-green-600">✓ WebSocket connection successful!</span>';
                });
                
                testChannel.error((error) => {
                    console.error('✗ Connection error:', error);
                    statusDiv.innerHTML = '<span class="text-red-600">✗ Connection failed. Check console for details.</span>';
                });
                
                setTimeout(() => {
                    window.Echo.leave('test-channel');
                }, 3000);
            } else {
                console.error('✗ Laravel Echo not initialized');
                statusDiv.innerHTML = '<span class="text-red-600">✗ Laravel Echo not initialized</span>';
            }
        }

        // Auto-test on page load
        setTimeout(() => {
            console.log('Page loaded, checking Echo...');
            if (window.Echo) {
                console.log('✓ Laravel Echo is available');
                console.log('Echo connector:', window.Echo.connector);
            } else {
                console.error('✗ Laravel Echo is NOT available');
            }
        }, 1500);
    </script>
</body>
</html>
<?php /**PATH D:\usp_official\resources\views\chat\test.blade.php ENDPATH**/ ?>