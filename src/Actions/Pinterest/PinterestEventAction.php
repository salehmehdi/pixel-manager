<?php

namespace SalehSignal\PixelManager\Actions\Pinterest;

use Illuminate\Support\Facades\Response;
use Exception;
use App\Exceptions\ApplicationError;
use Illuminate\Support\Facades\Http;
use App;
use Auth;
use Illuminate\Support\Str;

class PinterestEventAction
{

    public function execute($arg, $application)
    {
        try
        {
            if(config('app.env') != 'production' || botDetected())
            {
                //return [];
            }

            $accessToken  = $application['pinterest_access_token'];
            $accountId    = $application['pinterest_account_id'];
            if(!$accessToken || !$accountId)
            {
                throw new \Exception('access_token or account_id not found');
            }

            $url = "https://api-sandbox.pinterest.com/ad_accounts/{$accountId}/events?test=true";


            $data = $arg['data'];

            $events = $this->setEvents($data);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post($url, $events);
            \Log::info($response);

            if (!$response->successful())
            {
                throw new \Exception('Failed to send Pinteres  event: ' . $response->body());
            }

            return $response->json();

        }
        catch (RequestException $requestException)
        {
            return true;
        }

        catch (ApplicationError $e)
        {
            \Log::error($e->getMessageArr());
        }
        catch (Exception $e)
        {
            \Log::error(__CLASS__." ".$e->getMessage()." ".$e->getFile()." ".$e->getLine());
        }
    }

    private function setEvents($data)
    {
        $event = [];

        $eventName = isset($data['event']) ? $data['event'] : null;
        $eventName =  match ($eventName)
        {
            'add_to_cart'            => 'add_to_cart',
            'view_item'              => "page_visit", // ViewContent doesnt exist for pint,
            'purchase'               => 'checkout',
            'completed_registration' => 'signup',
            'page_view'              => 'page_visit',
            'search'                 => 'search',
            //'subscription'         => "subscription", // Subscription doesnt exist for pint,
            //'begin_checkout'       => "begin_checkout", // begin checkout  doesnt exist for pint,
            //'view_cart'            => "view_cart", // view cart  doesnt exist for pint,
            //'add_to_wishlist'      => "add_to_vishlist", // add_to_wishlist  doesnt exist for pint,
            //'add_payment_info'     => "add_payment_info", // add payment info  doesnt exist for pint,
            default                  => null,
        };

        if($eventName)
        {
            $event['event_name'] = $eventName;
        }

        $eventId = (string) $data['event_id'];
        $eventId = $eventName ? $eventName.'.'.$eventId : $eventId;

        $event['event_id'] = $eventId;

        $event['event_time'] = (time());
        $event['action_source'] = 'website';

        $userData = $this->setUserData($data);
        $event['user_data'] = $userData;

        $customData = $this->setCustomData($data);
        if($customData)
        {
            $event['custom_data'] = $customData;
        }

        if(isset($data['page_url']))
        {
            $event['event_source_url'] = $data['page_url'];
        }

        if(isset($data['partner_name']))
        {
            $event['partner_name'] = $data['partner_name'];
        }

        /**
         * bool ... Sets a flag that indicates Facebook should or should not use this event for ads delivery optimization
         */
        if(isset($data['opt_out']))
        {
            $event['opt_out'] = $data['event_name'];
        }

        $events['data'] = [];
        array_push($events['data'], $event);
        return $events;
    }

    private function setUserData($data)
    {
        $customer = $data['customer'];
        $phone = isset($customer['phone']) ? $customer['phone'] : null;
        if($phone)
        {
            $phone = data_get($customer, 'phone_code').''.$phone;
        }

        $email  = isset($customer['email']) ? $customer['email'] : null;

        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $email = null;
        }

        $ip = data_get($customer, 'ip_address');

        if( count(explode(',', $ip)) > 1 )
        {
            $ip = current(explode(',', $ip));
        }

        $userData = [];

        if($email)
        {
            $email = strtolower($email);
            $email = hash('sha256', $email);
            $userData['em'] = $email;
        }

        if($phone)
        {
            $userData['ph'] = hash('sha256', $phone);
        }

        if($ip)
        {
            $userData['client_ip_address'] = $ip;
        }

        if(isset($customer['user_agent']))
        {
            $userData['client_user_agent'] = $customer['user_agent'];
        }

        if(isset($customer['gender']))
        {
            $userData['ge'] = hash('sha256', $customer['gender']);
        }

        if(isset($customer['date_of_birth']))
        {
            $userData['db'] = hash('sha256', $customer['date_of_birth']);
        }

        if(isset($customer['last_name']))
        {
            $userData['ln'] = hash('sha256', $customer['last_name']);
        }

        if(isset($customer['first_name']))
        {
            $userData['fn'] = hash('sha256', $customer['first_name']);
        }

        if(isset($customer['city']))
        {
            $userData['ct'] = hash('sha256', $customer['city']);
        }

        if(isset($customer['state']))
        {
            $userData['st'] = hash('sha256', $customer['state']);
        }

        if(isset($customer['country_code']))
        {
            $userData['country'] = hash('sha256', $customer['country_code']);
        }

        if(isset($customer['zip_code']))
        {
            $userData['zp'] = hash('sha256', $customer['zip_code']);
        }

        if(isset($customer['external_id']))
        {
            $userData['external_id'] = hash('sha256', $customer['external_id']);
        }

        if(isset($customer['click_value']) && isset($customer['click_value']['epik']))
        {
            $userData['click_id'] = $customer['click_value']['epik'];
        }

        if(isset($data['partner_id']))
        {
            $userData['partner_id'] = $data['partner_id'];
        }
        return $userData;
    }

    private function setCustomData($data)
    {
        $customData = [];
        if(isset($data['value']))
        {
            $customData['value'] = $data['value'];
        }

        if(isset($data['currency']))
        {
            $customData['currency'] = $data['currency'];
        }

        if(isset($data['content_name']))
        {
            $customData['content_name'] = $data['content_name'];
        }

        if(isset($data['content_category']))
        {
            $customData['content_category'] = $data['content_category'];
        }

        if(isset($data['content_ids']))
        {
            $customData['content_ids'] = $data['content_ids'];
        }

        $item = isset($data['items']) ? $data['items'] : [];
        $contents = $this->setContents($item);
        if($contents)
        {
            $customData['contents'] = $contents;
        }

        if(isset($data['transaction_id']))
        {
            $customData['order_id'] = $data['transaction_id'];
        }


        if(isset($data['num_items']))
        {
            $customData['num_items'] = $data['num_items'];
        }


        if(isset($data['search_term']))
        {
            $customData['search_string'] = $data['search_term'];
        }


        if(isset($data['external_measurement_vendor_id']))
        {
            $customData['external_measurement_vendor_id'] = $data['external_measurement_vendor_id'];
        }

        if(isset($data['external_measurement_id']))
        {
            $customData['external_measurement_id'] = $data['external_measurement_id'];
        }

        if(isset($data['opt_out_type']))
        {
            $customData['opt_out_type'] = $data['opt_out_type'];
        }

        return $customData;
    }

    private function setContents($items)
    {
        if(!$items)
        {
            return [];
        }

        $contents = [];
        foreach($items as $item)
        {
            $content = [];

            if(isset($item['price']))
            {
                $content['item_price'] = (string) $item['price'];
            }

            if(isset($item['item_id']))
            {
                $content['id'] = $item['item_id'];
            }

            if(isset($item['quantity']))
            {
                $content['quantity'] = $item['quantity'];
            }

            if(isset($item['item_name']))
            {
                $content['item_name'] = $item['item_name'];
            }

            if(isset($item['item_brand']))
            {
                $content['item_brand'] = $item['item_brand'];
            }

            if(isset($item['category']))
            {
                $content['item_category'] = $item['category'];
            }

            $contents[] =  $content;
        }

        return $contents;
    }
}
