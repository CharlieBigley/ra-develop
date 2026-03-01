<?php
namespace Ramblers\Component\Ra_develop\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;

class JsonHelper
{
    /**
     * Execute a curl command to fetch API data
     * @param int $api_site_id ID of the record in api_sites
     * @param string $endpoint The required endpoint URL
     * @param int $verbose Verbose flag (0/1)
     * @return array Decoded response or error info
     */
    public static function fetchApiData($api_site_id, $endpoint, $verbose = 0)
    {
        $db = Factory::getDbo();
        // Get token for api_site_id
        $query = $db->getQuery(true)
            ->select($db->quoteName(['token', 'url']))
            ->from($db->quoteName('#__ra_api_sites'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $api_site_id, ParameterType::INTEGER);
        $db->setQuery($query);
        $site = $db->loadObject();
        if (!$site || empty($site->token)) {
            return ['error' => 'API site or token not found'];
        }
        $token = $site->token;
        $url = $site->url . $endpoint;
        $headers = [
            'Accept: application/vnd.api+json',
            'Content-Type: application/json',
            'X-Joomla-Token: ' . $token,
            'Authorization: Bearer ' . $token
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $curl_info = curl_getinfo($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        // Split headers/body
        $header_size = $curl_info['header_size'] ?? 0;
        $raw_headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $decoded = json_decode($body, true);
        if ($verbose) {
            // Log to DB with correct columns
            $log_date = date('Y-m-d H:i:s');
            $sub_system = 'RA Develop';
            $record_type = 11;
            $ref = 'builds';
            $message = "Endpoint: $endpoint\n" .
                "API Site ID: $api_site_id\n" .
                "Headers: " . json_encode($headers) . "\n" .
                "Raw Headers: $raw_headers\n" .
                "Body: $body\n" .
                "Curl Info: " . json_encode($curl_info) . "\n" .
                "Curl Error: $curl_error\n" .
                "Decoded: " . json_encode($decoded);
            $query = "INSERT INTO #__ra_logfile (`log_date`, `sub_system`, `record_type`, `ref`, `message`) VALUES (" .
                $db->quote($log_date) . ", " .
                $db->quote($sub_system) . ", " .
                (int)$record_type . ", " .
                $db->quote($ref) . ", " .
                $db->quote($message) . ")";
            $db->setQuery($query);
            $db->execute();
        }
        return $decoded ?: ['error' => $curl_error ?: 'No response'];
    }

    /**
     * Display all fields from the first record in the response
     * @param array $response Decoded API response
     * @return array List of field names and values
     */
    public static function displayFields($response)
    {
        if (!isset($response['data'][0])) {
            return ['error' => 'No records found'];
        }
        return $response['data'][0]['attributes'] ?? $response['data'][0];
    }
}
