<?php

use Abraham\TwitterOAuth\TwitterOAuth;

/**
* --------------------------------------------------------------------------
* TwitterConnector:
*       Wrapper functions for Twitter connection
* Usage:
*       // For the connection url, and tokens.
*       TwitterHelper::getTwitterConnectURL()
*
*       // For connecting the user
*       $twitterConnector = new TwitterConnector($user);
*       $twitterConnector->getTokens($token_ours, $token_request, $token_secret, $verifier);
*       $stripeconnector->connect();
* --------------------------------------------------------------------------
*/

class TwitterConnector
{
    /* -- Class properties -- */
    private $user;

    /* -- Constructor -- */
    function __construct($user) {
        $this->user = $user;
    }

    /**
     * ================================================== *
     *                   STATIC SECTION                   *
     * ================================================== *
     */

    /**
     * getTwitterConnectURL
     * --------------------------------------------------
     * Returns the twitter connect url, based on config.
     * @return array
     * --------------------------------------------------
     */
    public static function getTwitterConnectURL() {
        /* Setting up connection. */
        $connection = new TwitterOAuth(
            $_ENV['TWITTER_CONSUMER_KEY'],
            $_ENV['TWITTER_CONSUMER_SECRET']
        );
        /* Getting a request token. */
        $requestToken = $connection->oauth('oauth/request_token', array('oauth_callback' => $_ENV['TWITTER_OAUTH_CALLBACK']));

        /* Return URI */
        return array(
            'oauth_token'        => $requestToken['oauth_token'],
            'oauth_token_secret' => $requestToken['oauth_token_secret'],
            'connection_url'     => $connection->url(
                'oauth/authorize',
                array('oauth_token' => $requestToken['oauth_token'])
            )

        );
    }

    /**
     * ================================================== *
     *                   PUBLIC SECTION                   *
     * ================================================== *
     */

    /**
     * connect
     * --------------------------------------------------
     * Sets up a stripe connection with the API key. * @throws StripeNotConnected
     * --------------------------------------------------
     */
    public function connect() {
        /* Check valid connection */
        if (!$this->user->isTwitterConnected()) {
            throw new TwitterNotConnected();
        }

        /* Get access tokens from DB. */
        $accessToken = json_decode($this->user->connections()
            ->where('service', 'twitter')
            ->first()->access_token, 1);

        /* Creating connection */
        $connection = new TwitterOAuth($_ENV['TWITTER_CONSUMER_KEY'], $_ENV['TWITTER_CONSUMER_SECRET'], $accessToken['oauth_token'], $accessToken['oauth_token_secret']);

        return $connection;
    }

    /**
     * disconnect
     * --------------------------------------------------
     * Disconnecting the user from twitter.
     * @throws TwitterNotConnected
     * --------------------------------------------------
     */
    public function disconnect() {
        /* Check valid connection */
        if (!$this->user->isTwitterConnected()) {
            throw new TwtitterNotConnected();
        }
        /* Deleting connection */
        $this->user->connections()->where('service', 'twitter')->delete();

        /* Deleting all widgets, plans, subscribtions */
        foreach ($this->user->widgets() as $widget) {
            if ($widget->descriptor->category == 'twitter') {

                /* Saving data while it is accessible. */
                $dataID = 0;
                if (!is_null($widget->data)) {
                    $dataID = $widget->data->id;
                }

                $widget->delete();

                /* Deleting data if it was present. */
                if ($dataID > 0) {
                    Data::find($dataID)->delete(); }
            }
        }
    }

    /**
     * getTokens
     * --------------------------------------------------
     * Retrieving the access, and refresh tokens from OAUTH.
     * @param (string) ($code) The returned code by stripe.
     * @return None
     * @throws TwitterConnectFailed
     * --------------------------------------------------
     */
    public function getTokens($tokenOurs, $tokenRequest, $tokenSecret, $verifier) {

        /* Oauth ready. */
        $requestToken = [];
        $requestToken['oauth_token'] = $tokenOurs;
        $requestToken['oauth_token_secret'] = $tokenSecret;

        /* Checking validation. */
        if ($tokenOurs !== $tokenRequest) {
            throw new TwitterConnectFailed("Error Processing Request", 1);
        }

        /* Setting up connection. */
        $connection = new TwitterOAuth(
            $_ENV['TWITTER_CONSUMER_KEY'],
            $_ENV['TWITTER_CONSUMER_SECRET'],
            $requestToken['oauth_token'],
            $requestToken['oauth_token_secret']
        );

        /* Retreiving access token. */
        try {
            $accessToken = $connection->oauth(
               "oauth/access_token", array("oauth_verifier" => $verifier));
        } catch (Abraham\TwitterOAuth\TwitterOAuthException $e) {
            throw new TwitterConnectFailed($e->getMessage(), 1);
        }

        /* Deleting all previos connections. */
        $this->user->connections()->where('service', 'twitter')->delete();

        /* Creating a Connection instance, and saving to DB. */
        $connection = new Connection(array(
            'access_token'  => json_encode(array(
                'oauth_token' => $accessToken['oauth_token'],
                'oauth_token_secret' => $accessToken['oauth_token_secret']
            )),
            'refresh_token' => '',
            'service'       => 'twitter',
        ));
        $connection->user()->associate($this->user);
        $connection->save();

    }

    /**
     * getNewAccessToken
     * --------------------------------------------------
     * Retrieving the access token from a refresh token.
     * @param None
     * @return None
     * @throws StripeConnectFailed
     * --------------------------------------------------
     */
    public function getNewAccessToken() {
        /* Check connection errors. */
        if (!$this->user->isStripeConnected()) {
            throw new StripeNotConnected();
        }

        /* Build and send POST request */
        $stripe_connection = $this->user->connections()->where('service', 'stripe')->first();
        $url = $this->buildTokenPostUriFromRefreshToken($stripe_connection->refresh_token);

        /* Get response */
        $response = SiteFunctions::postUrl($url);

        /* Invalid/No answer from Stripe. */
        if ($response === null) {
            throw new StripeConnectFailed('Stripe server error, please try again.', 1);
        }

        /* Error handling. */
        if (isset($response['error'])) {
            throw new StripeConnectFailed('Your connection expired, please try again.', 1);
        }

        /* Saving new token. */
        $stripe_connection->access_token = $response['access_token'];
        $stripe_connection->save();
    }

    /**
     * ================================================== *
     *                   PRIVATE SECTION                  *
     * ================================================== *
     */

    /**
     * buildTokenPostUriFromAuthCode
     * --------------------------------------------------
     * Creates a POST URI for the authorization and retrieving token.
     * @param (string) ($code) The returned code by stripe.
     * @return (array) (post_uri) The POST URI parameters
     * --------------------------------------------------
     */
    private function buildTokenPostUriFromAuthCode($code) {
        /* Build URI */
        $post_uri = array(
            'endpoint'  => $_ENV['STRIPE_ACCESS_TOKEN_URI'],
            'params'    => array(
                'client_secret' => $_ENV['STRIPE_SECRET_KEY'],
                'client_id'     => $_ENV['STRIPE_CLIENT_ID'],
                'code'          => $code,
                'grant_type'    => 'authorization_code'),
        );

        /* Return URI */
        return $post_uri;
    }

    /**
     * buildTokenPostUriFromRefreshToken
     * --------------------------------------------------
     * Creates a POST URI for the authorization and retrieving token.
     * @param (string) ($code) The returned code by stripe.
     * @return (string) (post_uri) The POST URI
     * --------------------------------------------------
     */
    private function buildTokenPostUriFromRefreshToken($refresh_token) {
        /* Build URI */
        $post_uri = array(
            'endpoint'  => $_ENV['STRIPE_ACCESS_TOKEN_URI'],
            'params'    => array(
                'client_secret' => $_ENV['STRIPE_SECRET_KEY'],
                'client_id'     => $_ENV['STRIPE_CLIENT_ID'],
                'refresh_token' => $refresh_token,
                'grant_type'    => 'refresh_token'
            )
        );

        /* Return URI */
        return $post_uri;
    }


} /* StripeConnector */
