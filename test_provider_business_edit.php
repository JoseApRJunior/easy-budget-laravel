<?php
/**
 * Test Script for Provider Business Update Endpoint
 * URL: https://dev.easybudget.net.br/provider/business/ (PATCH)
 *
 * This script tests the provider business data update functionality
 * The edit form is at GET /provider/business/edit
 * The update action is at PATCH /provider/business/
 */

// Configuration
$baseUrl        = 'https://dev.easybudget.net.br';
$endpoint       = '/provider/business/edit';
$updateEndpoint = '/provider/business/';
$loginEndpoint  = '/login';

// Test data for provider business update
$testData = [
    // Personal data
    'first_name'          => 'João',
    'last_name'           => 'Silva',
    'birth_date'          => '1990-01-15',
    'email_personal'      => 'joao.silva@email.com',
    'phone_personal'      => '(11) 99999-9999',

    // Business data
    'company_name'        => 'João Serviços Ltda',
    'cnpj'                => '12.345.678/0001-90',
    'area_of_activity_id' => 1, // Assuming ID exists
    'profession_id'       => 1, // Assuming ID exists
    'description'         => 'Empresa especializada em serviços de TI',

    // Business contact
    'email_business'      => 'contato@joaoservicos.com.br',
    'phone_business'      => '(11) 3333-4444',
    'website'             => 'https://joaoservicos.com.br',

    // Address
    'address'             => 'Rua das Flores',
    'address_number'      => '123',
    'neighborhood'        => 'Centro',
    'city'                => 'São Paulo',
    'state'               => 'SP',
    'cep'                 => '01234-567',

    // Logo (if uploading)
    // 'logo' => '@path/to/logo.png', // For file upload
];

/**
 * Instructions for testing:
 *
 * 1. Start your Laravel development server:
 *    php artisan serve --host=0.0.0.0 --port=8000
 *
 * 2. Update the baseUrl if needed:
 *    $baseUrl = 'http://localhost:8000';
 *
 * 3. Make sure you have a logged-in session or valid authentication
 *
 * 4. Test the endpoints:
 *    - GET /provider/business/edit (to see the form)
 *    - PATCH /provider/business/ (to update data)
 *
 * 5. Use tools like Postman, Insomnia, or curl:
 */

// Example curl commands:

// 1. Get the edit form (requires authentication)
$curlGet = "curl -X GET '{$baseUrl}{$endpoint}' \\
  -H 'Cookie: your_session_cookie_here' \\
  -H 'Accept: text/html'";

// 2. Update business data (PATCH request)
$curlPatch = "curl -X PATCH '{$baseUrl}{$updateEndpoint}' \\
  -H 'Cookie: your_session_cookie_here' \\
  -H 'Content-Type: application/x-www-form-urlencoded' \\
  -H 'X-CSRF-TOKEN: your_csrf_token_here' \\
  --data '" . http_build_query( $testData ) . "'";

// 3. For file upload (logo), use multipart/form-data
$curlFileUpload = "curl -X PATCH '{$baseUrl}{$updateEndpoint}' \\
  -H 'Cookie: your_session_cookie_here' \\
  -H 'X-CSRF-TOKEN: your_csrf_token_here' \\
  -F 'first_name=João' \\
  -F 'last_name=Silva' \\
  -F 'company_name=João Serviços Ltda' \\
  -F 'email_business=contato@joaoservicos.com.br' \\
  -F 'address=Rua das Flores' \\
  -F 'address_number=123' \\
  -F 'neighborhood=Centro' \\
  -F 'city=São Paulo' \\
  -F 'state=SP' \\
  -F 'cep=01234-567' \\
  -F 'logo=@/path/to/your/logo.png'";

/**
 * Postman Collection Structure:
 *
 * Easy Budget Laravel API
 * ├── Auth
 * │   ├── Login
 * │   └── Logout
 * ├── Provider
 * │   ├── Business Data
 * │   │   ├── Get Edit Form
 * │   │   └── Update Business Data
 * │   └── Other endpoints...
 * └── Admin
 *     └── ...
 */

/**
 * Postman Request Examples:
 */

// GET /provider/business/edit
/*
Method: GET
URL: {{base_url}}/provider/business/edit
Headers:
  Cookie: laravel_session=your_session_value
  Accept: text/html
*/

// PATCH /provider/business/
$updateRequest = [
    'method'  => 'PATCH',
    'url'     => '{{base_url}}/provider/business/',
    'headers' => [
        'Cookie'       => 'laravel_session=your_session_value',
        'X-CSRF-TOKEN' => '{{csrf_token}}',
        'Accept'       => 'application/json',
    ],
    'body'    => [
        'mode'     => 'formdata',
        'formdata' => $testData
    ]
];

/**
 * Expected Responses:
 */

// Success response (200 OK)
$successResponse = [
    'redirect' => '/settings',
    'message'  => 'Dados empresariais atualizados com sucesso!',
    'status'   => 'success'
];

// Validation error response (422 Unprocessable Entity)
$validationErrorResponse = [
    'message' => 'The given data was invalid.',
    'errors'  => [
        'email_business' => [ 'O e-mail empresarial deve ter um formato válido.' ],
        'cnpj'           => [ 'O CNPJ deve ter o formato XX.XXX.XXX/XXXX-XX.' ]
    ]
];

// Authentication error (401 Unauthorized)
$authErrorResponse = [
    'message' => 'Unauthenticated.'
];

// Server error (500 Internal Server Error)
$serverErrorResponse = [
    'message' => 'Erro ao atualizar dados empresariais: Call to undefined method...'
];

/**
 * Testing Checklist:
 *
 * □ Authentication works
 * □ CSRF token is valid
 * □ Form validation passes
 * □ File upload works (logo)
 * □ Database updates correctly
 * □ Redirect works after success
 * □ Error handling works
 * □ Session data is updated
 * □ Audit logs are created
 */

/**
 * How to Get CSRF Token:
 *
 * 1. From HTML Meta Tag (Laravel default):
 *    <meta name="csrf-token" content="{{ csrf_token() }}">
 *
 *    In browser dev tools:
 *    - Go to any page of the application
 *    - Inspect element (F12)
 *    - Find: <meta name="csrf-token" content="...">
 *    - Copy the content value
 *
 * 2. From Cookie (alternative):
 *    - Check browser cookies for: XSRF-TOKEN
 *    - URL decode the value if needed
 *
 * 3. From API Endpoint (if available):
 *    GET /sanctum/csrf-cookie
 *
 * 4. JavaScript (programmatic):
 *    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
 *
 * 5. In Postman:
 *    - Set header: X-CSRF-TOKEN: your_token_here
 *    - Or use: X-XSRF-TOKEN: your_token_here (for cookie-based)
 */

/**
 * Complete Postman Setup:
 */

// Environment Variables
$postmanEnvironment = [
    'base_url'       => 'https://dev.easybudget.net.br',
    'csrf_token'     => '', // Fill this from meta tag
    'session_cookie' => '', // Fill this from browser
];

// Pre-request Script (to get CSRF token automatically)
$preRequestScript = <<<JS
// Get CSRF token from meta tag
if (pm.request.url.toString().includes('dev.easybudget.net.br')) {
    pm.sendRequest({
        url: pm.environment.get('base_url') + '/login',
        method: 'GET'
    }, function (err, response) {
        if (!err && response.code === 200) {
            const html = response.text();
            const csrfMatch = html.match(/name="csrf-token" content="([^"]+)"/);
            if (csrfMatch) {
                pm.environment.set('csrf_token', csrfMatch[1]);
            }
        }
    });
}
JS;

/**
 * Postman Collection JSON Structure:
 */
$postmanCollection = [
    'info'     => [
        'name'   => 'Easy Budget Laravel API',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
    ],
    'variable' => [
        [ 'key' => 'base_url', 'value' => 'https://dev.easybudget.net.br' ],
        [ 'key' => 'csrf_token', 'value' => '' ],
        [ 'key' => 'session_cookie', 'value' => '' ]
    ],
    'item'     => [
        [
            'name' => 'Auth',
            'item' => [
                [
                    'name'    => 'Login',
                    'request' => [
                        'method' => 'POST',
                        'header' => [
                            [ 'key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded' ],
                            [ 'key' => 'X-CSRF-TOKEN', 'value' => '{{csrf_token}}' ]
                        ],
                        'body'   => [
                            'mode'       => 'urlencoded',
                            'urlencoded' => [
                                [ 'key' => 'email', 'value' => 'your-email@example.com' ],
                                [ 'key' => 'password', 'value' => 'your-password' ]
                            ]
                        ],
                        'url'    => [ 'raw' => '{{base_url}}/login' ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'Provider Business',
            'item' => [
                [
                    'name'    => 'Update Business Data',
                    'request' => [
                        'method' => 'PATCH',
                        'header' => [
                            [ 'key' => 'Cookie', 'value' => 'laravel_session={{session_cookie}}' ],
                            [ 'key' => 'X-CSRF-TOKEN', 'value' => '{{csrf_token}}' ],
                            [ 'key' => 'Accept', 'value' => 'application/json' ]
                        ],
                        'body'   => [
                            'mode'     => 'formdata',
                            'formdata' => [
                                [ 'key' => 'first_name', 'value' => 'João' ],
                                [ 'key' => 'last_name', 'value' => 'Silva' ],
                                [ 'key' => 'company_name', 'value' => 'João Serviços Ltda' ],
                                [ 'key' => 'email_business', 'value' => 'contato@joaoservicos.com.br' ],
                                [ 'key' => 'address', 'value' => 'Rua das Flores' ],
                                [ 'key' => 'address_number', 'value' => '123' ],
                                [ 'key' => 'neighborhood', 'value' => 'Centro' ],
                                [ 'key' => 'city', 'value' => 'São Paulo' ],
                                [ 'key' => 'state', 'value' => 'SP' ],
                                [ 'key' => 'cep', 'value' => '01234-567' ]
                            ]
                        ],
                        'url'    => [ 'raw' => '{{base_url}}/provider/business/' ]
                    ]
                ]
            ]
        ]
    ]
];

/**
 * Common Issues and Solutions:
 *
 * 1. CSRF Token Issues:
 *    - Make sure to include X-CSRF-TOKEN header
 *    - Get token from meta tag or dedicated endpoint
 *
 * 2. Session Issues:
 *    - Ensure you're logged in
 *    - Check session cookie is valid
 *    - Try clearing browser cache/cookies
 *
 * 3. File Upload Issues:
 *    - Use multipart/form-data for file uploads
 *    - Check file size limits (2MB default)
 *    - Verify allowed mime types
 *
 * 4. Validation Issues:
 *    - Check regex patterns for CNPJ/CPF/CEP
 *    - Ensure foreign keys exist (area_of_activity_id, profession_id)
 *    - Verify date formats
 *
 * 5. Database Issues:
 *    - Check if provider has required relationships (commonData, contact, address)
 *    - Verify tenant isolation
 *    - Check for unique constraints
 */

/**
 * Performance Testing:
 *
 * Test with different data sizes:
 * - Small payload (basic info only)
 * - Large payload (all fields + file upload)
 * - Concurrent requests (if applicable)
 *
 * Monitor:
 * - Response time
 * - Memory usage
 * - Database queries count
 * - File I/O operations
 */

echo "=== Provider Business Update Test Script ===\n";
echo "Base URL: {$baseUrl}\n";
echo "Edit Endpoint: {$endpoint}\n";
echo "Update Endpoint: {$updateEndpoint}\n\n";

echo "=== Authentication Instructions ===\n";
echo "To properly test the provider business edit endpoint:\n\n";

echo "1. Log in to the application at {$baseUrl}{$loginEndpoint}\n";
echo "2. Get your session cookie from browser dev tools\n";
echo "3. Get CSRF token from the page or meta tag\n";
echo "4. Use the cookie and CSRF token in your requests\n\n";

echo "=== Test Data Structure ===\n";
echo json_encode( $testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n\n";

echo "=== cURL Examples ===\n";
echo "GET (Edit Form):\n{$curlGet}\n\n";
echo "PATCH (Update Data):\n{$curlPatch}\n\n";
echo "PATCH (With File):\n{$curlFileUpload}\n\n";

echo "=== Expected Response Codes ===\n";
echo "200 - Success\n";
echo "302 - Redirect after success\n";
echo "401 - Unauthorized\n";
echo "422 - Validation errors\n";
echo "500 - Server error\n\n";

echo "=== Next Steps ===\n";
echo "1. Set up your testing environment\n";
echo "2. Run the requests using Postman or curl\n";
echo "3. Verify the responses match expectations\n";
echo "4. Check database for correct updates\n";
echo "5. Test error scenarios\n";

?>
