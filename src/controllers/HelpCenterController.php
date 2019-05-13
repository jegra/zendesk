<?php
/**
 * Zendesk plugin for Craft CMS 3.x
 *
 * Creates Craft endpoints for accessing Zendesk support articles
 * using the JSON API
 *
 * @link      https://adigital.agency
 * @copyright Copyright (c) 2018 Matt Shearing
 */

namespace adigital\zendesk\controllers;

use adigital\zendesk\Zendesk;

use Craft;
use craft\web\Controller;

/**
 * Support Controller
 *
 * Manages requests for accessing support articles on Zendesk.
 */
class HelpCenterController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'categories', 'sections', 'articles'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/zendesk/help-center
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the SupportController actionIndex() method';

        return $result;
    }

    /**
     * Retrieve top-level categories for the Zendesk Guide content
     * e.g.: actions/zendesk/help-center/categories
     *
     * @return mixed
     */
    public function actionCategories() {
        $data = Zendesk::$plugin->zendeskService->getCategories();

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }

    /**
     * Retrieve sub-level sections for the Zendesk Guide content
     * e.g.: actions/zendesk/help-center/sections
     *
     * @return mixed
     */
    public function actionSections() {
        $data = Zendesk::$plugin->zendeskService->getSections();

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }

    /**
     * Retrieve articles for the Zendesk Guide content.  Note that we 
     * also return Category and Section data with this request, so as
     * to cut down on number of api requests (service is rate limited).
     * e.g.: actions/zendesk/help-center/categories
     *
     * @return mixed
     */
    public function actionArticles() {
        $data = Zendesk::$plugin->zendeskService->getArticles();

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }
}
