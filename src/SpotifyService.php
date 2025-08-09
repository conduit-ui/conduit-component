<?php

namespace ConduitComponents\Spotify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\select;

class SpotifyService
{
    private Client $httpClient;
    private ?string $accessToken = null;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => 'https://api.spotify.com/v1/',
            'timeout' => 30,
        ]);
        
        // Load configuration from multiple sources
        $this->loadConfiguration();
        
        // Load token from storage
        $this->loadAccessToken();
    }

    /**
     * Check if Spotify is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && $this->accessToken;
    }

    /**
     * Authenticate with Spotify using OAuth flow
     */
    public function login(): string
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            error('❌ Missing Spotify credentials');
            info('💡 Run "spotify setup" to get started');
            return "❌ Please set SPOTIFY_CLIENT_ID and SPOTIFY_CLIENT_SECRET environment variables";
        }

        // Try to find an available port
        $port = $this->findAvailablePort();
        $redirectUri = "http://127.0.0.1:{$port}/callback";
        $scopes = [
            'user-read-playback-state',
            'user-modify-playback-state',
            'user-read-currently-playing',
            'streaming',
            'playlist-read-private',
            'playlist-read-collaborative'
        ];

        // Generate authorization URL
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => bin2hex(random_bytes(16)),
        ];

        $authUrl = 'https://accounts.spotify.com/authorize?' . http_build_query($params);
        
        // Start local server for callback
        try {
            $server = $this->startCallbackServer($redirectUri);
        } catch (\Exception $e) {
            error('❌ Failed to start callback server: ' . $e->getMessage());
            return "❌ Authentication setup failed";
        }
        
        info('🎵 Spotify Authentication');
        info('🌐 Opening browser for authorization...');
        info('📋 Using redirect URI: ' . $redirectUri);
        
        if ($port !== 8888) {
            warning('⚠️  Using port ' . $port . ' because 8888 is in use');
            warning('⚠️  You must add this EXACT URI to your Spotify app:');
            warning('   ' . $redirectUri);
            info('');
            info('Or kill the process using port 8888:');
            info('   lsof -ti:8888 | xargs kill -9');
        }
        
        // Open browser
        $this->openUrl($authUrl);
        
        // Wait for callback
        $authCode = $this->waitForCallback($server);
        
        if (!$authCode) {
            error('❌ Authentication failed or cancelled');
            info('');
            info('💡 Common issues:');
            info('1. Redirect URI mismatch - check your Spotify app has EXACTLY:');
            info('   ' . $redirectUri);
            info('2. Port ' . ($parts['port'] ?? 8888) . ' might be in use');
            info('3. Browser popup might be blocked');
            return "❌ Authentication failed";
        }

        // Exchange code for tokens
        return $this->exchangeCodeForTokens($authCode, $redirectUri);
    }

    /**
     * Play a track, album, or playlist with smart device activation
     */
    public function play(?string $uri = null, ?string $device = null): string
    {
        if (!$this->accessToken) {
            return "❌ Not authenticated with Spotify. Run: spotify login";
        }

        $data = [];
        if ($uri) {
            if (str_contains($uri, 'spotify:track:')) {
                $data['uris'] = [$uri];
            } else {
                // If it's not a URI, search for it
                $searchResults = $this->search($uri, 'track', 1);
                if (empty($searchResults)) {
                    return "❌ No tracks found for: {$uri}";
                }
                $data['uris'] = [$searchResults[0]['uri']];
            }
        }

        $url = 'me/player/play';
        if ($device) {
            $url .= "?device_id={$device}";
        }

        try {
            $this->makeRequest('PUT', $url, $data);
            return $uri ? "🎵 Playing: {$uri}" : "▶️ Resuming playback";
        } catch (\Exception $e) {
            // Check if it's a "No active device" error
            if (str_contains($e->getMessage(), 'No active device found')) {
                return $this->handleNoActiveDevice($uri, $data);
            }
            return "❌ Failed to play: " . $e->getMessage();
        }
    }

    /**
     * Pause playback
     */
    public function pause(?string $device = null): string
    {
        if (!$this->accessToken) {
            return "❌ Not authenticated with Spotify";
        }

        $url = 'me/player/pause';
        if ($device) {
            $url .= "?device_id={$device}";
        }

        try {
            $this->makeRequest('PUT', $url);
            return "⏸️ Playback paused";
        } catch (\Exception $e) {
            return "❌ Failed to pause: " . $e->getMessage();
        }
    }

    /**
     * Skip to next track
     */
    public function next(?string $device = null): string
    {
        if (!$this->accessToken) {
            return "❌ Not authenticated with Spotify";
        }

        $url = 'me/player/next';
        if ($device) {
            $url .= "?device_id={$device}";
        }

        try {
            $this->makeRequest('POST', $url);
            return "⏭️ Skipped to next track";
        } catch (\Exception $e) {
            return "❌ Failed to skip: " . $e->getMessage();
        }
    }

    /**
     * Skip to previous track
     */
    public function previous(?string $device = null): string
    {
        if (!$this->accessToken) {
            return "❌ Not authenticated with Spotify";
        }

        $url = 'me/player/previous';
        if ($device) {
            $url .= "?device_id={$device}";
        }

        try {
            $this->makeRequest('POST', $url);
            return "⏮️ Skipped to previous track";
        } catch (\Exception $e) {
            return "❌ Failed to skip: " . $e->getMessage();
        }
    }

    /**
     * Get current playing track
     */
    public function current(): array
    {
        if (!$this->accessToken) {
            return ['error' => 'Not authenticated with Spotify'];
        }

        try {
            $response = $this->makeRequest('GET', 'me/player/currently-playing');
            
            if (!$response || !isset($response['item'])) {
                return ['message' => 'Nothing currently playing'];
            }

            $track = $response['item'];
            return [
                'name' => $track['name'],
                'artist' => $track['artists'][0]['name'] ?? 'Unknown',
                'album' => $track['album']['name'] ?? 'Unknown',
                'uri' => $track['uri'],
                'is_playing' => $response['is_playing'] ?? false,
                'progress' => $response['progress_ms'] ?? 0,
                'duration' => $track['duration_ms'] ?? 0,
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Search for tracks, albums, playlists
     */
    public function search(string $query, string $type = 'track', int $limit = 10): array
    {
        if (!$this->accessToken) {
            return ['error' => 'Not authenticated with Spotify'];
        }

        try {
            $response = $this->makeRequest('GET', 'search', [
                'q' => $query,
                'type' => $type,
                'limit' => $limit
            ]);

            $items = $response[$type . 's']['items'] ?? [];
            
            return array_map(function ($item) use ($type) {
                return [
                    'name' => $item['name'],
                    'uri' => $item['uri'],
                    'artist' => $type === 'track' ? $item['artists'][0]['name'] ?? 'Unknown' : null,
                    'album' => $type === 'track' ? $item['album']['name'] ?? 'Unknown' : null,
                ];
            }, $items);

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Set or get volume (supports relative changes)
     */
    public function volume($volume = null, ?string $device = null): string
    {
        if (!$this->accessToken) {
            return "❌ Not authenticated with Spotify";
        }

        // Get current volume first (we'll need it for relative changes)
        $currentVolume = 0;
        try {
            $player = $this->makeRequest('GET', 'me/player');
            if (!$player || !isset($player['device'])) {
                return "❌ No active playback device";
            }
            $currentVolume = $player['device']['volume_percent'] ?? 0;
        } catch (\Exception $e) {
            if ($volume === null) {
                return "❌ Failed to get volume: " . $e->getMessage();
            }
        }

        // If no volume specified, just show current
        if ($volume === null) {
            $volumeBar = $this->createVolumeBar($currentVolume);
            return "🔊 Current volume: {$currentVolume}%\n{$volumeBar}";
        }

        // Handle relative volume changes
        $targetVolume = $volume;
        if (is_string($volume)) {
            if (str_starts_with($volume, '+')) {
                $targetVolume = min(100, $currentVolume + (int)substr($volume, 1));
            } elseif (str_starts_with($volume, '-')) {
                $targetVolume = max(0, $currentVolume - (int)substr($volume, 1));
            } else {
                $targetVolume = (int)$volume;
            }
        }

        if ($targetVolume < 0 || $targetVolume > 100) {
            return "❌ Volume must be between 0 and 100";
        }

        $url = "me/player/volume?volume_percent={$targetVolume}";
        if ($device) {
            $url .= "&device_id={$device}";
        }

        try {
            $this->makeRequest('PUT', $url);
            $volumeBar = $this->createVolumeBar($targetVolume);
            
            // Show change if it was relative
            if (is_string($volume) && (str_starts_with($volume, '+') || str_starts_with($volume, '-'))) {
                $change = $targetVolume - $currentVolume;
                $changeStr = $change > 0 ? "+{$change}" : "{$change}";
                return "🔊 Volume: {$currentVolume}% → {$targetVolume}% ({$changeStr})\n{$volumeBar}";
            }
            
            return "🔊 Volume set to {$targetVolume}%\n{$volumeBar}";
        } catch (\Exception $e) {
            return "❌ Failed to set volume: " . $e->getMessage();
        }
    }

    private function createVolumeBar(int $volume): string
    {
        $filled = round($volume / 5); // 20 segments total
        $empty = 20 - $filled;
        
        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
        
        // Add volume indicator
        if ($volume == 0) {
            return "🔇 [{$bar}]";
        } elseif ($volume < 33) {
            return "🔈 [{$bar}]";
        } elseif ($volume < 66) {
            return "🔉 [{$bar}]";
        } else {
            return "🔊 [{$bar}]";
        }
    }

    /**
     * Interactive search and play
     */
    public function find(string $query): string
    {
        if (!$this->accessToken) {
            return "❌ Not authenticated with Spotify. Run: spotify login";
        }
        
        info("🔍 Searching for: {$query}");
        
        $results = $this->search($query, 'track', 10);
        
        if (isset($results['error'])) {
            return "❌ Search failed: {$results['error']}";
        }
        
        if (empty($results)) {
            return "❌ No results found for: {$query}";
        }
        
        // Build options for select
        $options = [];
        foreach ($results as $index => $track) {
            $num = str_pad($index + 1, 2, ' ', STR_PAD_LEFT);
            $options[$track['uri']] = "{$num}. 🎵 {$track['name']} - {$track['artist']}";
        }
        $options['cancel'] = '❌ Cancel';
        
        $selected = select(
            label: 'Select a track to play:',
            options: $options,
            default: array_key_first($options)
        );
        
        if ($selected === 'cancel') {
            return 'Cancelled.';
        }
        
        // Play the selected track
        return $this->play($selected);
    }

    /**
     * Get available devices
     */
    public function devices(): array
    {
        if (!$this->accessToken) {
            return ['error' => 'Not authenticated with Spotify'];
        }

        try {
            $response = $this->makeRequest('GET', 'me/player/devices');
            $devices = $response['devices'] ?? [];
            
            return array_map(function ($device) {
                return [
                    'id' => $device['id'],
                    'name' => $device['name'],
                    'type' => $device['type'],
                    'is_active' => $device['is_active'] ?? false,
                    'is_private_session' => $device['is_private_session'] ?? false,
                ];
            }, $devices);

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Interactive setup wizard using Laravel Prompts
     */
    public function setupInteractive(bool $reset = false): string
    {
        if ($reset) {
            return $this->handleReset();
        }

        // Check if already configured
        if ($this->hasCredentials()) {
            info('✅ Spotify is already configured');
            info('');
            info('Run: spotify current (to see what\'s playing)');
            info('Run: spotify login (if authentication expired)');
            info('Run: spotify setup --reset (to reconfigure)');
            return '';
        }

        $this->displayWelcome();

        if (!confirm('Ready to set up Spotify integration?', true)) {
            info('Setup cancelled.');
            return '';
        }

        return $this->executeSetupTasks();
    }

    private function hasCredentials(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    private function handleReset(): string
    {
        if (!confirm('This will remove your stored Spotify credentials. Continue?')) {
            info('Setup cancelled.');
            return '';
        }

        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
            unlink($envPath);
        }
        
        info('✅ Spotify credentials cleared');
        info('Run: spotify setup');
        
        return '';
    }

    private function displayWelcome(): void
    {
        info('🎵 Spotify Integration Setup');
        info('This will guide you through setting up your personal Spotify integration.');
        info('You\'ll need to create a Spotify app (takes 2 minutes).');
    }

    private function executeSetupTasks(): string
    {
        echo "\n";
        echo "🎵 Setting up Spotify Integration\n"; 
        echo "\n";

        $port = null;

        try {
            // Task 1: Determine callback port
            $this->runTask('Determining callback port', function () use (&$port) {
                $port = $this->findAvailablePort();
                return true;
            });

            // Task 2: Open Spotify Developer Dashboard
            $this->runTask('Opening Spotify Developer Dashboard', function () {
                $this->openUrl('https://developer.spotify.com/dashboard/applications');
                return true;
            });

            // Task 3: Display app configuration
            $this->runTask('Preparing app configuration', function () use ($port) {
                echo "\n";
                $this->displayAppConfiguration($port);
                return true;
            });

            // Task 4: Wait for app creation
            $this->runTask('Waiting for app creation', function () use ($port) {
                $redirectUri = "http://127.0.0.1:{$port}/callback";

                info('📋 Now create your Spotify app in the browser');
                info('Follow the 6 steps shown above');
                echo "\n";
                echo "📋 Quick Copy: Redirect URI\n";
                echo "   {$redirectUri}\n";
                echo "\n";

                // Try to copy to clipboard
                if ($this->copyToClipboard($redirectUri)) {
                    info('✅ Redirect URI copied to clipboard!');
                } else {
                    info('💡 Tip: Triple-click the green URL above to select it easily');
                }

                return confirm('✅ Have you created the app and are viewing its settings/dashboard page?', true);
            });

            // Task 5: Collect credentials
            $credentials = null;
            $this->runTask('Collecting app credentials', function () use (&$credentials) {
                try {
                    $credentials = $this->collectCredentials();
                    return true;
                } catch (\Exception $e) {
                    // User cancelled
                    return false;
                }
            });

            // Task 6: Validate credentials
            $this->runTask('Validating credentials', function () use ($credentials) {
                return $this->validateCredentials($credentials);
            });

            // Task 7: Store credentials
            $this->runTask('Storing credentials securely', function () use ($credentials) {
                $this->saveCredentials($credentials['client_id'], $credentials['client_secret']);
                
                // Reload credentials
                $_ENV['SPOTIFY_CLIENT_ID'] = $credentials['client_id'];
                $_ENV['SPOTIFY_CLIENT_SECRET'] = $credentials['client_secret'];
                $this->clientId = $credentials['client_id'];
                $this->clientSecret = $credentials['client_secret'];
                
                return true;
            });

            // Task 8: Test connection
            $this->runTask('Testing Spotify connection', function () use ($credentials) {
                return $this->testSpotifyConnection($credentials);
            });

            echo "\n";
            $this->displaySuccess();

            // Offer to start authentication immediately
            if (confirm('🔐 Would you like to authenticate with Spotify now?', true)) {
                echo "\n";
                info('🚀 Starting Spotify authentication...');
                
                $result = $this->login();
                if (str_contains($result, 'successful')) {
                    $this->loadAccessToken();
                    info('✅ Authentication complete!');
                } else {
                    warning('Authentication failed - run: spotify login');
                }
            }

            return '';

        } catch (\Exception $e) {
            error("❌ Setup failed: {$e->getMessage()}");
            info('You can try running the setup again');
            return '';
        }
    }

    private function runTask(string $description, callable $task): void
    {
        // Use simple echo for inline task output like the global version
        echo "  {$description}...";
        
        try {
            $result = $task();
            if ($result) {
                echo " ✓\n";
            } else {
                echo " ✗\n";
                throw new \Exception("Task failed: {$description}");
            }
        } catch (\Exception $e) {
            echo " ✗\n";
            throw $e;
        }
    }

    private function displayAppConfiguration(int $port): void
    {
        $username = $this->getSystemUsername();
        $appName = "Conduit CLI - {$username}";
        $redirectUri = "http://127.0.0.1:{$port}/callback";

        info('📋 Step-by-step Spotify app creation:');

        echo "\n";
        info('1. 📱 App Name: Enter any name you prefer (suggestion: "'.$appName.'")');
        info('2. 📝 App Description: Enter any description (suggestion: "Personal music control for development workflows")');
        info('3. 🌐 Website URL: Enter any URL (suggestion: "https://github.com/jordanpartridge/conduit")');

        echo "\n";
        echo "4. 🔗 REDIRECT URI - COPY THIS EXACTLY:\n";
        echo "   \033[32m{$redirectUri}\033[0m\n";
        echo "\n";

        info('5. 📡 Which APIs: Select "Web API" (✅ Web API only - no Web Playback SDK needed)');
        info('6. ✅ Accept Terms of Service and click "Save"');

        echo "\n";
        warning('⚠️  IMPORTANT: Must use 127.0.0.1 (not localhost) for security');
        info("💡 Using port {$port} for OAuth callback server");
    }

    private function collectCredentials(): ?array
    {
        info('🔑 App Credentials');
        info('In your Spotify app dashboard:');
        info('1. Copy your Client ID (visible by default)');
        info('2. Click "View client secret" and copy the secret');
        echo "\n";

        $clientId = text(
            label: '📋 Client ID',
            placeholder: 'Paste your Spotify app Client ID',
            required: true,
            validate: fn (string $value) => strlen($value) < 20
                ? 'Client ID appears to be too short'
                : null
        );

        $clientSecret = text(
            label: '🔐 Client Secret', 
            placeholder: 'Paste your Spotify app Client Secret',
            required: true,
            validate: fn (string $value) => strlen($value) < 20
                ? 'Client Secret appears to be too short'
                : null
        );

        return [
            'client_id' => trim($clientId),
            'client_secret' => trim($clientSecret),
        ];
    }

    private function validateCredentials(array $credentials): bool
    {
        // Basic validation
        if (strlen($credentials['client_id']) < 20) {
            throw new \Exception('Client ID appears to be invalid (too short)');
        }

        if (strlen($credentials['client_secret']) < 20) {
            throw new \Exception('Client Secret appears to be invalid (too short)');
        }

        // Pattern validation
        if (!preg_match('/^[a-zA-Z0-9]+$/', $credentials['client_id'])) {
            throw new \Exception('Client ID contains invalid characters');
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $credentials['client_secret'])) {
            throw new \Exception('Client Secret contains invalid characters');
        }

        return true;
    }

    private function testSpotifyConnection(array $credentials): bool
    {
        try {
            // Test client credentials flow (doesn't require user auth)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://accounts.spotify.com/api/token',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic '.base64_encode($credentials['client_id'].':'.$credentials['client_secret']),
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return isset($data['access_token']);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function displaySuccess(): void
    {
        info('🎉 Spotify integration setup complete!');

        info('🚀 What\'s next?');
        info('1. 🔐 spotify login (authenticate with Spotify)');
        info('2. 🎵 spotify current (see what\'s playing)');
        info('3. 🎧 spotify play "your favorite song" (start playing music)');

        info('💡 Pro Tips:');
        info('• All commands support full parameters and options');
        info('• Use spotify help to see all available commands');
        info('• Run spotify setup again to reconfigure if needed');
    }

    private function getSystemUsername(): string
    {
        return trim(shell_exec('whoami')) ?: 'Developer';
    }

    private function copyToClipboard(string $text): bool
    {
        $os = PHP_OS_FAMILY;

        try {
            switch ($os) {
                case 'Darwin': // macOS
                    shell_exec("echo '{$text}' | pbcopy");
                    return true;
                case 'Windows':
                    shell_exec("echo {$text} | clip");
                    return true;
                case 'Linux':
                    shell_exec("echo '{$text}' | xclip -selection clipboard");
                    return true;
            }
        } catch (\Exception $e) {
            // Clipboard copy failed
        }

        return false;
    }


    private function saveCredentials(string $clientId, string $clientSecret): void
    {
        info('💾 Saving your credentials...');
        
        // Try to save to a .env file in the component directory
        $envPath = dirname(__DIR__) . '/.env';
        $envContent = "SPOTIFY_CLIENT_ID=\"{$clientId}\"\n";
        $envContent .= "SPOTIFY_CLIENT_SECRET=\"{$clientSecret}\"\n";
        
        if (file_put_contents($envPath, $envContent)) {
            info("✅ Saved to {$envPath}");
        } else {
            info('📝 Add these to your shell profile:');
            info("export SPOTIFY_CLIENT_ID=\"{$clientId}\"");
            info("export SPOTIFY_CLIENT_SECRET=\"{$clientSecret}\"");
        }
    }

    private function testConnection(): void
    {
        info('🔍 Checking API connection...');
        
        try {
            $devices = $this->devices();
            if (isset($devices['error'])) {
                throw new \Exception($devices['error']);
            }
            
            info('✅ Connected successfully!');
            
            if (!empty($devices)) {
                info('📱 Found your devices:');
                foreach ($devices as $device) {
                    $icon = $device['is_active'] ? '🟢' : '⚪';
                    info("   {$icon} {$device['name']} ({$device['type']})");
                }
            } else {
                info('💡 No devices found - open Spotify on any device to see them here!');
            }
            
        } catch (\Exception $e) {
            warning("⚠️ Connection test: {$e->getMessage()}");
            info('💡 This is normal if Spotify isn\'t running - try opening it!');
        }
    }

    /**
     * Logout and clear stored tokens
     */
    public function logout(): string
    {
        $tokenFile = $this->getTokenFilePath();
        if (file_exists($tokenFile)) {
            unlink($tokenFile);
        }
        
        $this->accessToken = null;
        return "👋 Logged out from Spotify";
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): ?array
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ]
        ];

        if (!empty($data)) {
            if ($method === 'GET') {
                $options['query'] = $data;
            } else {
                $options['json'] = $data;
            }
        }

        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            $body = $response->getBody()->getContents();
            
            return $body ? json_decode($body, true) : null;
            
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 204) {
                return null; // No content is success for some endpoints
            }
            throw $e;
        }
    }

    private function startCallbackServer(string $redirectUri): array
    {
        $parts = parse_url($redirectUri);
        $port = $parts['port'] ?? 8888;
        $host = $parts['host'] ?? 'localhost';
        
        // Create socket
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            throw new \Exception('Failed to create socket: ' . socket_strerror(socket_last_error()));
        }
        
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        if (!socket_bind($socket, $host, $port)) {
            throw new \Exception('Failed to bind socket: ' . socket_strerror(socket_last_error()));
        }
        
        if (!socket_listen($socket, 5)) {
            throw new \Exception('Failed to listen on socket: ' . socket_strerror(socket_last_error()));
        }
        
        info("✅ Started temporary server on http://{$host}:{$port}");
        
        return [
            'socket' => $socket,
            'host' => $host, 
            'port' => $port
        ];
    }

    private function waitForCallback(array $server): ?string
    {
        info('⏳ Waiting for authorization...');
        info('💡 Complete the authorization in your browser');
        
        $socket = $server['socket'];
        $authCode = null;
        
        // Set timeout to 5 minutes
        $timeout = time() + 300;
        
        while (time() < $timeout) {
            socket_set_nonblock($socket);
            $client = @socket_accept($socket);
            
            if ($client !== false) {
                // Read HTTP request
                $request = '';
                while (($chunk = socket_read($client, 1024)) !== false && $chunk !== '') {
                    $request .= $chunk;
                    if (str_contains($request, "\r\n\r\n")) break;
                }
                
                // Parse query string for auth code or error
                if (preg_match('/GET \/callback\?.*code=([^&\s]+)/', $request, $matches)) {
                    $authCode = $matches[1];
                    
                    // Send success response
                    $response = "HTTP/1.1 200 OK\r\n" .
                               "Content-Type: text/html; charset=UTF-8\r\n" .
                               "Connection: close\r\n\r\n" .
                               "<html><head><title>Conduit Spotify</title></head><body>" .
                               "<h1>🎉 Success!</h1>" .
                               "<p>✅ Spotify connected to Conduit successfully!</p>" .
                               "<p>🎵 You can now close this window and return to your terminal.</p>" .
                               "<script>setTimeout(() => window.close(), 3000);</script>" .
                               "</body></html>";
                } elseif (preg_match('/GET \/callback\?.*error=([^&\s]+)/', $request, $errorMatch)) {
                    // Parse error details
                    $error = urldecode($errorMatch[1]);
                    preg_match('/error_description=([^&\s]+)/', $request, $descMatch);
                    $errorDesc = isset($descMatch[1]) ? urldecode($descMatch[1]) : '';
                    
                    // Log the error
                    error("❌ Spotify authorization error: {$error}");
                    if ($errorDesc) {
                        error("   Description: {$errorDesc}");
                    }
                    
                    // Handle error
                    $response = "HTTP/1.1 400 Bad Request\r\n" .
                               "Content-Type: text/html; charset=UTF-8\r\n" .
                               "Connection: close\r\n\r\n" .
                               "<html><head><title>Conduit Spotify</title></head><body>" .
                               "<h1>❌ Authorization Failed</h1>" .
                               "<p>Error: {$error}</p>" .
                               ($errorDesc ? "<p>Details: {$errorDesc}</p>" : "") .
                               "<p>Please check your Spotify app settings and try again.</p>" .
                               "</body></html>";
                }
                
                if (isset($response)) {
                    socket_write($client, $response);
                    socket_close($client);
                    break;
                }
                
                socket_close($client);
            }
            
            usleep(100000); // Sleep 100ms
        }
        
        socket_close($socket);
        
        if (!$authCode) {
            error('⏰ Authorization timed out');
        } else {
            info('✅ Authorization code received!');
        }
        
        return $authCode;
    }

    private function exchangeCodeForTokens(string $authCode, string $redirectUri): string
    {
        $tokenClient = new Client(['base_uri' => 'https://accounts.spotify.com/']);
        
        try {
            $response = $tokenClient->post('api/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $authCode,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $this->accessToken = $data['access_token'];
            
            // Store token
            $this->storeAccessToken($data);
            
            return "✅ Authentication successful! You can now use Spotify commands.";
            
        } catch (\Exception $e) {
            return "❌ Failed to exchange code for tokens: " . $e->getMessage();
        }
    }

    private function loadAccessToken(): void
    {
        $tokenFile = $this->getTokenFilePath();
        if (file_exists($tokenFile)) {
            $data = json_decode(file_get_contents($tokenFile), true);
            $this->accessToken = $data['access_token'] ?? null;
        }
    }

    private function storeAccessToken(array $tokenData): void
    {
        $tokenFile = $this->getTokenFilePath();
        $dir = dirname($tokenFile);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        
        file_put_contents($tokenFile, json_encode($tokenData));
        chmod($tokenFile, 0600);
    }

    private function getTokenFilePath(): string
    {
        return ($_SERVER['HOME'] ?? '/tmp') . '/.conduit/spotify_token.json';
    }

    private function loadConfiguration(): void
    {
        // Try to load from .env file first
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
            $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($envLines as $line) {
                if (str_contains($line, '=') && !str_starts_with(trim($line), '#')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');
                    $_ENV[$key] = $value;
                }
            }
        }
        
        // Load from environment variables
        $this->clientId = $_ENV['SPOTIFY_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'] ?? '';
    }

    private function openUrl(string $url): void
    {
        $os = PHP_OS_FAMILY;
        
        if ($os === 'Darwin') {
            exec("open '{$url}'");
        } elseif ($os === 'Windows') {
            exec("start '{$url}'");
        } else {
            exec("xdg-open '{$url}'");
        }
    }

    private function findAvailablePort(): int
    {
        $ports = [8888, 8889, 8890, 8891, 8892];
        
        foreach ($ports as $port) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
            if ($connection) {
                fclose($connection);
                continue; // Port is in use
            }
            
            // Try to bind to make sure we can use it
            $testSocket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($testSocket) {
                socket_set_option($testSocket, SOL_SOCKET, SO_REUSEADDR, 1);
                if (@socket_bind($testSocket, '127.0.0.1', $port)) {
                    socket_close($testSocket);
                    return $port; // Port is available
                }
                socket_close($testSocket);
            }
        }
        
        // If all ports are in use, try a random high port
        return rand(9000, 9999);
    }

    private function handleNoActiveDevice(?string $uri, array $data): string
    {
        info('🔍 No active device found. Checking available devices...');
        
        $devices = $this->devices();
        if (isset($devices['error']) || empty($devices)) {
            return "❌ No Spotify devices found. Please open Spotify on any device.";
        }
        
        // Try to find the best device to activate
        // Priority: 1. Last active computer, 2. Any computer, 3. Any speaker
        $lastComputer = null;
        $anyComputer = null;
        $anySpeaker = null;
        
        foreach ($devices as $device) {
            if ($device['type'] === 'Computer') {
                if (!$anyComputer) {
                    $anyComputer = $device;
                }
                // Prefer user's main computer
                if (str_contains($device['name'], 'MacBook') || str_contains($device['name'], 'Desktop')) {
                    $lastComputer = $device;
                }
            } elseif ($device['type'] === 'Speaker' && !$anySpeaker) {
                $anySpeaker = $device;
            }
        }
        
        // Select the best device
        $selectedDevice = $lastComputer ?? $anyComputer ?? $anySpeaker ?? $devices[0];
        
        info("🎯 Activating device: {$selectedDevice['name']} ({$selectedDevice['type']})");
        
        // Transfer playback to the selected device
        try {
            $this->makeRequest('PUT', 'me/player', [
                'device_ids' => [$selectedDevice['id']],
                'play' => true
            ]);
            
            // Now try to play again on the activated device
            sleep(1); // Give Spotify a moment to activate the device
            
            $url = "me/player/play?device_id={$selectedDevice['id']}";
            $this->makeRequest('PUT', $url, $data);
            
            $message = $uri ? "🎵 Playing on {$selectedDevice['name']}: {$uri}" : "▶️ Resuming playback on {$selectedDevice['name']}";
            return $message;
            
        } catch (\Exception $e) {
            // If transfer fails, try direct play with device_id
            try {
                $url = "me/player/play?device_id={$selectedDevice['id']}";
                $this->makeRequest('PUT', $url, $data);
                
                $message = $uri ? "🎵 Playing on {$selectedDevice['name']}: {$uri}" : "▶️ Resuming playback on {$selectedDevice['name']}";
                return $message;
            } catch (\Exception $e2) {
                return "❌ Failed to activate device. Please open Spotify manually on: {$selectedDevice['name']}";
            }
        }
    }
}