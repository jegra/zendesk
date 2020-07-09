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
    protected $allowAnonymous = [
        'index', 
        'categories', 
        'sections', 
        'article',
        'articles',
        'section-articles',
        'search',
        'vote',
        'labels'
    ];

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
     * Retrieve labels (tags) used in our articles
     * e.g.: actions/zendesk/help-center/labels
     *
     * @return mixed
     */
    public function actionLabels() {
        $request = Craft::$app->getRequest();
        $params = $request->queryStringWithoutPath;
        $data = Zendesk::$plugin->zendeskService->getLabels($params);

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }

    /**
     * Retrieve top-level categories for the Zendesk Guide content
     * e.g.: actions/zendesk/help-center/categories
     *
     * @return mixed
     */
    public function actionCategories() {
        $request = Craft::$app->getRequest();
        $params = $request->queryStringWithoutPath;
        $data = Zendesk::$plugin->zendeskService->getCategories($params);

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
        $request = Craft::$app->getRequest();
        $params = $request->queryStringWithoutPath;
        $data = Zendesk::$plugin->zendeskService->getSections($params);

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }

    /**
     * Retrieve a single article
     * e.g.: actions/zendesk/help-center/article
     *
     * @return mixed
     */
    public function actionArticle() {
        $request = Craft::$app->getRequest();
        $params = '';
        $sideload = $request->getParam('sideload');
        
        if ($sideload != "false") {
            // Only _avoid_ sideloading if explicitly requested to do so
            // (This is for backwards compatibility...)
            $params .= ($params == '') ? '' : '&';
            $params .= 'include=sections,categories';
        }

        $articleId = $request->getParam('articleId');

        $data = Zendesk::$plugin->zendeskService->getArticle($articleId, $params);

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }

    /**
     * Retrieve articles for the Zendesk Guide content.  Note that we 
     * also can return Category and Section data with this request, so as
     * to cut down on number of api requests (service is rate limited).
     * e.g.: actions/zendesk/help-center/articles
     * 
     * @return mixed
     */
    public function actionArticles() {
        $request = Craft::$app->getRequest();
        $params = '';
        $sideload = $request->getParam('sideload');
        $page = $request->getParam('page');
        $per_page = $request->getParam('per_page');
        if ($page) {
            $params .= ($params == '') ? '' : '&';
            $params .= 'page=' . $page;
        }
        if ($per_page) {
            $params .= ($params == '') ? '' : '&';
            $params .= 'per_page=' . $per_page;
        }
        if ($sideload != "false") {
            // Only _avoid_ sideloading if explicitly requested to do so
            // (This is for backwards compatibility...)
            $params .= ($params == '') ? '' : '&';
            $params .= 'include=sections,categories';
        }

        $data = Zendesk::$plugin->zendeskService->getArticles($params);

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }


    /**
     * Submit a vote for an article (vote values of 'up' or 'down'
     * for a given article ID)
     * 
     * @return mixed
     */
    public function actionVote() {
        $request = Craft::$app->getRequest();
        $articleId = $request->getParam('articleId');
        $vote = $request->getParam('vote');

        $data = Zendesk::$plugin->zendeskService->postVote($articleId, $vote);


        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }

    /**
     * Retrieve articles for a particular section in the Zendesk Guide 
     * content.  Note that we can also return Category and Section data 
     * with this request.
     * e.g.: actions/zendesk/help-center/section-articles
     *
     * @return mixed
     */
    public function actionSectionArticles() {
        $request = Craft::$app->getRequest();
        $params = '';
        $sideload = $request->getParam('sideload');
        $page = $request->getParam('page');
        $per_page = $request->getParam('per_page');
        if ($page) {
            $params .= ($params == '') ? '' : '&';
            $params .= 'page=' . $page;
        }
        if ($per_page) {
            $params .= ($params == '') ? '' : '&';
            $params .= 'per_page=' . $per_page;
        }
        if ($sideload != "false") {
            // Only _avoid_ sideloading if explicitly requested to do so
            // (This is for backwards compatibility...)
            $params .= ($params == '') ? '' : '&';
            $params .= 'include=sections,categories';
        }

	    $sectionId = $request->getParam('sectionId');

        $data = Zendesk::$plugin->zendeskService->getSectionArticles($sectionId, $params);

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }

    /**
     * Perform a search against the help-desk api. NOTE: The parameters
     * submitted to this method will be forwarded directly to the
     * zendesk service, so be sure all content is formatted as 
     * needed by the search endpoint.
     * e.g.: actions/zendesk/help-center/search
     *
     * @return mixed
     */
    public function actionSearch() {
        $request = Craft::$app->getRequest();
        $params = $request->queryStringWithoutPath;

        $data = Zendesk::$plugin->zendeskService->getSearchResults($params);

        if ($data) {
            return $this->asJson(array(
                'success' => 1,
                'data' => $data
            ));
        }
    }
}
