<?php

/**
 * --------------------------------------------------------------------------
 * APIController: Handles the Fruit Dashboard Widget API
 * --------------------------------------------------------------------------
 */
class APIController extends BaseController
{
    /**
     * ================================================== *
     *                   PUBLIC SECTION                   *
     * ================================================== *
     */

    /**
     * postData
     * --------------------------------------------------
     * @return Handles the incoming POST request, and checks its integrity
     * --------------------------------------------------
     */
    public function postData($apiVersion = null, $apiKey = null, $widgetID = null) {
        /* Check API version */
        if (!in_array($apiVersion, SiteConstants::getApiVersions())) {
            return Response::json(array('status'  => false,
                                        'message' => 'This API version is not supported.'));
        }

        /* Call API hadler */
        $result = $this->handlePostData($apiVersion, $apiKey, $widgetID);

        /* Return based on result */
        return Response::json($result);
    }

    /**
     * getTest
     * --------------------------------------------------
     * @return Renders the example page
     * --------------------------------------------------
     */
    public function getTest($widgetID) {
        /* Get the requested widget */
        $widget = Widget::find($widgetID);

        /* Error handling */
        if ($widget == null) {
            return Redirect::route('dashboard.dashboard')->with(['error' => 'Sorry the requested widget does not exist']);
        }
        if ($widget->user()->id != Auth::user()->id) {
            return Redirect::route('dashboard.dashboard')->with(['error' => 'Sorry the requested widget does not exist']);
        }

        /* Get the widget API url */
        $url = $widget->getSettings()['url'];

        /* Create default JSON string */
        $defaultJSON =
            "{\n".
            "'timestamp':" . Carbon::now()->getTimestamp(). ", \n" .
            "'Graph One': 15, \n" .
            "'Graph Two': 40\n" .
            "}";

        /* Render view */
        return View::make('api.api-test',
                            ['url'         => $url,
                             'defaultJSON' => $defaultJSON,
                             'toDashboard' => $widget->dashboard->id]);
    }


    /**
     * ================================================== *
     *                   PRIVATE SECTION                  *
     * ================================================== *
     */

    /**
     * handlePostData
     * --------------------------------------------------
     * @return Handles the POST data (url and data check)
     * --------------------------------------------------
     */
    private function handlePostData($apiVersion, $apiKey, $widgetID) {
        /* Handle POST data based on the API version */
        switch ($apiVersion) {
            case '1.0':
            default:
                /* Get user and widget */
                $settings = Settings::where('api_key', $apiKey)->first();
                $user   = $settings->user;
                $widget = Widget::find($widgetID);

                /* Check API key */
                if (is_null($user)) {
                    return array('status'  => false,
                                 'message' => 'Your API key is invalid.');
                }

                /* Check Widget ID */
                if (is_null($widget)) {
                    return array('status'  => false,
                                 'message' => 'Your Widget ID is invalid.');
                }

                /* Check if the widget belongs to the user */
                if ($user->id != $widget->user()->id) {
                    return array('status'  => false,
                                 'message' => 'Your Url is invalid (api key and widget id doesn\'t match).');
                }

                /* Save widget data */
                return $this->saveWidgetData($widget);
                break;
        }
    }

    /**
     * saveWidgetData
     * --------------------------------------------------
     * @return Saves the data to the database
     * --------------------------------------------------
     */
    private function saveWidgetData($widget) {
        /* Check if timestamp exists */
        if (is_null(Input::get('timestamp')) && is_null(Input::get('date'))) {
            return array('status'  => false,
                         'message' => "You must provide a 'timestamp' or a 'date' attribute for your data.");
        } else if (Input::get('timestamp')) {
            try {
                $date = Carbon::createFromTimeStamp(Input::get('timestamp'));
            } catch (Exception $e) {
                return array('status'  => false,
                             'message' => "Your 'timestamp' is not a valid timestamp.");
            }
        } else if (Input::get('date')) {
            try {
                $date = Carbon::parse(Input::get('date'));
            } catch (Exception $e) {
                return array('status'  => false,
                             'message' => "Your 'date' is not a valid date.");
            }
        }


        /* Check all other data */
        $hasData = false;
        foreach (Input::except('timestamp', 'date') as $key => $value) {
            if ( ! is_numeric($value)) {
                return array('status'  => false,
                             'message' => "You have to provide numbers for the graph values. The value of '". $key ."' is not a number.");
            } else if ( ! $hasData) {
                $hasData = true;
            }
        }

        if ( ! $hasData) {
            return array('status'  => false,
                         'message' => "You have to provide at least one dataset.");
        }

        /* Everything is ok */
        $widget->updateData(array('entry' =>Input::all()));
        return array('status'  => true,
                     'message' => 'Your data has been successfully saved.');

    }
} /* APIController */
