<?php

namespace SalehSignal\PixelManager\Actions\TikTok;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class TikTokEventAction
{
    public function execute($arg, $application)
    {
        try
        {
            $data = $arg['data'];
            $pixelCode = $application['tiktok_pixel_code'];
            $accessToken = $application['tiktok_access_token'];
            if(!$accessToken || !$pixelCode)
            {
                throw new \Exception('access_token or pixel_code not found');
            }

            $url = "https://business-api.tiktok.com/open_api/v1.3/event/track/";

            $eventName = isset($data['event']) ? $data['event'] : null;
            $eventName = match ($eventName)
            {
                'add_to_cart'            => 'AddToCart',
                'view_item'              => 'ViewContent',
                'purchase'               => 'CompletePayment',
                'completed_registration' => 'CompleteRegistration',
                'page_view'              => 'Pageview',
                'search'                 => 'Search',
                'subscription'           => 'Subscribe',
                'begin_checkout'         => 'InitiateCheckout',
                'add_to_wishlist'        => 'AddToWishlist',
                'add_payment_info'       => 'AddPaymentInfo',
                default                  => null,
            };

            $eventData = [
                'event' => $eventName, // require
                'event_time' => strtotime($this->setTimestamp(isset($data['timestamp']) ? $data['timestamp'] : null)), // required
                'event_id' => $data['event_id'] ?? null
            ];

            // User data
            $userData = $this->setUserData($data);
            if ($userData)
            {
                $eventData['user'] = $userData;
            }

            // Page data
            $pageData = $this->setPage($data);
            if ($pageData)
            {
                $eventData['page'] = $pageData;
            }

            // Properties data
            $propertiesData = $this->setCustomData($data);
            if ($propertiesData)
            {
                $eventData['properties'] = $propertiesData;
            }

            $payload = [
                'event_source' => 'web',
                'event_source_id' => $pixelCode, // required
                'data' => [$eventData] // required
            ];

            // Add test_event_code if exists
            if (isset($application['data']['test_event_code'])) {
                $payload['test_event_code'] = $application['data']['test_event_code'];
            }

            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            \Log::info(['tiktok' => $response]);

            if (!$response->successful())
            {
                throw new \Exception('Failed to send TikTok event: ' . $response->body());
            }

            return $response->json();
        }
        catch (\InvalidArgumentException $e)
        {
            throw new \InvalidArgumentException('Invalid argument: ' . $e->getMessage());
        }
        catch (\Exception $e)
        {
            throw new \Exception('Error occurred while sending TikTok event: ' . $e->getMessage());
        }
    }

    private function setUserData($data)
    {
        $customer = $data['customer'] ?? [];
        $user = [];

        // Email handling
        $email = isset($customer['email']) ? $customer['email'] : null;
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $email = strtolower($email);
            $user['email'] = [hash('sha256', $email)];
        }

        // Phone handling
        $phone = isset($customer['phone']) ? $customer['phone'] : null;
        if ($phone)
        {
            $phone = ($customer['phone_code'] ?? '') . $phone;
            if (strpos($phone, '+') !== 0)
            {
                $phone = '+' . $phone;
            }
            $user['phone'] = [hash('sha256', $phone)];
        }

        // TTP handling
        if (isset($data['ttp']))
        {
            $user['ttp'] = $data['ttp'];
        }

        // TTCLID handling
        if (isset($customer['click_value']['ttclid']))
        {
            $user['ttclid'] = $customer['click_value']['ttclid'];
        }

        // IP Address
        if (isset($customer['ip_address']))
        {
            $ip = $customer['ip_address'];
            if (count(explode(',', $ip)) > 1)
            {
                $ip = current(explode(',', $ip));
            }
            $user['ip'] = $ip;
        }

        // User Agent
        if (isset($customer['user_agent']))
        {
            $user['user_agent'] = $customer['user_agent'];
        }

        return $user;
    }

    private function setPage($data)
    {
        $page = [];

        if (isset($data['page_url']))
        {
            $page['url'] = $data['page_url'];
        }

        if (isset($data['referrer']))
        {
            $page['referrer'] = $data['referrer'];
        }

        return $page;
    }

    private function setCustomData($data)
    {
        $properties = [];

        // Contents handling
        $contents = $this->setContents($data['items'] ?? []);
        if ($contents)
        {
            $properties['contents'] = $contents;
        }

        if (isset($data['content_type']))
        {
            $properties['content_type'] = $data['content_type'];
        }

        if (isset($data['currency']))
        {
            $properties['currency'] = $data['currency'];
        }

        if (isset($data['value']))
        {
            $properties['value'] = (float) $data['value'];
        }

        return $properties;
    }

    private function setContents($items)
    {
        if (empty($items))
        {
            return [];
        }

        $contents = [];

        foreach ($items as $item)
         {
            $content = [];

            if (isset($item['price']))
            {
                $content['price'] = (float) $item['price'];
            }

            if (isset($item['quantity']))
            {
                $content['quantity'] = (int) $item['quantity'];
            }

            if (isset($item['item_id']))
            {
                $content['content_id'] = (string) $item['item_id'];
            }

            if (isset($item['item_name']))
            {
                $content['content_name'] = $item['item_name'];
            }

            if (isset($item['category']) && is_array($item['category']))
            {
                $content['content_category'] = implode(' > ', $item['category']);
            }

            if (isset($item['item_brand']))
            {
                $content['brand'] = $item['item_brand'];
            }

            $contents[] = $content;
        }

        return $contents;
    }

    private function setTimestamp($timestamp = null)
    {
        if (!$timestamp || !$this->validateTimestampFormat($timestamp)) {
            $timestamp = date('Y-m-d\TH:i:s\Z');
        }

        return $timestamp;
    }

    private function validateTimestampFormat($timestamp)
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/';
        return preg_match($pattern, $timestamp);
    }
}
