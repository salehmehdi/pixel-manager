<?php

namespace SalehSignal\PixelManager\Actions\Google;

use App;
use Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Exception;

class GoogleEventAction {
    public function execute($arg, $application)
    {
        $data = $arg['data'];

        $measurementId = $application['google_measurement_id'] ?? null;
        $apiSecret = $application['google_api_secret'] ?? null;
        if(!$measurementId || !$apiSecret)  //TODO XXX...
        {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send event to Google Analytics',
                'error' => $response->body(),
                'validation_error' => $response->json()
            ], 500);
        }

        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}";

        $payload['events'] = $this->setEvents($data);

        if(isset($data['customer']['external_id']))
        {
            $payload['client_id'] = (string) $data['customer']['external_id'];

        }

        $response = Http::post($url, $payload);

        if ($response->successful())
        {
            \Log::info('Event successfully sent to Google Analytics', [
                'validation' => $response->json(),
                'response' => $response->body()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event successfully sent to Google Analytics',
                'validation_response' => $response->json()
            ]);
        }
        else
        {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send event to Google Analytics',
                'error' => $response->body(),
                'validation_error' => $response->json()
            ], 500);
        }
    }

    private function setEvents($data)
    {
        $events = $event = [];
        $eventName = isset($data['event']) ? $data['event'] : null;
        $eventName =  match ($eventName)
        {
            'add_to_cart'            => 'add_to_cart',
            'view_item'              => 'view_item' ,
            'purchase'               => 'purchase',
            'completed_registration' => 'sign_up',
            'page_view'              => 'page_view',
            'search'                 => 'search',
            //'subscription'         => 'subscription', // subscription doesnt exist for google,
            'begin_checkout'         => 'begin_checkout',
            'view_cart'              => 'view_cart',
            'add_payment_info'       => 'add_payment_info',
            'add_to_wishlist'        => 'add_to_wishlist',
            default                  => null,
        };

        if($eventName)
        {
            $event['name'] = $eventName;
        }

        $customData = $this->setCustomData($data);
        if(!$customData)
        {
            // bir hata gitmeli
        }

        $event['params'] = $customData; // zorunlu alan boş gönderilemez

        $events[] = $event;
        return $events;
    }

    private function setCustomData($data)
    {
        $params = [];

        if(isset($data['currency']))
        {
            $params['currency'] = $data['currency'];
        }

        if(isset($data['value']))
        {
            $params['value'] = (float) $data['value'];
        }

        $item = isset($data['items']) ? $data['items'] : [];
        $contents = $this->setContents($item);
        if($contents)
        {
            $params['items'] = $contents;
        }

        if(isset($data['registration_method']))
        {
            $params['method'] = $data['registration_method'];
        }

        if(isset($data['page_url']))
        {
            $params['page_location'] = $data['page_url'];
        }

        if(isset($data['referrer']))
        {
            $params['page_referrer'] = $data['referrer'];
        }

        if(isset($data['page_title']))
        {
            $params['page_page_title'] = $data['page_title'];
        }

        if(isset($data['shipping']))
        {
            $params['shipping'] = (float) $data['shipping'];
        }

        if(isset($data['transaction_id']))  // TODO XXX
        {
            $params['transaction_id'] = $data['transaction_id'];
        }

        if(isset($data['search_term']))
        {
            $params['search_term'] = $data['search_term']; // search için zorunlu bir alan
        }

        return $params;

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

            if (isset($item['item_id']))
            {
                $content['index'] = $item['index'];
            }

            if (isset($item['item_id']))
            {
                $content['item_id'] = $item['item_id'];
            }

            if (isset($item['item_name']))
            {
                $content['item_name'] =  $item['item_name'];
            }

            if (isset($item['item_brand']))
            {
                $content['item_brand'] = $item['item_brand'];
            }

            if (isset($item['price']))
            {
                $content['price'] = (float) $item['price'];
            }

            if (isset($item['quantity']))
            {
                $content['quantity'] = $item['quantity'];
            }

            if (isset($item['item_variant']))
            {
                $variants = [];
                if (isset($item['item_variant']['color']))
                {
                    $variants[] = $item['item_variant']['color'];
                }
                if (isset($item['item_variant']['size']))
                {
                    $variants[] = $item['item_variant']['size'];
                }

                if (!empty($variants))
                {
                    $content['item_variant'] = implode('-', $variants);
                }
            }

            if (isset($item['discount']))
            {
                $content['discount'] = $item['discount'];
            }

            if (isset($item['category']) && is_array($item['category']))  // taxonomy sıralaması şeklinde olmalı
            {
                foreach ($item['category'] as $categoryIndex => $category)
                {
                    if ($categoryIndex === 0)
                    {
                        $content['item_category'] = $category;
                    }
                    else
                    {
                        $content['item_category' . ($categoryIndex + 1)] = $category;
                    }
                }
            }

            $contents[] = $content;
        }

        return $contents;
    }
}
