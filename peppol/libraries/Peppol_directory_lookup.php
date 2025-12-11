<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Simplified Peppol Directory Lookup Library
 */
class Peppol_directory_lookup
{
    protected $CI;
    protected $api_base_url = 'https://directory.peppol.eu/api/v1';
    protected $timeout = 30;

    // Peppol scheme identifiers mapping
    const SCHEME_MAPPING = [
        'BE' => '0208', 'NL' => '0106', 'DE' => '9930', 'FR' => '0009', 'IT' => '9907',
        'ES' => '9920', 'DK' => '0184', 'NO' => '9908', 'SE' => '0007', 'FI' => '0037',
        'AT' => '0204', 'PL' => '9915', 'CZ' => '9954', 'SK' => '9953', 'HU' => '9910',
        'RO' => '9920', 'BG' => '9923', 'HR' => '9946', 'SI' => '9948', 'LT' => '9914',
        'LV' => '9917', 'EE' => '9913', 'IE' => '9906', 'LU' => '9928', 'PT' => '9920',
        'MT' => '9912', 'CY' => '9963', 'GR' => '9931'
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('clients_model');
    }

    /**
     * Auto-lookup and update customer Peppol fields
     * 
     * @param int $customer_id Customer ID
     * @return array Result array
     */
    public function auto_lookup_customer($customer_id)
    {
        $customer = $this->CI->clients_model->get($customer_id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Customer not found'];
        }

        // Build search terms
        $search_terms = [];
        if (!empty($customer->company)) {
            $search_terms[] = $customer->company;
        }

        // Try VAT search first if available
        if (!empty($customer->vat)) {

            $search_terms[] = $customer->vat;

            $result = $this->search_by_vat($customer->vat, $customer->country);
            if ($result['success'] && !empty($result['participant'])) {
                return $this->update_customer_fields($customer_id, $result['participant']);
            }
        }

        // Fallback to company name search
        if (!empty($search_terms)) {
            $result = $this->search_directory($search_terms);
            if ($result['success'] && !empty($result['participants'])) {
                if (count($result['participants']) === 1) {
                    // Single result - auto-update
                    return $this->update_customer_fields($customer_id, $result['participants'][0]);
                } else {
                    // Multiple results - return for manual selection
                    return [
                        'success' => false,
                        'message' => 'Multiple participants found (' . count($result['participants']) . ' results). Manual selection required.',
                        'multiple_results' => true,
                        'participants' => $result['participants']
                    ];
                }
            }
        }

        return ['success' => false, 'message' => 'No participants found'];
    }

    /**
     * Search by VAT number
     */
    public function search_by_vat($vat, $country_code = null)
    {
        $vat = $this->clean_vat($vat);

        // Extract country from VAT if not provided
        if (!$country_code && preg_match('/^([A-Z]{2})/', $vat, $matches)) {
            $country_code = $matches[1];
            $vat = substr($vat, 2);
        }

        if (!$country_code || !isset(self::SCHEME_MAPPING[$country_code])) {
            return ['success' => false, 'message' => 'Invalid country or VAT'];
        }

        $scheme = self::SCHEME_MAPPING[$country_code];
        $identifier = $scheme . ':' . $vat;

        $url = $this->api_base_url . '/participants/' . urlencode($identifier);
        $response = $this->make_request($url);

        if ($response['success']) {
            return [
                'success' => true,
                'participant' => $this->format_participant($response['data'])
            ];
        }

        return ['success' => false, 'message' => 'Participant not found'];
    }

    /**
     * Search directory by terms
     */
    public function search_directory($search_terms)
    {
        $query = is_array($search_terms) ? implode(' ', $search_terms) : $search_terms;
        $url = $this->api_base_url . '/participants?' . http_build_query(['q' => $query, 'limit' => 10]);

        $response = $this->make_request($url);
        if (!$response['success']) {
            return $response;
        }

        $participants = [];
        foreach ($response['data'] as $item) {
            $participants[] = $this->format_participant($item);
        }

        return ['success' => true, 'participants' => $participants];
    }

    /**
     * Update customer custom fields
     */
    public function update_customer_fields($customer_id, $participant)
    {
        if (empty($participant['scheme']) || empty($participant['identifier'])) {
            return ['success' => false, 'message' => 'Invalid participant data'];
        }

        // Update custom fields using existing slugs from install.php
        $update_data = [
            'custom_fields' => [
                'customers_peppol_scheme' => $participant['scheme'],
                'customers_peppol_identifier' => $participant['identifier']
            ]
        ];

        $updated = $this->CI->clients_model->update($customer_id, $update_data);

        if ($updated) {
            return [
                'success' => true,
                'message' => 'Customer Peppol fields updated successfully',
                'participant' => $participant
            ];
        }

        return ['success' => false, 'message' => 'Failed to update customer'];
    }

    /**
     * Format participant data
     */
    private function format_participant($data)
    {
        $identifiers = $data['identifiers'] ?? [];
        $primary = !empty($identifiers) ? $identifiers[0] : null;

        return [
            'name' => $data['name'] ?? 'Unknown',
            'scheme' => $primary['scheme'] ?? null,
            'identifier' => $primary['value'] ?? null,
            'country' => $data['countryCode'] ?? null
        ];
    }

    /**
     * Clean VAT number
     */
    private function clean_vat($vat)
    {
        return strtoupper(str_replace([' ', '-', '.', 'VAT', 'BTW'], '', trim($vat)));
    }

    /**
     * Make API request
     */
    private function make_request($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            return ['success' => true, 'data' => $data];
        }

        return ['success' => false, 'message' => "HTTP $http_code"];
    }
}