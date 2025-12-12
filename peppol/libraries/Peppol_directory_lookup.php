<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Simplified Peppol Directory Lookup Library
 */
class Peppol_directory_lookup
{
    protected $CI;
    protected $api_base_url = 'https://directory.peppol.eu/search/1.0/json';
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
        }

        if (!empty($customer->website)) {
            $search_terms[] = $customer->website;
        }

        // Fallback to company name search
        if (!empty($search_terms)) {
            $result = $this->search_directory($search_terms);
            if ($result['success'] && !empty($result['participants'])) {
                if (count($result['participants']) === 1) {
                    // Single result - auto-update
                    return $this->update_customer_fields($customer_id, $result['participants'][0]);
                } else {
                    // Multiple results - apply smart VAT matching
                    $selected_participant = $this->smart_vat_matching($customer, $result['participants']);

                    if ($selected_participant) {
                        // Exact VAT match found - auto-update
                        return $this->update_customer_fields($customer_id, $selected_participant);
                    } else {
                        // No clear match - return for manual selection
                        return [
                            'success' => false,
                            'message' => 'Multiple participants found (' . count($result['participants']) . ' results). Manual selection required.',
                            'multiple_results' => true,
                            'participants' => $result['participants'],
                            'customer_data' => [
                                'userid' => $customer_id,
                                'company' => $customer->company,
                                'vat' => $customer->vat
                            ]
                        ];
                    }
                }
            }
        }

        return ['success' => false, 'message' => 'No participants found'];
    }

    /**
     * Search directory by terms - updated for real Peppol directory response format
     */
    public function search_directory($search_terms)
    {
        $query = is_array($search_terms) ? implode(' ', $search_terms) : $search_terms;
        $url = $this->api_base_url . '?' . http_build_query(['q' => $query, 'limit' => 20]);

        $response = $this->make_request($url);
        if (!$response['success']) {
            return $response;
        }

        // Parse real Peppol directory response format
        $directory_data = $response['data'];
        $matches = $directory_data['matches'] ?? [];

        if (empty($matches)) {
            return ['success' => true, 'participants' => []];
        }

        $participants = [];
        foreach ($matches as $match) {
            $participants[] = $this->format_participant($match);
        }

        return ['success' => true, 'participants' => $participants, 'total_count' => $directory_data['total-result-count'] ?? count($participants)];
    }

    /**
     * Smart VAT matching - finds exact VAT match if any, returns null if multiple or none
     */
    private function smart_vat_matching($customer, $participants)
    {
        if (empty($customer->vat)) {
            return null; // No customer VAT to match against
        }

        // Clean customer VAT (remove non-digits for comparison)
        $customer_vat_clean = preg_replace('/\D/', '', $customer->vat);

        $exact_matches = [];
        foreach ($participants as $participant) {
            if (!empty($participant['vat'])) {
                // Clean participant VAT for comparison
                $participant_vat_clean = preg_replace('/\D/', '', $participant['vat']);

                if ($participant_vat_clean === $customer_vat_clean) {
                    $exact_matches[] = $participant;
                }
            }
        }

        // Return participant only if exactly one VAT match found
        return (count($exact_matches) === 1) ? $exact_matches[0] : null;
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
     * Format participant data from real Peppol directory response structure
     */
    private function format_participant($match)
    {
        // Extract participant ID information
        $participant_id = $match['participantID'] ?? [];
        $scheme = $participant_id['scheme'] ?? '';
        $identifier = $participant_id['value'] ?? '';

        if (strpos($identifier, ':') !== false) {
            $parts = explode(':', $identifier);
            $scheme = $parts[0] ?? ''; // e.g., "0208"
            $identifier = $parts[1] ?? ''; // e.g., "0552912569"
        }

        // Extract entity information (use first entity)
        $entities = $match['entities'] ?? [];
        $entity = !empty($entities) ? $entities[0] : [];

        // Extract name (use first name entry)
        $names = $entity['name'] ?? [];
        $name = !empty($names) ? ($names[0]['name'] ?? 'Unknown') : 'Unknown';

        // Extract country code
        $country_code = $entity['countryCode'] ?? '';

        // Extract VAT from identifiers
        $vat = null;
        $identifiers = $entity['identifiers'] ?? [];
        foreach ($identifiers as $id) {
            if (($id['scheme'] ?? '') === 'VAT') {
                $vat = $id['value'] ?? null;
                break;
            }
        }

        return [
            'name' => $name,
            'company' => $name, // Alias for compatibility
            'scheme' => $scheme,
            'identifier' => $identifier,
            'country' => $country_code,
            'vat' => $vat,
            'geo_info' => $entity['geoInfo'] ?? '',
            'websites' => $entity['websites'] ?? [],
            'reg_date' => $entity['regDate'] ?? ''
        ];
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