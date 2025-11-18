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
    const ENDPOINT_SEND_DOCUMENT = 'send_document';

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

            $headers = [
                'Content-Type: application/json',
                'Authorization: ' . $token
            ];

            $payload = [
                'document_type' => $document_type,
                'document' => base64_encode($ubl_content),
                'sender' => $sender_info,
                'receiver' => $receiver_info,
                'metadata' => $document_data
            ];

            $send_endpoint = $this->get_endpoint(self::ENDPOINT_SEND_DOCUMENT);
            $response = $this->call_api($send_endpoint, $payload, $headers);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => _l('peppol_ademico_document_sent_success'),
                    'document_id' => $response['data']['document_id'] ?? null
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
            $token = $this->get_access_token($settings);

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
                self::ENDPOINT_SEND_DOCUMENT => $prod_api_base . '/documents/send'
            ],
            'sandbox' => [
                self::ENDPOINT_OAUTH => $sandbox_oauth_base . '/oauth2/token',
                self::ENDPOINT_API_BASE => $sandbox_api_base,
                self::ENDPOINT_CONNECTIVITY => $sandbox_api_base . '/tools/connectivity',
                self::ENDPOINT_SEND_DOCUMENT => $sandbox_api_base . '/documents/send'
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
     * @return string|false JWT access token on success, false on failure
     */
    private function get_access_token($settings)
    {
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
            return $response['data']['access_token'];
        }

        return false;
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
            if ($decoded_response && isset($decoded_response['error'])) {
                $error_message .= ': ' . $decoded_response['error'];
            }
            return ['success' => false, 'error' => $error_message];
        }
    }
}
