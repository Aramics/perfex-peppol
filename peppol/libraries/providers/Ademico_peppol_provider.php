<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/Abstract_peppol_provider.php';

/**
 * Ademico PEPPOL Provider
 * 
 * Provider for sending documents via Ademico's PEPPOL access point service
 */
class Ademico_peppol_provider extends Abstract_peppol_provider
{
    // Endpoint service constants
    const ENDPOINT_OAUTH = 'oauth';
    const ENDPOINT_API_BASE = 'api_base';
    const ENDPOINT_CONNECTIVITY = 'connectivity';
    const ENDPOINT_SEND_INVOICE = 'send_invoice';
    const ENDPOINT_SEND_CREDIT_NOTE = 'send_credit_note';

    public function get_provider_info()
    {
        return [
            'id' => 'ademico',
            'name' => _l('peppol_ademico_provider_name'),
            'description' => _l('peppol_ademico_provider_description'),
            'test_connection' => true
        ];
    }

    public function send($document_type, $ubl_content, $document_data, $sender_info, $receiver_info)
    {
        $settings = $this->get_settings();

        try {
            // Prepare API request
            $token = $this->get_access_token($settings);
            if (!$token) {
                return [
                    'success' => false,
                    'message' => _l('peppol_ademico_token_failed')
                ];
            }

            // Get appropriate endpoint for document type
            if ($document_type === 'invoice') {
                $send_endpoint = $this->get_endpoint(self::ENDPOINT_SEND_INVOICE);
            } elseif ($document_type === 'credit_note') {
                $send_endpoint = $this->get_endpoint(self::ENDPOINT_SEND_CREDIT_NOTE);
            } else {
                return [
                    'success' => false,
                    'message' => 'Unsupported document type: ' . $document_type
                ];
            }

            // Send UBL file via multipart/form-data as required by API
            $response = $this->send_ubl_file($send_endpoint, $ubl_content, $token, $document_type);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => _l('peppol_ademico_document_sent_success'),
                    'document_id' => $response['data']['id'] ?? $response['data']['document_id'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'message' => _l('peppol_ademico_api_error', ($response['error'] ?? _l('peppol_ademico_unknown_error')))
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => _l('peppol_ademico_connection_failed', $e->getMessage())
            ];
        }
    }

    public function test_connection($settings)
    {
        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            return [
                'success' => false,
                'message' => _l('peppol_ademico_credentials_required')
            ];
        }

        try {
            // Clear any existing cached token before testing
            $this->clear_token_cache($settings);

            // For test connection, don't use cache to ensure fresh validation
            $token = $this->get_access_token($settings, false);

            if ($token) {
                // Test API connectivity endpoint  
                $connectivity_endpoint = $this->get_endpoint(self::ENDPOINT_CONNECTIVITY, $settings['environment'] ?? null);
                $response = $this->call_api($connectivity_endpoint, null, [
                    'Authorization: ' . $token
                ]);

                if ($response['success']) {
                    return [
                        'success' => true,
                        'message' => _l('peppol_ademico_connection_success')
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => _l('peppol_ademico_health_check_failed', ($response['error'] ?? _l('peppol_ademico_unknown_error')))
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => _l('peppol_ademico_token_failed')
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => _l('peppol_ademico_test_failed', $e->getMessage())
            ];
        }
    }

    public function get_setting_inputs()
    {
        return [
            'environment' => [
                'type' => 'select',
                'label' => _l('peppol_environment'),
                'options' => [
                    'sandbox' => _l('peppol_environment_sandbox'),
                    'production' => _l('peppol_environment_production')
                ],
                'default' => 'sandbox',
                'required' => true,
                'help' => _l('peppol_ademico_environment_help')
            ],
            'client_id' => [
                'type' => 'text',
                'label' => _l('peppol_ademico_client_id'),
                'placeholder' => _l('peppol_ademico_client_id_placeholder'),
                'required' => true,
                'help' => _l('peppol_ademico_client_id_help')
            ],
            'client_secret' => [
                'type' => 'password',
                'label' => _l('peppol_ademico_client_secret'),
                'placeholder' => _l('peppol_ademico_client_secret_placeholder'),
                'required' => true,
                'help' => _l('peppol_ademico_client_secret_help')
            ],
            'timeout' => [
                'type' => 'number',
                'label' => _l('peppol_ademico_timeout'),
                'default' => 30,
                'attributes' => ['min' => 5, 'max' => 300],
                'help' => _l('peppol_ademico_timeout_help')
            ],
            'api_version' => [
                'type' => 'hidden',
                'label' => _l('peppol_ademico_api_version'),
                'default' => 'v1',
            ]
        ];
    }

    public function supported_documents()
    {
        return ['invoice', 'credit_note', 'purchase_order'];
    }

    /**
     * Get endpoint URLs mapped by environment and service
     * 
     * @return array Multi-dimensional array of endpoints
     */
    private function get_endpoints()
    {
        // Base URLs for different environments
        $prod_oauth_base = 'https://peppol-oauth2.ademico-software.com';
        $prod_api_base = 'https://peppol-api.ademico-software.com/api/peppol/v1';
        $sandbox_oauth_base = 'https://test-peppol-oauth2.ademico-software.com';
        $sandbox_api_base = 'https://test-peppol-api.ademico-software.com/api/peppol/v1';

        return [
            'production' => [
                self::ENDPOINT_OAUTH => $prod_oauth_base . '/oauth2/token',
                self::ENDPOINT_API_BASE => $prod_api_base,
                self::ENDPOINT_CONNECTIVITY => $prod_api_base . '/tools/connectivity',
                self::ENDPOINT_SEND_INVOICE => $prod_api_base . '/invoices/ubl-submissions',
                self::ENDPOINT_SEND_CREDIT_NOTE => $prod_api_base . '/credit-notes/ubl-submissions'
            ],
            'sandbox' => [
                self::ENDPOINT_OAUTH => $sandbox_oauth_base . '/oauth2/token',
                self::ENDPOINT_API_BASE => $sandbox_api_base,
                self::ENDPOINT_CONNECTIVITY => $sandbox_api_base . '/tools/connectivity',
                self::ENDPOINT_SEND_INVOICE => $sandbox_api_base . '/invoices/ubl-submissions',
                self::ENDPOINT_SEND_CREDIT_NOTE => $sandbox_api_base . '/credit-notes/ubl-submissions'
            ]
        ];
    }

    /**
     * Get specific endpoint URL for given service and optional environment
     * 
     * @param string $service Endpoint service name (use class constants)
     * @param string|null $environment Optional environment override ('production' or 'sandbox')
     * @return string Full endpoint URL
     */
    private function get_endpoint($service, $environment = null)
    {
        // Auto-fetch environment from settings if not provided
        if ($environment === null) {
            $settings = $this->get_settings();
            $environment = $settings['environment'] ?? 'sandbox';
        }

        $endpoints = $this->get_endpoints();
        $env = $environment === 'production' ? 'production' : 'sandbox';

        return $endpoints[$env][$service] ?? $endpoints[$env][self::ENDPOINT_API_BASE];
    }

    /**
     * Get OAuth2 access token using client credentials flow
     * 
     * Authenticates with Ademico OAuth2 endpoint using Basic authentication
     * and client credentials grant type to obtain a JWT access token.
     * 
     * @param array $settings Provider settings containing client_id and client_secret
     * @param bool $use_cache Whether to use cached token if available (default: true)
     * @return string|false JWT access token on success, false on failure
     */
    private function get_access_token($settings, $use_cache = true)
    {
        $cache_key = $this->get_token_cache_key($settings);

        // Check cache first if enabled
        if ($use_cache) {
            $cached_token = $this->get_cached_token($cache_key);
            if ($cached_token) {
                return $cached_token;
            }
        }

        $token_endpoint = $this->get_endpoint(self::ENDPOINT_OAUTH, $settings['environment']);

        // Base64 encode client_id:client_secret for Basic auth header
        $credentials = base64_encode($settings['client_id'] . ':' . $settings['client_secret']);

        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded'
        ];

        // OAuth2 form-encoded parameters for client credentials grant
        $url_params = [
            'grant_type' => 'client_credentials',
            'scope' => 'peppol/document'
        ];

        $response = $this->call_api($token_endpoint, null, $headers, $url_params);

        if ($response['success'] && isset($response['data']['access_token'])) {
            $token = $response['data']['access_token'];

            // Cache the token for future use
            if ($use_cache) {
                $this->cache_token($cache_key, $token);
            }

            return $token;
        }

        return false;
    }

    /**
     * Generate cache key for token based on settings
     * 
     * @param array $settings Provider settings
     * @return string Cache key
     */
    private function get_token_cache_key($settings)
    {
        return 'peppol_ademico_token_' . md5($settings['client_id'] . '_' . $settings['environment']);
    }

    /**
     * Get cached token if valid and not expired
     * 
     * @param string $cache_key Cache key
     * @return string|false Cached token or false if expired/missing
     */
    private function get_cached_token($cache_key)
    {
        $cached_data = $this->CI->session->userdata($cache_key);

        if ($cached_data) {
            $data = json_decode($cached_data, true);

            // Check if token is still valid (expires_at is in the future)
            if (isset($data['token']) && isset($data['expires_at']) && $data['expires_at'] > time()) {
                return $data['token'];
            }
        }

        return false;
    }

    /**
     * Cache token with expiration
     * 
     * @param string $cache_key Cache key
     * @param string $token JWT token
     */
    private function cache_token($cache_key, $token)
    {
        // Cache for 50 minutes (JWT tokens expire after 1 hour, leave 10 min buffer)
        $expires_at = time() + (50 * 60);

        $cache_data = json_encode([
            'token' => $token,
            'expires_at' => $expires_at,
            'created_at' => time()
        ]);

        $this->CI->session->set_userdata($cache_key, $cache_data);
    }

    /**
     * Clear cached token for given settings
     * 
     * @param array $settings Provider settings
     * @return bool True if cache was cleared
     */
    public function clear_token_cache($settings)
    {
        $cache_key = $this->get_token_cache_key($settings);
        $this->CI->session->unset_userdata($cache_key);
        return true;
    }

    /**
     * Send UBL file via multipart/form-data as required by Ademico API
     * 
     * @param string $endpoint The API endpoint URL
     * @param string $ubl_content The UBL XML content
     * @param string $token JWT authorization token
     * @param string $document_type Type of document (invoice/credit_note)
     * @return array Response array with 'success' boolean and 'data'/'error' keys
     */
    private function send_ubl_file($endpoint, $ubl_content, $token, $document_type = 'invoice')
    {
        $settings = $this->get_settings();

        // Create temporary file for UBL content
        $temp_file = tempnam(get_temp_dir(), 'peppol_ubl_');
        file_put_contents($temp_file, $ubl_content);

        // Generate appropriate filename based on document type
        $filename = $document_type === 'credit_note' ? 'credit-note-sending.xml' : 'invoice-sending.xml';

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $settings['timeout'] ?? 30,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $token
                    // Note: Don't set Content-Type, let cURL set it for multipart/form-data
                ],
                CURLOPT_POSTFIELDS => [
                    'file' => new CURLFile($temp_file, 'application/xml', $filename)
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects for file uploads
                CURLOPT_MAXREDIRS => 0
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            // Clean up temporary file
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }

            if ($error) {
                return ['success' => false, 'error' => 'CURL Error: ' . $error];
            }

            // Try to parse JSON response
            $decoded_response = null;
            if ($response) {
                $decoded_response = json_decode($response, true);

                // If JSON parsing fails but we got a response, include raw response
                if (json_last_error() !== JSON_ERROR_NONE && $response) {
                    return [
                        'success' => false,
                        'error' => 'HTTP ' . $http_code . ' - Invalid JSON response: ' . substr($response, 0, 200)
                    ];
                }
            }

            if ($http_code >= 200 && $http_code < 300) {
                return ['success' => true, 'data' => $decoded_response ?: []];
            } else {
                $error_message = 'HTTP ' . $http_code;
                log_message("error", json_encode($decoded_response));
                if ($decoded_response) {
                    // Parse Ademico-specific error response structure
                    $parsed_error = $this->parse_ademico_error($decoded_response);
                    $error_message .= ': ' . $parsed_error;
                } elseif ($response) {
                    // Include raw response if no JSON structure
                    $error_message .= ' - ' . substr($response, 0, 200);
                }

                return ['success' => false, 'error' => $error_message];
            }
        } catch (Exception $e) {
            // Clean up temporary file in case of exception
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Convert array or object to string for error messages
     * 
     * @param mixed $value The value to convert
     * @return string String representation of the value
     */
    private function format_error_value($value)
    {
        if (is_array($value)) {
            // Handle multi-dimensional arrays by flattening
            $flat_values = [];
            array_walk_recursive($value, function ($item) use (&$flat_values) {
                $flat_values[] = is_string($item) ? $item : json_encode($item);
            });
            return implode(', ', $flat_values);
        } elseif (is_object($value)) {
            return json_encode($value);
        } else {
            return (string) $value;
        }
    }

    /**
     * Parse Ademico API error response and extract meaningful error message
     * 
     * @param array $response_data Decoded JSON response from API
     * @return string Formatted error message
     */
    private function parse_ademico_error($response_data)
    {
        $error_parts = [];

        // Handle nested message structure
        if (isset($response_data['message'])) {
            if (is_array($response_data['message'])) {
                // Extract code and message from nested structure
                if (isset($response_data['message']['code'])) {
                    $error_parts[] = 'Code: ' . $response_data['message']['code'];
                }
                if (isset($response_data['message']['message'])) {
                    $error_parts[] = $response_data['message']['message'];
                }
            } else {
                $error_parts[] = $this->format_error_value($response_data['message']);
            }
        }

        // Handle validation report if present
        if (isset($response_data['validationReport'])) {
            $validation = $response_data['validationReport'];

            if (isset($validation['globalStatus']) && $validation['globalStatus'] === 'ERROR') {
                $validation_errors = [];

                // Add error count if available
                if (isset($validation['errorsCount']) && $validation['errorsCount'] > 0) {
                    $validation_errors[] = sprintf('%d validation error(s)', $validation['errorsCount']);
                }

                // Add validated file name
                if (isset($validation['validatedXmlName'])) {
                    $validation_errors[] = 'File: ' . $validation['validatedXmlName'];
                }

                // Extract specific validation errors from reports
                $specific_errors = [];

                // Check schematron validation report
                if (isset($validation['schematronValidationReport']) && is_array($validation['schematronValidationReport'])) {
                    foreach ($validation['schematronValidationReport'] as $schematron_error) {
                        if (isset($schematron_error['message'])) {
                            $specific_errors[] = 'Schematron: ' . $schematron_error['message'];
                        }
                    }
                }

                // Check XSD validation report
                if (isset($validation['xsdValidationReport']) && is_array($validation['xsdValidationReport'])) {
                    foreach ($validation['xsdValidationReport'] as $xsd_error) {
                        if (isset($xsd_error['message'])) {
                            $specific_errors[] = 'XSD: ' . $xsd_error['message'];
                        }
                    }
                }

                if (!empty($validation_errors)) {
                    $error_parts[] = 'Validation failed: ' . implode(', ', $validation_errors);
                }

                // Add first few specific errors (limit to 3 to keep message readable)
                if (!empty($specific_errors)) {
                    $error_parts[] = 'Details: ' . implode('; ', array_slice($specific_errors, 0, 3));
                    if (count($specific_errors) > 3) {
                        $error_parts[] = sprintf('... and %d more error(s)', count($specific_errors) - 3);
                    }
                }
            }
        }

        // Fallback to other common error fields
        if (empty($error_parts)) {
            if (isset($response_data['error'])) {
                $error_parts[] = $this->format_error_value($response_data['error']);
            } elseif (isset($response_data['detail'])) {
                $error_parts[] = $this->format_error_value($response_data['detail']);
            } elseif (isset($response_data['errors'])) {
                $error_parts[] = $this->format_error_value($response_data['errors']);
            }
        }

        return !empty($error_parts) ? implode(' | ', $error_parts) : 'Unknown error format';
    }

    /**
     * Make HTTP API call to Ademico endpoints
     * 
     * Supports both JSON POST requests and URL-encoded form submissions.
     * All endpoints return JSON responses which are automatically parsed.
     * 
     * @param string $url The full endpoint URL to call
     * @param array|null $data JSON data payload for POST requests (will be JSON encoded)
     * @param array $headers HTTP headers to include in the request
     * @param array $url_params URL-encoded form parameters for POST requests (alternative to JSON)
     * @return array Response array with 'success' boolean and 'data'/'error' keys
     */
    private function call_api($url, $data = null, $headers = [], $url_params = [])
    {
        $settings = $this->get_settings();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $settings['timeout'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        // Handle URL-encoded parameters (for OAuth2 and form submissions)
        if (!empty($url_params)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($url_params));
        }
        // Handle JSON data payload (for API requests)
        elseif ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        // GET request if no data provided

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        // All Ademico endpoints return JSON responses
        $decoded_response = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'data' => $decoded_response];
        } else {
            $error_message = 'HTTP ' . $http_code;
            if ($decoded_response) {
                // Parse Ademico-specific error response structure
                $parsed_error = $this->parse_ademico_error($decoded_response);
                $error_message .= ': ' . $parsed_error;
            }
            return ['success' => false, 'error' => $error_message];
        }
    }
}
