<?php

namespace SalehSignal\PixelManager\Actions\Meta;

use App\Domain\Actions\Marketing\Meta\Model\Store;
use Illuminate\Support\Facades\Response;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Api;
use FacebookAds\Http\Exception\RequestException;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;

use Exception;
use App\Exceptions\ApplicationError;
use App;
use Auth;
use Illuminate\Support\Str;

class MetaEventAction
{

    public function execute($arg, $application)
    {
        try
        {
            if(config('app.env') != 'production' || botDetected())
            {
                //return [];
            }

            $data = $arg['data'];

            $accessToken  = $application['meta_access_token'];
            $pixelId      = $application['meta_pixel_id'];
            if(!$accessToken || !$pixelId)
            {
                return false; // TODO XXX...
            }

           // $api = Api::init(null, null, $accessToken);
            //$api->setLogger(new CurlLogger());

            $events = $this->setEvents($data);

            return (new EventRequest($pixelId))->setEvents($events)->execute();
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
        $event = (new Event());

        $eventName = isset($data['event']) ? $data['event'] : null;
        $eventName =  match ($eventName)
        {
            'add_to_cart'            => 'AddToCart',
            'view_item'              => 'ViewContent' ,
            'purchase'               => 'CompletePayment',
            'completed_registration' => 'CompleteRegistration',
            'page_view'              => 'Pageview',
            'search'                 => 'Search',
            'subscription'           => 'Subscribe',
            'begin_checkout'         => 'InitiateCheckout',
            'view_cart'              => 'ViewCart',
            'add_payment_info'       => 'AddPaymentInfo',
            'add_to_wishlist'        => 'AddToWishlist',
            default                  => null,
        };

        if($eventName)
        {
            $event->setEventName($eventName);
        }

        $eventId = (string) $data['event_id'];
        $eventId = $eventName ? $eventName.'.'.$eventId : $eventId;

        $event->setEventId($eventId);
        $event->setEventTime(time());
        $event->setActionSource(ActionSource::WEBSITE);

        $userData = $this->setUserData($data);
        $event->setUserData($userData);
        $customData = $this->setCustomData($data);
        $event->setCustomData($customData);

        if(isset($data['page_url']))
        {
            $event->setEventSourceUrl($data['page_url']);
        }

        if(isset($data['data_processing_options']))
        {
            $event->setDataProcessingOptions($data['data_processing_options']);
        }

        if(isset($data['data_processing_options_country']))
        {
            $event->setDataProcessingOptionsCountry($data['data_processing_options_country']);
        }

        if(isset($data['data_processing_options_state']))
        {
            $event->setDataProcessingOptionsState($data['ata_processing_options_state']);
        }

        /**
         * bool ... Sets a flag that indicates Facebook should or should not use this event for ads delivery optimization
         */
        if(isset($data['opt_out']))
        {
            $event->setOptOut($data['opt_out']);
        }

        $events = [];
        array_push($events, $event);
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

        $userData = (new UserData());

        if($email)
        {
            $userData->setEmail($email);
        }

        if($phone)
        {
            $userData->setPhone($phone);
        }

        if($ip)
        {
            $userData->setClientIpAddress($ip);
        }

        if(isset($customer['user_agent']))
        {
            $userData->setClientUserAgent($customer['user_agent']);
        }

        if(isset($customer['gender']))
        {
            $userData->setGender($customer['gender']);
        }

        if(isset($customer['date_of_birth']))
        {
            $userData->setGender($customer['date_of_birth']);
        }

        if(isset($customer['last_name']))
        {
            $userData->setLastName($customer['last_name']);
        }

        if(isset($customer['first_name']))
        {
            $userData->setFirstName($customer['first_name']);
        }

        if(isset($customer['city']))
        {
            $userData->setCity($customer['city']);
        }

        if(isset($customer['state']))
        {
            $userData->setState($customer['state']);
        }

        if(isset($customer['country_code']))
        {
            $userData->setCountryCode($customer['country_code']);
        }

        if(isset($customer['zip_code']))
        {
            $userData->setZipCode($customer['zip_code']);
        }

        if(isset($customer['external_id']))
        {
            $userData->setExternalId($customer['external_id']);
        }

        if(isset($customer['subscription_id']))
        {
            $userData->setSubscriptionId($customer['subscription_id']);
        }

        if(isset($customer['fb_login_id']))
        {
            $userData->setFbLoginId($customer['fb_login_id']);
        }

        if(isset($customer['lead_id']))
        {
            $userData->setLeadId($customer['lead_id']); // A lead_id is associated with a lead generated by Facebook's Lead Ads.
        }

        if(isset($customer['5first']))
        {
            $userData->setF5first($customer['5first']); // f5first The first 5 letters of the first name.
        }

        if(isset($customer['f5last']))
        {
            $userData->setF5last($customer['f5last']); // f5last The first 5 letters of the last name.
        }

        if(isset($customer['fi']))
        {
            $userData->setFi($customer['fi']); // fi The first initial.
        }

        if(isset($customer['dobd']))
        {
            $userData->setDobd($customer['dobd']); // dobd The date of birth day.
        }

        if(isset($customer['dobm']))
        {
            $userData->setDobm($customer['dobm']); // dobm The date of birth month.
        }

        if(isset($customer['doby']))
        {
            $userData->setDoby($customer['doby']); // doby The date of birth year.
        }

        if(isset($customer['click_value']) &&  isset($customer['click_value']['fbclid']))
        {
            $userData->setFbc($this->generate_fbc($customer['click_value']['fbclid']));
        }

        $userData->setFbp($this->generate_fbp());

        return $userData;
    }

    private function setCustomData($data)
    {
        $customData = (new CustomData());
        if(isset($data['value']))
        {
            $customData->setValue($data['value']);
        }

        if(isset($data['currency']))
        {
            $customData->setCurrency($data['currency']);
        }

        if(isset($data['content_name']))
        {
            $customData->setContentName($data['content_name']);
        }

        if(isset($data['content_category']))
        {
            $customData->setContentCategory($data['content_category']);
        }

        if(isset($data['content_ids']))
        {
            $customData->setContentIds($data['content_ids']);
        }

        $item = isset($data['items']) ? $data['items'] : [];
        $contents = $this->setContents($item);
        if($contents)
        {
            $customData->setContents($contents);
        }

        if(isset($data['content_type']))
        {
            $customData->setContentType($data['content_type']);
        }

        if(isset($data['transaction_id']))
        {
            $customData->setOrderId($data['transaction_id']);
        }

        if(isset($data['predicted_ltv']))
        {
            $customData->setPredictedLtv($data['predicted_ltv']);
        }

        if(isset($data['num_items']))
        {
            $customData->setNumItems($data['num_items']);
        }

        if(isset($data['status']))
        {
            $customData->setStatus($data['status']);
        }

        if(isset($data['search_term']))
        {
            $customData->setSearchString($data['search_term']);
        }

        if(isset($data['delivery_category']))
        {
            $customData->setDeliveryCategory($data['delivery_category']);
        }

        if(isset($data['item_number']))
        {
            $customData->setItemNumber($data['item_number']);
        }

        if(isset($data['custom_properties']))
        {
            $customData->setCustomProperties($data['custom_properties']);
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
            $content = (new Content());

            if(isset($item['price']))
            {
                $content->setItemPrice($item['price']);
            }

            if(isset($item['item_id']))
            {
                $content->setProductId($item['item_id']);
            }

            if(isset($item['quantity']))
            {
                $content->setQuantity($item['quantity']);
            }

            if(isset($item['item_name']))
            {
                $content->setTitle($item['item_name']);
            }

            if(isset($item['description']))
            {
                $content->setDescription($item['description']);
            }

            if(isset($item['item_brand']))
            {
                $content->setBrand($item['item_brand']);
            }

            if(isset($item['category']))
            {
                $content->setCategory($item['category']);
            }

            if(isset($item['delivery_category']))
            {
                $content->setDeliveryCategory($item['delivery_category']);
            }

            $contents[] =  $content;
        }

        return $contents;
    }

    private function generate_fbp()
    {
        // Version prefix
        $version = 'fb';
        // Subdomain index
        $subdomainIndex = 1; // Assuming it's on the domain itself
        // Creation time (UNIX timestamp in milliseconds)
        $creationTime = time() * 1000; // Current time in milliseconds
        // Random number
        $randomNumber = mt_rand(); // Generate a random number

        // Concatenate the components to form the fbp value
        $fbp = $version . '.' . $subdomainIndex . '.' . $creationTime . '.' . $randomNumber;

        return $fbp;
    }

    // TODO || XXX fbc ve fbp sistem tarafından generate edilmiş olarak gelecek.
    private function generate_fbc($clickId)
    {
        if(!$clickId)
        {
            return null;
        }

        // Version prefix
        $version = 'fb';
        // Subdomain index
        $subdomainIndex = 1; // Assuming it's on the domain itself
        // Creation time (UNIX timestamp in milliseconds)
        $creationTime = session("fbc_unix") ?? time() * 1000; // Current time in milliseconds

        // Concatenate the components to form the fbc value
        $fbc = $version . '.' . $subdomainIndex . '.' . $creationTime . '.' . session("fbclid");

        return $fbc;
    }
}
