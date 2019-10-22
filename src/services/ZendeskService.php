<?php
/**
 * Zendesk plugin for Craft CMS 3.x
 *
 * Creates a new support ticket in Zendesk using the JSON API
 *
 * @link      https://adigital.agency
 * @copyright Copyright (c) 2018 Matt Shearing
 */

namespace adigital\zendesk\services;

use adigital\zendesk\Zendesk;

use Craft;
use craft\base\Component;

/**
 * ZendeskService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Matt Shearing
 * @package   Zendesk
 * @since     1.0.0
 */
class ZendeskService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Zendesk::$plugin->zendeskService->submitTicket()
     *
     * @return mixed
     */
    public function submitTicket($data)
    {
		//build up the json array
		$create = [
			'ticket' => [
				'subject' => $data['subject'],
				'priority' => $data['priority'],
				'status' => 'new',
				'type' => $data['type'],
				'comment' => [
					'body' => $data['body']
				],
				'custom_fields' => $data["customFields"],
				'requester' => [
					'name' => $data['name'],
					'email' => $data['email']
				]
			]
		];
		if (isset($data["token"]) && $data["token"] <> '') {
			$create["ticket"]["comment"]["uploads"] = [$data["token"]];
		}
		$create = json_encode($create);
		$headers = ['Content-type: application/json'];

		//send all this to zendesk using our curl wrapper
		$output = self::curlWrap("/tickets.json", $create, $headers);
		
		//get the ticket ID - also checks the new ticket was created successfully
		$ticketId = $output->ticket->id;
		
		//if return exists and we've a ticket ID - it must have been created successfully :-)
		if ($output && $ticketId) {
			return $ticketId;
		}
		return false;
    }
    
    public function submitAttachments($attachments)
    {
	    $token = false;
	    foreach($attachments["attachments"]["name"] as $key => $filename) {
		    $file = $attachments["attachments"]["tmp_name"][$key];
		    if (isset($file) && $file <> '') {
			    $filedata = file_get_contents($file);
			    $url = '/uploads.json?filename=' . str_replace(" ", "_", $filename);
			    if (isset($token) && $token <> '') {
				    $url .= '&token=' . $token;
			    }
			    $headers = ['Content-Type: application/binary', 'Accept: application/json; charset=utf-8'];
			    $output = self::curlWrap($url, $filedata, $headers);
			    if ($key === 0) {
				    $token = $output->upload->token;
			    }
		    }
	    }
	    
		return $token;
    }

    // Get the available article categories
    public function getCategories($params)
    {
        $endpoint = "/help_center/en-us/categories.json";
        // Append our params, if provided...
        if ($params) {
            $endpoint .= "?{$params}";
        }
        return $this->processRequest($endpoint);
    }

    // Get the available article sections (category sub-divisions)
    public function getSections($params)
    {
        $endpoint = "/help_center/en-us/sections.json";
        // Append our params, if provided...
        if ($params) {
            $endpoint .= "?{$params}";
        }
        return $this->processRequest($endpoint);
    }

    // Get single article
    public function getArticle($articleId, $params)
    {
        $endpoint = "/help_center/en-us/articles/{$articleId}.json";
        // Append our params, if provided...
        if ($params) {
            $endpoint .= "?{$params}";
        }
        return $this->processRequest($endpoint);
    }
    
    // Get the available articles (we sideload by default...)
    public function getArticles($params)
    {
        $endpoint = "/help_center/en-us/articles.json";
        // Append our params, if provided...
        if ($params) {
            $endpoint .= "?{$params}";
        }
        return $this->processRequest($endpoint);
    }

    // Get articles in a particular section
    public function getSectionArticles($sectionId, $params)
    {
        $endpoint = "/help_center/en-us/sections/{$sectionId}/articles.json";
        // Append our params, if provided...
        if ($params) {
            $endpoint .= "?{$params}";
        }
        return $this->processRequest($endpoint);
    }

    // Get articles based on search parameters
    public function getSearchResults($params)
    {
        $endpoint = "/help_center/articles/search.json";
        // Append our params...
        $endpoint .= "?{$params}";

        return $this->processRequest($endpoint);
    }

    // Post vote based on vote input
    public function postVote($articleId, $vote)
    {
        if ($vote == 'up') {
            $endpoint = "/help_center/articles/{$articleId}/up.json";
        } elseif ($vote == 'down') {
            $endpoint = "/help_center/articles/{$articleId}/down.json";
        } else {
            return null;
        }

        return $this->processRequest($endpoint, null, null, true);
    }



    private function processRequest($endpoint, $data = null, $headers = null, $usePost = false) 
    {
        if (is_null($headers)) {
            $headers = [
                'Content-type: application/json',
                'Accept: application/json'
            ];
        }

		//send all this to zendesk using our curl wrapper
		$output = self::curlWrap($endpoint, $data, $headers, $usePost);
		
		//if response exists, return resutls
		if ($output) {
			return $output;
		}
		return false;
    }
    
    public function curlWrap($url, $data, $headers, $usePost = true)
	{
		$settings = Zendesk::$plugin->getSettings();
	    $zdApiKey = $settings->api_key;
	    $zdUser = $settings->user;
	    $zdUrl = $settings->url;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_URL, $zdUrl.$url);
		curl_setopt($ch, CURLOPT_USERPWD, $zdUser."/token:".$zdApiKey);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if ($usePost) {
            // If we're submitting via POST, set params for that
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
		$output = curl_exec($ch);
		curl_close($ch);
		
		$decoded = json_decode($output);
        return $decoded;
	}
}
