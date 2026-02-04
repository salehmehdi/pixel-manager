<?php

namespace SalehSignal\PixelManager\Actions\Snapchat;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;

use Exception;
use App\Exceptions\ApplicationError;
use App;
use Auth;
use Illuminate\Support\Str;

class SnapchatEventAction
{

    public function execute($arg, $application)
    {
        try
        {
            if(config('app.env') != 'production' || botDetected())
            {
                //return [];
            }

            $accessToken  = $application['snapchat_access_token'];
            $pixelId      = $application['snapchat_pixel_id'];
            if(!$accessToken || !$pixelId)
            {
                throw new \Exception('access_token or pixel_id not found');
            }

            $url = "https://tr.snapchat.com/v3/{$pixelId}/events?access_token={$accessToken}";


            $data = $arg['data'];

            $events = $this->setEvents($data);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $events);
            \Log::debug($response);
            if (!$response->successful())
            {
                throw new \Exception('Failed to send Snapchat  event: ' . $response->body());
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
            'add_to_cart'            => 'ADD_CART',
            'view_item'              => 'VIEW_CONTENT' ,
            'purchase'               => 'PURCHASE',
            'completed_registration' => 'SIGN_UP',
            'page_view'              => 'PAGE_VIEW',
            'search'                 => 'SEARCH',
            'subscription'           => 'SUBSCRIBE',
            'begin_checkout'         => 'START_CHECKOUT',
            //'view_cart'            => "VIEW_CART", // view cart  doesnt exist for snap, send to custom event
            'add_to_wishlist'        => 'ADD_TO_WISHLIST',
            //'add_payment_info'     => "ADD_PAYMENT_INFO",  // ADD PAYMENT INFO  doesnt exist for snap, send to custom event
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

        $events['data'] = [];
        array_push($events['data'], $event);
        return $events;
    }

    private function setUserData($data)
    {
        $customer = $data['customer'];
        $phone = null;
        if(data_get($customer, 'phone'))
        {
            $phone = data_get($customer, 'phone_code').''.data_get( $customer, 'phone');
        }

        $email  = data_get($customer, 'email');

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

        if($phone && strpos($phone, '+') !== 0)
        {
            $phone = '+' . $phone;
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

        if(isset($customer['click_value']) && isset($customer['click_value']['ScCid']))
        {
            $userData['sc_click_id'] = $customer['click_value']['ScCid'];
        }

        if(isset($data['sc_cookie1']))  //TODO xxx çerez..
        {
            $userData['sc_cookie1'] = $data['sc_cookie1'];
        }

        if(isset($data['idfv']))
        {
            $userData['idfv'] = $data['idfv'];
        }

        if(isset($data['partner_id']))
        {
            $userData['partner_id'] = $data['partner_id'];
        }

        if(isset($data['subscription_id']))
        {
            $userData['subscription_id'] = $data['subscription_id'];
        }

        if(isset($data['lead_id']))
        {
            $userData['lead_id'] = $data['lead_id'];
        }

        if(isset($data['download_id']))
        {
            $userData['download_id'] = $data['download_id'];
        }
        return $userData;
    }

    private function setCustomData($data)
    {
        $customData = [];
        if(isset($data['value']))
        {
            $customData['value'] = (float) $data['value'];
        }

        if(isset($data['currency']))
        {
            $customData['currency'] = $data['currency'];
        }

        if(isset($data['content_name']))
        {
            $customData['content_name'] = $data['content_name'];
        }

        if(isset($data['content_type']))
        {
            $customData['content_type'] = $data['content_type'];
        }

        if(isset($data['content_category']))
        {
            $customData['content_category'] = $data['content_category'];     //['shoes', 'umbrellas']
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

        if(isset($data['brands']))
        {
            $customData['brands'] = $data['brands'];
        }

        if(isset($data['sign_up_method']))
        {
            $customData['sign_up_method'] = $data['sign_up_method'];
        }

        if(isset($data['search_term']))
        {
            $customData['search_string'] = $data['search_term'];
        }

        if(isset($data['predicted_ltv']))
        {
            $customData['predicted_ltv'] = $data['predicted_ltv'];
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
                $content['item_price'] = (float) $item['price'];
            }

            if(isset($item['item_id']))
            {
                $content['id'] = $item['item_id'];
            }

            if(isset($item['quantity']))
            {
                $content['quantity'] = $item['quantity'];
            }

            if(isset($item['delivery_category']))
            {
                $content['delivery_category'] = $item['delivery_category'];
            }

            $contents[] =  $content;
        }

        return $contents;
    }
}
