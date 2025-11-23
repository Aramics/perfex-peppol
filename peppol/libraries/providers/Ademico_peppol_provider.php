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
    const ENDPOINT_LEGAL_ENTITIES = 'legal_entities';
    const ENDPOINT_NOTIFICATIONS = 'notifications';
    const ENDPOINT_GET_UBL = 'get_ubl';

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

            // Step 1: Ensure sender (company) is registered as legal entity
            $sender_registration = $this->ensure_legal_entity_registered($sender_info);
            if (!$sender_registration['success']) {
                return [
                    'success' => false,
                    'message' => 'Sender registration failed: ' . $sender_registration['message']
                ];
            }

            // Step 2: Ensure receiver (client) is registered as legal entity
            $receiver_registration = $this->ensure_legal_entity_registered($receiver_info);

            if (!$receiver_registration['success']) {
                return [
                    'success' => false,
                    'message' => 'Receiver registration failed: ' . $receiver_registration['message']
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

            // Step 3: Send UBL file via multipart/form-data as required by API
            $response = $this->send_ubl_file($send_endpoint, $ubl_content, $token, $document_type);

            if ($response['success']) {
                // Include entity registration information in metadata
                $metadata = array_merge($response['data'] ?? [], [
                    'sender_entity_id' => $sender_registration['entity_id'],
                    'receiver_entity_id' => $receiver_registration['entity_id'],
                    'sender_was_existing' => $sender_registration['was_existing'] ?? false,
                    'receiver_was_existing' => $receiver_registration['was_existing'] ?? false,
                    'registration_timestamp' => date('Y-m-d H:i:s')
                ]);

                return [
                    'success' => true,
                    'message' => _l('peppol_ademico_document_sent_success'),
                    'document_id' => $response['data']['id'] ?? $response['data']['documentId'] ?? null,
                    'metadata' => $metadata
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
                self::ENDPOINT_SEND_CREDIT_NOTE => $prod_api_base . '/invoices/ubl-submissions',
                self::ENDPOINT_LEGAL_ENTITIES => $prod_api_base . '/legal-entities',
                self::ENDPOINT_NOTIFICATIONS => $prod_api_base . '/notifications',
                self::ENDPOINT_GET_UBL => $prod_api_base . '/invoices'
            ],
            'sandbox' => [
                self::ENDPOINT_OAUTH => $sandbox_oauth_base . '/oauth2/token',
                self::ENDPOINT_API_BASE => $sandbox_api_base,
                self::ENDPOINT_CONNECTIVITY => $sandbox_api_base . '/tools/connectivity',
                self::ENDPOINT_SEND_INVOICE => $sandbox_api_base . '/invoices/ubl-submissions',
                self::ENDPOINT_SEND_CREDIT_NOTE => $sandbox_api_base . '/invoices/ubl-submissions',
                self::ENDPOINT_LEGAL_ENTITIES => $sandbox_api_base . '/legal-entities',
                self::ENDPOINT_NOTIFICATIONS => $sandbox_api_base . '/notifications',
                self::ENDPOINT_GET_UBL => $sandbox_api_base . '/invoices'
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
     * Register a legal entity with Ademico PEPPOL network
     * 
     * @param array $entity_info Entity information including PEPPOL identifiers
     * @return array Registration result with success status and entity ID
     */
    private function register_legal_entity($entity_info)
    {
        $settings = $this->get_settings();

        try {
            $token = $this->get_access_token($settings);
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Failed to obtain access token for legal entity registration'
                ];
            }

            $endpoint = $this->get_endpoint(self::ENDPOINT_LEGAL_ENTITIES);

            // Build address string for geographicalInformation field
            $address_parts = array_filter([
                $entity_info['address'] ?? '',
                $entity_info['city'] ?? '',
                $entity_info['postal_code'] ?? '',
                $entity_info['country_code'] ?? ''
            ]);
            $geographical_info = implode(', ', $address_parts);

            // Prepare contacts array
            $contacts = [];
            if (!empty($entity_info['email']) || !empty($entity_info['phone'])) {
                $contact = [];
                if (!empty($entity_info['email'])) {
                    $contact['email'] = $entity_info['email'];
                }
                if (!empty($entity_info['phone'])) {
                    $contact['phone'] = $entity_info['phone'];
                }
                if (!empty($entity_info['contact_name'])) {
                    $contact['name'] = $entity_info['contact_name'];
                }

                $contact_type = empty($entity_info['contact_type']) ? 'primary' : $entity_info['contact_type'];

                $contact['contactType'] = $contact_type;
                $contacts[] = $contact;
            }

            // Prepare legal entity data according to Ademico API specification
            $legal_entity_data = [
                'legalEntityDetails' => [
                    'name' => $entity_info['name'],
                    'countryCode' => $entity_info['country_code'] ?? 'BE',
                    'geographicalInformation' => $geographical_info ?: 'Address not provided',
                    'publishInPeppolDirectory' => true,
                    'contacts' => $contacts,
                    'additionalInformation' => 'Registered via Perfex CRM PEPPOL module',
                    'peppolAdditionalIdentifiers' => []
                ],
                'peppolRegistrations' => [
                    [
                        'peppolIdentifier' => [
                            'scheme' => $entity_info['scheme'],
                            'identifier' => $entity_info['identifier']
                        ],
                        'peppolRegistration' => true,
                        'supportedDocuments' => [
                            'PEPPOL_BIS_BILLING_UBL_INVOICE_V3',
                            'PEPPOL_BIS_BILLING_UBL_CREDIT_NOTE_V3'
                        ]
                    ]
                ]
            ];

            // Add website URL if available
            if (!empty($entity_info['website'])) {
                $legal_entity_data['legalEntityDetails']['websiteURL'] = $entity_info['website'];
            }

            $headers = [
                'Authorization: ' . $token,
                'Content-Type: application/json'
            ];

            $response = $this->call_api($endpoint, $legal_entity_data, $headers, [], 'POST');

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Legal entity registered successfully',
                    'entity_id' => $response['data']['legalEntityId'] ?? $response['data']['id'] ?? null,
                    'entity_data' => $response['data']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to register legal entity: ' . $response['error']
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Legal entity registration failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if a legal entity is already registered
     * 
     * @param array $entity_info Entity information
     * @return array Check result with registration status
     */
    private function check_legal_entity_registration($entity_info)
    {
        $settings = $this->get_settings();

        try {
            $token = $this->get_access_token($settings);
            if (!$token) {
                return ['registered' => false, 'message' => 'No access token'];
            }

            $endpoint = $this->get_endpoint(self::ENDPOINT_LEGAL_ENTITIES);

            $headers = [
                'Authorization: ' . $token,
                'Content-Type: application/json'
            ];

            // Search by PEPPOL registration scheme and identifier according to API docs
            $search_params = [
                'peppolRegistrationScheme' => $entity_info['scheme'],
                'peppolRegistrationIdentifier' => $entity_info['identifier'],
                'pageSize' => 10
            ];

            $response = $this->call_api($endpoint, null, $headers, $search_params);

            if ($response['success']) {
                $response_data = $response['data'];
                $entities = $response_data['legalEntities'] ?? $response_data['content'] ?? [];

                if (!empty($entities)) {
                    // Find exact match by PEPPOL identifier
                    foreach ($entities as $entity) {
                        $peppol_registrations = $entity['peppolRegistrations'] ?? [];
                        foreach ($peppol_registrations as $registration) {
                            $peppol = $registration['peppolRegistrationDetails']['peppolIdentifier'] ?? [];
                            if (
                                isset($peppol['scheme']) && isset($peppol['identifier']) &
                                $peppol['scheme'] == $entity_info['scheme'] &&
                                $peppol['identifier'] == $entity_info['identifier']
                            ) {
                                return [
                                    'registered' => true,
                                    'entity_id' => $entity['id'] ?? null,
                                    'entity_data' => $entity
                                ];
                            }
                        }
                    }
                }
            }

            return ['registered' => false];
        } catch (Exception $e) {
            return ['registered' => false, 'message' => $e->getMessage()];
        }
    }


    /**
     * Ensure legal entity is registered (check first, register if not)
     * 
     * @param array $entity_info Entity information
     * @return array Registration result with entity_id
     */
    private function ensure_legal_entity_registered($entity_info)
    {
        // First check if already registered
        $check_result = $this->check_legal_entity_registration($entity_info);

        if ($check_result['registered']) {
            return [
                'success' => true,
                'message' => 'Legal entity already registered in PEPPOL network',
                'entity_id' => $check_result['entity_id'],
                'entity_data' => $check_result['entity_data'],
                'was_existing' => true
            ];
        }

        // Not registered, so register now (includes PEPPOL participant registration)
        $registration_result = $this->register_legal_entity($entity_info);

        if ($registration_result['success']) {
            return [
                'success' => true,
                'message' => 'Legal entity created and registered in PEPPOL network successfully',
                'entity_id' => $registration_result['entity_id'],
                'entity_data' => $registration_result['entity_data'],
                'was_existing' => false
            ];
        }

        return $registration_result;
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
                log_message("error", 'Ademico:' . $endpoint . ':' . json_encode($decoded_response));
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
                    foreach ($validation['schematronValidationReport'] as $schematron_report) {
                        if (isset($schematron_report['schematronValidationMessages']) && is_array($schematron_report['schematronValidationMessages'])) {
                            foreach ($schematron_report['schematronValidationMessages'] as $schematron_message) {
                                if (isset($schematron_message['description'])) {
                                    $error_detail = $schematron_message['description'];
                                    if (isset($schematron_message['errorId'])) {
                                        $error_detail = '[' . $schematron_message['errorId'] . '] ' . $error_detail;
                                    }
                                    $specific_errors[] = 'Schematron: ' . $error_detail;
                                }
                            }
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
     * Supports GET requests with query parameters, JSON POST requests, and URL-encoded form submissions.
     * All endpoints return JSON responses which are automatically parsed.
     * 
     * @param string $url The full endpoint URL to call
     * @param array|null $data JSON data payload for POST requests (will be JSON encoded)
     * @param array $headers HTTP headers to include in the request
     * @param array $url_params For GET: query parameters, For POST: form parameters
     * @param string $method HTTP method (GET or POST, auto-detected if not specified)
     * @return array Response array with 'success' boolean and 'data'/'error' keys
     */
    private function call_api($url, $data = null, $headers = [], $url_params = [], $method = null)
    {
        $settings = $this->get_settings();

        // Auto-detect method if not specified
        if ($method === null) {
            if ($data !== null || (!empty($url_params) && strpos($url, 'oauth2/token') !== false)) {
                $method = 'POST';
            } else {
                $method = 'GET';
            }
        }

        // For GET requests, append query parameters to URL
        if ($method === 'GET' && !empty($url_params)) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($url_params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $settings['timeout'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);

            // Handle URL-encoded parameters (for OAuth2 and form submissions)
            if (!empty($url_params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($url_params));
            }
            // Handle JSON data payload (for API requests)
            elseif ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        // GET request - no additional setup needed

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

    /**
     * Process PEPPOL webhook notifications
     * Fetches notifications, processes incoming documents, and updates sent document statuses
     * 
     * @param array $payload Webhook payload data
     * @return array Processing results
     */
    public function webhook($payload = [])
    {
        $CI = &get_instance();
        $CI->load->library('peppol/peppol_service');
        $CI->load->model('peppol/peppol_model');

        $results = [
            'success' => true,
            'processed_incoming' => 0,
            'updated_statuses' => 0,
            'errors' => [],
            'incoming_documents' => [],
            'status_updates' => []
        ];

        try {
            // Get recent notifications (last 24 hour if no specific filters)
            $filters = [
                'startDateTime' => date('c', strtotime('-100 hour')),
                'pageSize' => 100
            ];

            $notifications_response = $this->_get_notifications($filters);

            if (!$notifications_response['success']) {
                $results['success'] = false;
                $results['errors'][] = 'Failed to retrieve notifications: ' . $notifications_response['message'];
                return $results;
            }

            foreach ($notifications_response['notifications'] as $notification) {
                $notification_id = $notification['notificationId'] ?? null;
                $notification_processed = false;

                try {
                    $event_type = $notification['eventType'] ?? '';
                    $transmission_id = $notification['transmissionId'] ?? '';

                    // Process incoming documents
                    if ($event_type === 'DOCUMENT_RECEIVED' && !empty($transmission_id)) {
                        $incoming_result = $this->_process_incoming_document($notification);

                        if ($incoming_result['success']) {
                            $results['processed_incoming']++;
                            $results['incoming_documents'][] = $incoming_result['data'];
                            $notification_processed = true;
                        } elseif (isset($incoming_result['already_processed']) && $incoming_result['already_processed']) {
                            // Document was already processed, still mark notification as processed to consume it
                            $notification_processed = true;
                        } else {
                            $results['errors'][] = 'Failed to process incoming document ' . $transmission_id . ': ' . $incoming_result['message'];
                        }
                    }

                    // Process status updates for sent documents
                    elseif (in_array($event_type, ['DOCUMENT_SENT', 'DOCUMENT_SEND_FAILED', 'MLR_RECEIVED', 'INVOICE_RESPONSE_RECEIVED'])) {
                        $status_result = $this->_update_document_status($notification);
                        if ($status_result['success']) {
                            $results['updated_statuses']++;
                            $results['status_updates'][] = $status_result['data'];
                            $notification_processed = true;
                        } else {
                            $results['errors'][] = 'Failed to update status for document ' . ($notification['documentId'] ?? $transmission_id) . ': ' . $status_result['message'];
                        }
                    }

                    // Consume (delete) notification if it was successfully processed
                    if ($notification_processed && $notification_id) {
                        $consume_result = $this->consume_notification($notification_id);
                        if (!$consume_result['success']) {
                            // Log consumption error but don't fail the whole webhook
                            $results['errors'][] = 'Warning: Failed to consume notification ' . $notification_id . ': ' . $consume_result['message'];
                        }
                    }
                } catch (Exception $e) {
                    $results['errors'][] = 'Error processing notification: ' . $e->getMessage();
                }
            }

            // Log webhook processing activity
            if ($results['processed_incoming'] > 0 || $results['updated_statuses'] > 0) {
                $CI->peppol_model->log_activity([
                    'type' => 'webhook_processed',
                    'message' => sprintf(
                        'Webhook processed: %d incoming documents, %d status updates',
                        $results['processed_incoming'],
                        $results['updated_statuses']
                    ),
                    'data' => json_encode([
                        'processed_incoming' => $results['processed_incoming'],
                        'updated_statuses' => $results['updated_statuses'],
                        'errors_count' => count($results['errors'])
                    ])
                ]);
            }
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = 'Webhook processing failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get notifications from Ademico API (private method for webhook)
     */
    private function _get_notifications($filters = [])
    {
        $settings = $this->get_settings();

        try {
            $token = $this->get_access_token($settings);
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Failed to obtain access token for notifications'
                ];
            }

            $endpoint = $this->get_endpoint(self::ENDPOINT_NOTIFICATIONS);

            $headers = [
                'Authorization: ' . $token,
                'Content-Type: application/json'
            ];

            // Build query parameters from filters
            $query_params = array_merge([
                'page' => 0,
                'pageSize' => 50
            ], $filters);

            $response = $this->call_api($endpoint, null, $headers, $query_params, 'GET');

            if ($response['success']) {
                return [
                    'success' => true,
                    'notifications' => $response['data']['notifications'] ?? [],
                    'pagination' => $response['data']['pagination'] ?? []
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'API error: ' . ($response['error'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process a single incoming document notification
     */
    private function _process_incoming_document($notification)
    {
        $CI = &get_instance();
        $CI->load->model('peppol/peppol_model');

        try {
            $transmission_id = $notification['transmissionId'];

            // Check if this notification has already been processed
            $notification_id = $notification['notificationId'] ?? null;
            $existing_document = null;

            if ($notification_id) {
                $existing_document = $CI->peppol_model->get_peppol_document_by_metadata('notificationId', $notification_id, $this->get_id());
            }

            if ($existing_document) {
                return [
                    'success' => false,
                    'already_processed' => true,
                    'message' => 'Notification ' . $notification_id . ' has already been processed',
                    'data' => [
                        'notification_id' => $notification_id,
                        'transmission_id' => $transmission_id,
                        'existing_document_id' => $existing_document->document_id,
                        'existing_document_type' => $existing_document->document_type,
                        'processed_at' => $existing_document->created_at
                    ]
                ];
            }

            // Get UBL XML content directly (Ademico only provides UBL endpoint)
            $ubl_response = $this->get_document_ubl($transmission_id);
            if (!$ubl_response['success']) {
                return $ubl_response;
            }

            $document_data = [
                'notification' => $notification,
                'ubl_xml' => $ubl_response['ubl_xml'],
                'transmission_id' => $transmission_id,
                'document_type' => $notification['peppolDocumentType'] ?? null,
                'sender' => $notification['sender'] ?? null,
                'receiver' => $notification['receiver'] ?? null,
                'notification_date' => $notification['notificationDate'] ?? null,
                'processed_at' => date('Y-m-d H:i:s')
            ];

            // Parse UBL XML and create Perfex CRM document
            $result = $this->_create_document_from_ubl($ubl_response['ubl_xml'], $notification);
            if ($result['success']) {
                $document_data['document_type'] = $result['document_type'];
                $document_data['document_id'] = $result['document_id'];
            } else {
                $document_data['error'] = $result['message'];
            }

            return [
                'success' => true,
                'data' => $document_data,
                'message' => 'Incoming document processed successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to process incoming document: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update status of a sent document based on notification
     */
    private function _update_document_status($notification)
    {
        $CI = &get_instance();
        $CI->load->model('peppol/peppol_model');

        try {
            $document_id = $notification['documentId'] ?? null;
            $transmission_id = $notification['transmissionId'] ?? null;
            $status = $notification['documentStatus'] ?? $notification['eventType'];
            $event_type = $notification['eventType'];

            if (!$document_id && !$transmission_id) {
                return [
                    'success' => false,
                    'message' => 'No document ID or transmission ID provided'
                ];
            }

            // Find the PEPPOL document record
            $peppol_document = null;
            if ($transmission_id) {
                $peppol_document = $CI->peppol_model->get_peppol_document_by_metadata('transmissionId', $transmission_id, $this->get_id());
            }

            if (!$peppol_document && $document_id) {
                $peppol_document = $CI->peppol_model->get_peppol_document_by_provider_id($document_id, $this->get_id());
            }

            if (!$peppol_document) {
                return [
                    'success' => false,
                    'message' => 'PEPPOL document not found for update'
                ];
            }

            // Map Ademico status to internal status
            $internal_status = $this->_map_status_to_internal($status, $event_type);

            // Update the document status
            $update_data = [
                'status' => $internal_status,
                'provider_metadata' => json_encode(array_merge(
                    json_decode($peppol_document->provider_metadata ?? '{}', true),
                    [
                        'last_status_update' => date('Y-m-d H:i:s'),
                        'ademico_status' => $status,
                        'event_type' => $event_type,
                        'notification_date' => $notification['notificationDate'] ?? null
                    ]
                ))
            ];

            $updated = $CI->peppol_model->update_peppol_document($peppol_document->id, $update_data);

            if ($updated) {
                $status_data = [
                    'peppol_document_id' => $peppol_document->id,
                    'document_type' => $peppol_document->document_type,
                    'document_id' => $peppol_document->document_id,
                    'old_status' => $peppol_document->status,
                    'new_status' => $internal_status,
                    'provider_status' => $status,
                    'event_type' => $event_type,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                return [
                    'success' => true,
                    'data' => $status_data,
                    'message' => 'Document status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update document status in database'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update document status: ' . $e->getMessage()
            ];
        }
    }


    /**
     * Create Perfex CRM document from UBL XML using service layer
     * 
     * @param string $ubl_xml UBL XML content
     * @param array $notification Notification data
     * @return array Creation result
     */
    private function _create_document_from_ubl($ubl_xml, $notification)
    {
        // Load PEPPOL service
        $this->CI->load->library('peppol/peppol_service');

        // Prepare metadata including notification info with Ademico-specific fields
        $extra_data = [
            'provider' => $this->get_id(),
            'received_at' => $notification['receivedDate'] ?? $notification['notificationDate'] ?? date('Y-m-d H:i:s'),
            'metadata' => $notification,
        ];

        // Extract document ID from notification
        $document_id = $notification['documentId'] ?? 'unknown';

        // Use service layer to create document
        return $this->CI->peppol_service->create_document_from_ubl($ubl_xml, $document_id, $extra_data);
    }



    /**
     * Map Ademico status to internal PEPPOL status
     */
    private function _map_status_to_internal($ademico_status, $event_type)
    {
        switch ($event_type) {
            case 'DOCUMENT_SENT':
                return 'sent';
            case 'DOCUMENT_SEND_FAILED':
                return 'failed';
            case 'MLR_RECEIVED':
                return ($ademico_status === 'REJECTED') ? 'rejected' : 'delivered';
            case 'INVOICE_RESPONSE_RECEIVED':
                switch ($ademico_status) {
                    case 'ACCEPTED':
                        return 'accepted';
                    case 'REJECTED':
                        return 'rejected';
                    case 'FULLY_PAID':
                        return 'paid';
                    case 'PARTIALLY_PAID':
                        return 'partial_paid';
                    default:
                        return 'processed';
                }
            default:
                return 'sent';
        }
    }

    /**
     * Get UBL XML content for a document by transmission ID or document object
     * 
     * @param string|object $identifier The transmission ID (string) or PEPPOL document object
     * @param array $metadata Optional metadata for additional context (when using string identifier)
     * @return array Response with UBL XML content
     */
    public function get_document_ubl($identifier, $metadata = [])
    {
        $settings = $this->get_settings();

        try {
            // Handle method overloading - extract transmission_id from identifier
            $transmission_id = null;
            $document_metadata = $metadata;

            if (is_string($identifier)) {
                // Direct transmission ID passed as string
                $transmission_id = $identifier;
            } elseif (is_object($identifier)) {
                // PEPPOL document object passed
                // Check if metadata is already decoded (from model method), otherwise decode it
                if (isset($identifier->metadata) && is_array($identifier->metadata)) {
                    $document_metadata = $identifier->metadata;
                } else {
                    $document_metadata = json_decode($identifier->provider_metadata ?? '{}', true);
                }
                
                // Try to get transmission_id from metadata first, fallback to provider_document_id
                if (isset($document_metadata['transmissionId'])) {
                    $transmission_id = $document_metadata['transmissionId'];
                } else {
                    $transmission_id = $identifier->provider_document_id;
                }
            } else {
                return [
                    'success' => false,
                    'message' => _l('peppol_invalid_identifier_type')
                ];
            }

            if (empty($transmission_id)) {
                return [
                    'success' => false,
                    'message' => _l('peppol_no_transmission_id')
                ];
            }

            $token = $this->get_access_token($settings);
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Failed to obtain access token for UBL retrieval'
                ];
            }

            // Get UBL endpoint with transmission ID and /ubl suffix
            $endpoint = $this->get_endpoint(self::ENDPOINT_GET_UBL) . '/' . $transmission_id . '/ubl';

            $headers = [
                'Authorization: ' . $token,
                'Accept: application/xml'
            ];

            // Use a special method for XML response
            $response = $this->call_ubl_api($endpoint, $headers, $settings);

            if ($response['success']) {
                return [
                    'success' => true,
                    'ubl_xml' => $response['xml_content'],
                    'transmission_id' => $transmission_id,
                    'content_type' => 'application/xml'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => sprintf(_l('peppol_failed_to_retrieve_ubl') . ': %s', ($response['error'] ?? _l('peppol_ademico_unknown_error')))
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(_l('peppol_failed_to_retrieve_ubl') . ': %s', $e->getMessage())
            ];
        }
    }

    /**
     * Make HTTP API call specifically for UBL XML content
     * 
     * @param string $url The full endpoint URL to call
     * @param array $headers HTTP headers to include in the request
     * @param array $settings Provider settings
     * @return array Response array with 'success' boolean and 'xml_content'/'error' keys
     */
    private function call_ubl_api($url, $headers = [], $settings = [])
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $settings['timeout'] ?? 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'CURL Error: ' . $error];
        }

        if ($http_code >= 200 && $http_code < 300) {
            // Check if response is XML
            if (strpos($content_type, 'application/xml') !== false || strpos($content_type, 'text/xml') !== false) {
                return [
                    'success' => true,
                    'xml_content' => $response,
                    'content_type' => $content_type
                ];
            } else {
                // Try to parse as JSON error response
                $json_response = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($json_response['message'])) {
                    return ['success' => false, 'error' => $json_response['message']];
                }

                return [
                    'success' => false,
                    'error' => 'Expected XML content but received: ' . $content_type
                ];
            }
        } else {
            $error_message = 'HTTP ' . $http_code;

            // Try to parse JSON error response
            if ($response) {
                $json_response = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $parsed_error = $this->parse_ademico_error($json_response);
                    $error_message .= ': ' . $parsed_error;
                } else {
                    // Include raw response if not JSON
                    $error_message .= ' - ' . substr($response, 0, 200);
                }
            }

            return ['success' => false, 'error' => $error_message];
        }
    }

    /**
     * Consume (delete) a notification to remove it from the queue
     * 
     * @param int $notification_id The notification ID to consume
     * @return array Response with success status
     */
    public function consume_notification($notification_id)
    {
        $settings = $this->get_settings();

        try {
            $token = $this->get_access_token($settings);
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Failed to obtain access token for notification consumption'
                ];
            }

            // Build DELETE endpoint URL
            $endpoint = $this->get_endpoint(self::ENDPOINT_NOTIFICATIONS) . '/' . $notification_id;

            $headers = [
                'Authorization: ' . $token,
                'Content-Type: application/json'
            ];

            $response = $this->call_api($endpoint, null, $headers, [], 'DELETE');

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Notification consumed successfully',
                    'notification_id' => $notification_id
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to consume notification: ' . ($response['error'] ?? 'Unknown error')
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to consume notification: ' . $e->getMessage()
            ];
        }
    }
}