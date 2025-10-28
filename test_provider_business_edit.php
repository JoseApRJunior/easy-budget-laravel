<?php

/**
 * Test Script for Provider Business Update Endpoint
 * URL: https://dev.easybudget.net.br/provider/business/ (PATCH)
 *
 * This script tests the provider business data update functionality
 * The edit form is at GET /provider/business/edit
 * The update action is at PATCH /provider/business/
 */

class ProviderBusinessUpdateTest
{
    private string $baseUrl = 'https://dev.easybudget.net.br';
    private string $endpoint = '/provider/business/edit';
    private string $updateEndpoint = '/provider/business/';
    private ?string $csrfToken = null;
    private ?string $sessionCookie = null;

    public function __construct()
    {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    /**
     * Test the provider business update endpoint
     */
    public function testProviderBusinessUpdate(): void
    {
        echo "=== Provider Business Update Test ===\n\n";

        // Test data for provider business update
        $testData = [
            'company_name' => 'Empresa Teste Ltda',
            'cnpj' => '12.345.678/0001-90',
            'description' => 'Empresa de teste para desenvolvimento',
            'area_of_activity_id' => 1,
            'profession_id' => 1,
            'phone' => '(11) 99999-9999',
            'email_business' => 'contato@empresateste.com',
            'website' => 'https://empresateste.com',
            'address' => 'Rua Teste, 123',
            'address_number' => '123',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'cep' => '01234-567'
        ];

        echo "Test Data:\n";
        print_r($testData);
        echo "\n";

        // First, try to get the form (GET request)
        echo "1. Testing GET request to retrieve form...\n";
        $getResponse = $this->makeRequest('GET', $this->endpoint);
        echo "GET Response Status: " . ($getResponse['status'] ?? 'Unknown') . "\n";
        echo "GET Response Body Length: " . strlen($getResponse['body'] ?? '') . " characters\n\n";

        // Extract CSRF token from the form
        $this->extractCsrfToken($getResponse['body'] ?? '');

        // Check if we need authentication
        if (strpos($getResponse['body'] ?? '', 'login') !== false || $getResponse['status'] == 302) {
            echo "⚠️  Authentication required. Please login first.\n";
            echo "To test properly, you need to:\n";
            echo "1. Login to the application\n";
            echo "2. Copy the session cookie\n";
            echo "3. Set it in this script\n\n";

            $this->showAuthenticationInstructions();
            return;
        }

        // If authenticated, test the PATCH request
        echo "2. Testing PATCH request to update business data...\n";
        $patchResponse = $this->makeRequest('PATCH', $this->updateEndpoint, $testData);
        echo "PATCH Response Status: " . ($patchResponse['status'] ?? 'Unknown') . "\n";
        echo "PATCH Response Body Length: " . strlen($patchResponse['body'] ?? '') . " characters\n\n";

        // Analyze response
        $this->analyzeResponse($patchResponse);
    }

    /**
     * Make HTTP request using curl
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // For development only
            CURLOPT_SSL_VERIFYHOST => false, // For development only
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'ProviderBusinessEditTest/1.0'
        ]);

        // Set method
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        // Set headers
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding: gzip, deflate, br',
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
        ];

        // Add CSRF token if available (you might need to extract it from the form)
        // For testing, we'll try to extract it from the GET response first
        if (isset($this->csrfToken)) {
            $headers[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
        }

        // Add _token to POST data if we have it
        if (isset($this->csrfToken) && $method === 'PATCH') {
            $data['_token'] = $this->csrfToken;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Add session cookie if available
        if ($this->sessionCookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->sessionCookie);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return ['error' => $error, 'status' => $httpCode];
        }

        // Split headers and body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'status' => $httpCode,
            'header' => $header,
            'body' => $body,
            'full_response' => $response
        ];
    }

    /**
     * Analyze the response from the server
     */
    private function analyzeResponse(array $response): void
    {
        if (isset($response['error'])) {
            echo "❌ Request Error: " . $response['error'] . "\n";
            return;
        }

        $status = $response['status'];
        $body = $response['body'];

        echo "Response Analysis:\n";
        echo "- Status Code: $status\n";

        if ($status >= 200 && $status < 300) {
            echo "✅ Success! The request was processed successfully.\n";

            // Check for success messages
            if (strpos($body, 'success') !== false || strpos($body, 'atualizado') !== false) {
                echo "✅ Success message found in response.\n";
            }

            // Check for redirects
            if ($status == 302 || strpos($response['header'], 'Location:') !== false) {
                echo "➡️  Redirect detected - possibly successful update with redirect.\n";
            }

        } elseif ($status == 302) {
            echo "➡️  Redirect - Check if it's a successful redirect after update.\n";

        } elseif ($status == 401 || $status == 403) {
            echo "🔒 Authentication/Authorization error.\n";

        } elseif ($status == 422) {
            echo "📝 Validation error - Check the form data.\n";
            $this->extractValidationErrors($body);

        } elseif ($status >= 500) {
            echo "💥 Server error - Check application logs.\n";

        } else {
            echo "⚠️  Unexpected status code.\n";
        }

        // Save response for debugging
        $this->saveResponseForDebugging($response);
    }

    /**
     * Extract validation errors from response
     */
    private function extractValidationErrors(string $body): void
    {
        // Try to extract Laravel validation errors
        if (preg_match_all('/<li>(.*?)<\/li>/', $body, $matches)) {
            echo "Validation Errors Found:\n";
            foreach ($matches[1] as $error) {
                echo "  - $error\n";
            }
        }
    }

    /**
     * Save response for debugging purposes
     */
    private function saveResponseForDebugging(array $response): void
    {
        $filename = 'debug_response_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($filename, $response['body'] ?? '');
        echo "\n💾 Response saved to: $filename\n";
    }

    /**
     * Extract CSRF token from HTML response
     */
    private function extractCsrfToken(string $html): void
    {
        // Try to find CSRF token in meta tag
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
            $this->csrfToken = $matches[1];
            echo "✅ CSRF token extracted from meta tag: " . substr($this->csrfToken, 0, 20) . "...\n";
        }
        // Try to find CSRF token in hidden input
        elseif (preg_match('/name="_token" value="([^"]+)"/', $html, $matches)) {
            $this->csrfToken = $matches[1];
            echo "✅ CSRF token extracted from hidden input: " . substr($this->csrfToken, 0, 20) . "...\n";
        } else {
            echo "⚠️  CSRF token not found in response\n";
        }
    }

    /**
     * Show authentication instructions
     */
    private function showAuthenticationInstructions(): void
    {
        echo "=== Authentication Instructions ===\n";
        echo "To properly test the provider business edit endpoint:\n\n";

        echo "1. Open your browser and go to: {$this->baseUrl}/login\n";
        echo "2. Login with valid provider credentials\n";
        echo "3. Open browser developer tools (F12)\n";
        echo "4. Go to Application/Storage > Cookies\n";
        echo "5. Find the session cookie (usually 'laravel_session' or similar)\n";
        echo "6. Copy the cookie value\n";
        echo "7. Set it in this script by modifying the \$sessionCookie property\n\n";

        echo "Example:\n";
        echo "\$this->sessionCookie = 'laravel_session=your_session_value_here';\n\n";

        echo "Alternatively, you can modify the script to handle login programmatically.\n";
    }

    /**
     * Test with different data scenarios
     */
    public function testScenarios(): void
    {
        echo "=== Testing Different Scenarios ===\n\n";

        $scenarios = [
            'valid_data' => [
                'company_name' => 'Empresa Válida Ltda',
                'cnpj' => '12.345.678/0001-90',
                'description' => 'Descrição válida',
                'area_of_activity_id' => 1,
                'profession_id' => 1,
            ],
            'invalid_cnpj' => [
                'company_name' => 'Empresa Teste',
                'cnpj' => 'invalid-cnpj',
                'description' => 'Teste com CNPJ inválido',
            ],
            'missing_required' => [
                'description' => 'Teste sem campos obrigatórios',
            ],
            'empty_data' => []
        ];

        foreach ($scenarios as $name => $data) {
            echo "Testing scenario: $name\n";
            $response = $this->makeRequest('PATCH', $this->updateEndpoint, $data);
            $this->analyzeResponse($response);
            echo str_repeat("-", 50) . "\n\n";
            sleep(1); // Small delay between requests
        }
    }
}

// Run the test
$test = new ProviderBusinessUpdateTest();

// Basic test
$test->testProviderBusinessUpdate();

// Uncomment to test different scenarios
// $test->testScenarios();

echo "\n=== Test Completed ===\n";
echo "Check the generated debug files for detailed response analysis.\n";
?>