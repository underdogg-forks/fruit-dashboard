<?php

/**
 * --------------------------------------------------------------------------
 * SignupWizardController: Handles the services connections.
 * --------------------------------------------------------------------------
 */
class ServiceConnectionController extends BaseController
{
    /**
     * ================================================== *
     *                      BRAINTREE                     *
     * ================================================== *
     */

    /**
     * getBraintreeConnect
     * --------------------------------------------------
     * @return Renders the braintree connect setup step
     * --------------------------------------------------
     */
    public function getBraintreeConnect() {
        $braintreeConnector = new BraintreeConnector(Auth::user());

        /* Render the page */
        return View::make('service.braintree.connect')
            ->with('authFields', $braintreeConnector->getAuthFields());
    }

    /**
     * postBraintreeConnect
     * --------------------------------------------------
     * @return Saves the user braintree connect settings
     * --------------------------------------------------
     */
    public function postBraintreeConnect() {
        // Validation.
        $rules = array(
            'publicKey'   => 'required',
            'privateKey'  => 'required',
            'merchantID'  => 'required',
            'environment' => 'required'
        );

        // Run the validation rules on the inputs.
        $validator = Validator::make(Input::all(), $rules);

        if ($validator->fails()) {
            // validation error -> sending back
            $failedAttribute = $validator->invalid();
            return Redirect::back()
                ->with('error', 'Please correct the form errors')
                ->withErrors($validator->errors())
                ->withInput(); // sending back data
        }

        $braintreeConnector = new BraintreeConnector(Auth::user());
        $braintreeConnector->getTokens(Input::all());

        /* Render the page */
        return Redirect::route('signup-wizard.financial-connections');
    }

    /**
     * anyDisconnectBraintree
     * --------------------------------------------------
     * @return Deletes the logged in user's braintree connection.
     * --------------------------------------------------
     */
    public function anyBraintreeDisconnect() {
        /* Try to disconnect */
        try {
            $connector = new BraintreeConnector(Auth::user());
            $connector->disconnect();
        } catch (BraintreeNotConnected $e) {}

        /* Redirect */
        return Redirect::route('settings.settings');
    }

    /**
     * ================================================== *
     *                       TWITTER                      *
     * ================================================== *
     */

    /**
     * anyTwitterConnect
     * --------------------------------------------------
     * @return connects a user to twitter.
     * --------------------------------------------------
     */
    public function anyTwitterConnect() {
        /* Setting up connection. */
        if (Input::get('oauth_verifier', FALSE) && Input::get('oauth_token', FALSE)) {
            $connector = new TwitterConnector(Auth::user());
            try {
                $connector->getTokens(
                    Session::get('oauth_token'),
                    Input::get('oauth_token'),
                    Session::get('oauth_token_secret'),
                    Input::get('oauth_verifier')
                );
            } catch (TwitterConnectFailed $e) {
                return Redirect::route('signup-wizard.social-connections')
                    ->with('error', 'Something went wrong, please try again.');
            }

            /* Successful connect. */
            return Redirect::route('signup-wizard.social-connections')
                ->with('success', 'Twitter connection successful');

        } else {
            /* Creating connection, storing credentials. */
            $connectData = TwitterConnector::getTwitterConnectURL();
            Session::put('oauth_token', $connectData['oauth_token']);
            Session::put('oauth_token_secret', $connectData['oauth_token_secret']);
            return Redirect::to($connectData['connection_url']);
        }

        return Redirect::route('signup-wizard.social-connections')
            ->with('error', 'Something went wrong.');
     }

    /**
     * anyDisconnectTwitter * --------------------------------------------------
     * @return Deletes the logged in user's twitter connection.
     * --------------------------------------------------
     */
    public function anyTwitterDisconnect() {
        /* Try to disconnect */
        try {
            $connector = new TwitterConnector(Auth::user());
            $connector->disconnect();
        } catch (TwitterNotConnected $e) {}

        /* Redirect */
        return Redirect::route('settings.settings');
    }

    /**
     * ================================================== *
     *                        GOOGLE                      *
     * ================================================== *
     */

    /**
     * anyGoogleAnalyticsConnectanyGoogleConnect
     * --------------------------------------------------
     * @return connects a user to GA.
     * --------------------------------------------------
     */
    public function anyGoogleAnalyticsConnect() {
        return $this->connectGoogle("GoogleAnalyticsConnector");
     }

    /**
     * anyDisconnectGoogle
     * --------------------------------------------------
     * @return Deletes the logged in user's GA connection.
     * --------------------------------------------------
     */
    public function anyGoogleAnalyticsDisconnect() {
        return $this->disconnectGoogle("GoogleAnalyticsConnector");
    }

    /**
     * anyGoogleCalendarConnectanyGoogleConnect
     * --------------------------------------------------
     * @return connects a user to GA.
     * --------------------------------------------------
     */
    public function anyGoogleCalendarConnect() {
        return $this->connectGoogle("GoogleCalendarConnector");
     }

    /**
     * anyDisconnectGoogle
     * --------------------------------------------------
     * @return Deletes the logged in user's GA connection.
     * --------------------------------------------------
     */
    public function anyGoogleCalendarDisconnect() {
        return $this->disconnectGoogle("GoogleCalendarConnector");
    }
    /**
     * ================================================== *
     *                       STRIPE                       *
     * ================================================== *
     */

    /**
     * anyStripeConnect
     * --------------------------------------------------
     * @return connects a user to twitter.
     * --------------------------------------------------
     */
    public function anyStripeConnect() {
        if (Auth::user()->isServiceConnected('stripe')) {
            return Redirect::route('settings.settings')
                ->with('warning', 'You are already connected the service');
        }

        if (Input::get('code', FALSE)) {
            /* Oauth ready. */
            $connector = new StripeConnector(Auth::user());
            try {
                $connector->getTokens(Input::get('code'));
            } catch (StripeConnectFailed $e) {
                return Redirect::route('signup-wizard.financial-connections')
                    ->with('error', 'Something went wrong, please try again.');
            }

            /* Successful connect. */
            return Redirect::route('signup-wizard.financial-connections')
                ->with('success', 'Stripe connection successful');

        } else if (Input::get('error', FALSE)) {
            /* User declined */
            return Redirect::route('signup-wizard.social-connections')
                ->with('error', 'You\'ve declined the request.');
        }

        return Redirect::to(StripeConnector::getStripeConnectURI(route('service.stripe.connect')));
     }
    /**
     * anyDisconnectStripe
     * --------------------------------------------------
     * @return Deletes the logged in user's stripe connection.
     * --------------------------------------------------
     */
    public function anyStripeDisconnect() {
        /* Try to disconnect */
        try {
            $connector = new StripeConnector(Auth::user());
            $connector->disconnect();
        } catch (StripeNotConnected $e) {}

        /* Redirect */
        return Redirect::route('settings.settings');
    }

    /**
     * ================================================== *
     *                   GOOGLE SHORTHAND                 *
     * ================================================== *
     */

    /**
     * connectGoogle
     * --------------------------------------------------
     * @return connects a user to a google service.
     * --------------------------------------------------
     */
    private function connectGoogle($connectorClass) {
        /* Creating connection credentials. */
        $connector = new $connectorClass(Auth::user());
        if (Input::get('code', FALSE)) {
           try {
                $connector->getTokens(Input::get('code'));
           } catch (GoogleConnectFailed $e) {
               /* User declined */
               return Redirect::route('signup-wizard.social-connections')
                   ->with('error', 'something went wrong.');
           }
           /* Successful connect. */
           return Redirect::route('signup-wizard.social-connections')
               ->with('success', 'Google connection successful');

        } else if (Input::get('error', FALSE)) {
            /* User declined */
            return Redirect::route('signup-wizard.social-connections')
                ->with('error', 'You\'ve declined the request.');

        } else {
            /* Redirectong to Oauth. */
            return Redirect::to($connector->getGoogleConnectUrl());
        }
     }

    /**
     * disconnectGoogle
     * --------------------------------------------------
     * @return Deletes the logged in user's specific google connection.
     * --------------------------------------------------
     */
    private function disconnectGoogle($connectorClass) {
        /* Try to disconnect */
        try {
            $connector = new $connectorClass(Auth::user());
            $connector->disconnect();
        } catch (TwiterNotConnected $e) {}

        /* Redirect */
        return Redirect::route('settings.settings');
    }

} /* ServiceConnectionController */