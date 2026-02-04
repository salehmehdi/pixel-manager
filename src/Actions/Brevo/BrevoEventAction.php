<?php

namespace SalehSignal\PixelManager\Actions\Brevo;

use Illuminate\Support\Facades\Http;
use Exception;

class BrevoEventAction
{
    /**
     * Execute the Brevo event action.
     *
     * @param  array  $arg
     * @param  array  $application
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute($arg, $application)
    {
        try {
            $apiUrl = 'https://api.brevo.com/v3/events';
            $apiKey = $application['brevo_api_key'] ?? null;

            if (!$apiKey) {
                throw new Exception('API key required for Brevo');
            }

            $data = $arg['data'];

            $eventData = $this->setEvents($data);

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'api-key' => $apiKey
            ])->post($apiUrl, $eventData);

            \Log::info('Pixel Manager: Brevo Event Sent', [
                'event_name' => $eventData['event_name'] ?? 'unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Brevo event tracked successfully',
                'data' => $response->json()
            ]);
        } catch (Exception $e) {
            \Log::error('Pixel Manager: Brevo Event Failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error tracking brevo event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set event data for Brevo API.
     *
     * @param  array  $data
     * @return array
     */
    private function setEvents($data)
    {
        $event = [];

        if (isset($data['event'])) {
            $event['event_name'] = $data['event'];
        }

        $userData = $this->setUserData($data);

        $identifiers = $userData['identifiers'];
        if ($identifiers) {
            $event['identifiers'] = $identifiers;
        }

        $contactProperties = $userData['contact_properties'];
        if ($contactProperties) {
            $event['contact_properties'] = $contactProperties;
        }

        $eventProperties = $this->setCustomData($data);
        if ($eventProperties) {
            $event['event_properties'] = $eventProperties;
        }

        return $event;
    }

    /**
     * Set user data for Brevo API.
     *
     * @param  array  $data
     * @return array
     */
    private function setUserData($data)
    {
        $customer = $data['customer'] ?? [];

        $identifiers = $contactProperties = [];

        $email = $customer['email'] ?? null;

        $ip = data_get($customer, 'ip_address');

        if (count(explode(',', $ip)) > 1) {
            $ip = current(explode(',', $ip));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        $phone = null;
        if (isset($customer['phone']) && isset($customer['phone_code'])) {
            $phone = $customer['phone_code'] . '' . $customer['phone'];
        }

        if ($phone && strpos($phone, '+') !== 0) {
            $phone = '+' . $phone;
        }

        // NOTE: At least one identifier is required
        if ($email) {
            $identifiers['email_id'] = $email;
            $contactProperties['email'] = $email;
        }

        if ($phone) {
            // Uncomment if you want to use phone as identifier
            // $identifiers['phone_id'] = $phone;
        }

        if (isset($customer['whatsapp_id'])) {
            $identifiers['whatsapp_id'] = $customer['whatsapp_id'];
        }

        if (isset($customer['andline_number_id'])) {
            $identifiers['andline_number_id'] = $customer['andline_number_id'];
        }

        if (isset($customer['external_id'])) {
            $identifiers['ext_id'] = $customer['external_id'];
        }

        if ($ip) {
            $contactProperties['ip'] = $ip;
        }

        if (isset($customer['user_agent'])) {
            $contactProperties['user_agent'] = $customer['user_agent'];
        }

        if (isset($customer['gender'])) {
            $contactProperties['gender'] = $customer['gender'];
        }

        // BUG FIX: Changed 'datee_of_birth' to 'date_of_birth'
        if (isset($customer['date_of_birth'])) {
            $contactProperties['date_of_birth'] = $customer['date_of_birth'];
        }

        if (isset($customer['last_name'])) {
            $contactProperties['lastName'] = $customer['last_name'];
        }

        if (isset($customer['first_name'])) {
            $contactProperties['firstName'] = $customer['first_name'];
        }

        if (isset($customer['city'])) {
            $contactProperties['city'] = $customer['city'];
        }

        if (isset($customer['state'])) {
            $contactProperties['state'] = $customer['state'];
        }

        if (isset($customer['country_code'])) {
            $contactProperties['country_code'] = $customer['country_code'];
        }

        // BUG FIX: Changed $customer['zipcode'] to $customer['zip_code']
        if (isset($customer['zip_code'])) {
            $contactProperties['zip_code'] = $customer['zip_code'];
        }

        return ['identifiers' => $identifiers, 'contact_properties' => $contactProperties];
    }

    /**
     * Set custom event data for Brevo API.
     *
     * @param  array  $data
     * @return array
     */
    private function setCustomData($data)
    {
        $customData = [];

        if (isset($data['transaction_id'])) {
            $customData['cart_id'] = $data['transaction_id'];
        }

        if (isset($data['page_url'])) {
            $customData['page_url'] = $data['page_url'];
        }

        if (isset($data['value'])) {
            $customData['value'] = (float) $data['value'];
        }

        if (isset($data['currency'])) {
            $customData['currency'] = $data['currency'];
        }

        $item = $data['items'] ?? [];
        $contents = $this->setContents($item);
        if ($contents) {
            $customData['items'] = $contents;
        }

        if (isset($data['order_id'])) {
            $customData['order_id'] = $data['order_id'];
        }

        if (isset($data['search_term'])) {
            $customData['search_term'] = $data['search_term'];
        }

        if (isset($data['shipping'])) {
            $customData['shipping'] = (float) $data['shipping'];
        }

        return $customData;
    }

    /**
     * Set contents/items data for Brevo API.
     *
     * @param  array  $items
     * @return array
     */
    private function setContents($items)
    {
        if (!$items) {
            return [];
        }

        $contents = [];
        foreach ($items as $item) {
            $content = [];

            if (isset($item['price'])) {
                $content['price'] = (float) $item['price'];
            }

            if (isset($item['item_id'])) {
                $content['product_id'] = $item['item_id'];
            }

            if (isset($item['quantity'])) {
                $content['quantity'] = $item['quantity'];
            }

            if (isset($item['item_name'])) {
                $content['title'] = $item['item_name'];
            }

            if (isset($item['photo'])) {
                $content['photo'] = $item['photo'];
            }

            if (isset($item['description'])) {
                $content['description'] = $item['description'];
            }

            if (isset($item['item_brand'])) {
                $content['brand'] = $item['item_brand'];
            }

            if (isset($item['category'])) {
                $content['category'] = $item['category'];
            }

            if (isset($item['delivery_category'])) {
                $content['delivery_category'] = $item['delivery_category'];
            }

            if (isset($item['color'])) {
                $content['color'] = $item['color'];
            }

            if (isset($item['item_variant']) && isset($item['item_variant']['color'])) {
                $content['color_title'] = $item['item_variant']['color'];
            }

            if (isset($item['size'])) {
                $content['size'] = $item['size'];
            }

            if (isset($item['item_variant']) && isset($item['item_variant']['size'])) {
                $content['size_title'] = $item['item_variant']['size'];
            }

            $contents[] = $content;
        }

        return $contents;
    }
}
