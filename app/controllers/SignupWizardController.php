<?php


/**
 * --------------------------------------------------------------------------
 * SignupWizardController: Handles the signup process
 * --------------------------------------------------------------------------
 */
class SignupWizardController extends BaseController
{
    /**
     * ================================================== *
     *                   PUBLIC SECTION                   *
     * ================================================== *
     */

    /**
     * getAuthentication
     * --------------------------------------------------
     * @return Renders the authentication step
     * --------------------------------------------------
     */
    public function getAuthentication() {
        /* Render the page */
        return View::make('signup-wizard.authentication');
    }

    /**
     * postAuthentication
     * --------------------------------------------------
     * @return Saves the user authentication data
     * --------------------------------------------------     
     */
    public function postAuthentication() {
        /* Validation rules */
        $rules = array(
            'email' => 'required|email|unique:users',
            'password' => 'required|min:4',
        );

        /* Run validation rules on the inputs */
        $validator = Validator::make(Input::all(), $rules);
        
        /* Everything is ok */
        if (!$validator->fails()) {

            /* Create the user */
            $user = $this->createUser(Input::all());

            /* Log in the user*/
            Auth::login($user);

            /* Redirect to next step */
            return Redirect::route('signup-wizard.personal-widgets');

        /* Validator failed */
        } else {
            /* Render the page */
            return Redirect::route('signup-wizard.authentication')
                ->with('error', $validator->errors()->get(key($validator->invalid()))[0]);
        }

        /* Render the page */
        return View::make('signup-wizard.authentication');
    }

    /**
     * getPersonalWidgets
     * --------------------------------------------------
     * @return Renders the personal widget setup step
     * --------------------------------------------------
     */
    public function getPersonalWidgets() {
        /* Render the page */
        return View::make('signup-wizard.personal-widgets');
    }

    /**
     * postPersonalWidgets
     * --------------------------------------------------
     * @return Saves the user personal widget settings
     * --------------------------------------------------     
     */
    public function postPersonalWidgets() {
        /* Check for authenticated user, redirect if nobody found */
        if (!Auth::check()) {
            return Redirect::route('signup-wizard.authentication');
        }
        
        /* Create the personal dashboard based on the inputs */
        $dashboard = $this->makePersonalAutoDashboard(Auth::user(), Input::all());
        
        /* Render the page */
        return View::make('signup-wizard.financial-connections');
    }

    /**
     * getFinancialConnections
     * --------------------------------------------------
     * @return Renders the financial connections step
     * --------------------------------------------------
     */
    public function getFinancialConnections() {
        /* Coming back from STRIPE oauth, accessing code */
        if (Input::get('code', FALSE)) {
            $stripeconnector = new StripeConnector(Auth::user());
            try {
                /* Get tokens */
                $stripeconnector->getTokens(Input::get('code'));
            } catch (StripeConnectFailed $e) {
                /* Error logging */
                $messages = array();
                array_push($messages, $e->getMessage());
                Log::error($e->getMessage());
            }
            /* Connect to stripe */
            $stripeconnector->connect();
        }
        
        // 
        // try {
        //     $stripeconnector->getTokens($code);
        // } catch (StripeConnectFailed $e) {
        //     // error handling
        // }
        // $stripeconnector->connect();
        /* Render the page */
        return View::make('signup-wizard.financial-connections');
    }

    /**
     * postFinancialConnections
     * --------------------------------------------------
     * @return Saves the financial connection setting
     * --------------------------------------------------
     */
    public function postFinancialConnections() {
        /* Stripe connection */
        if(Input::get('stripe-connect', FALSE)) {
            error_log('STRIPE');
        }

        /* Braintree connection */
        if(Input::get('braintree-connect', FALSE)) {
            error_log('BRAINTREE');
        }

        /* Redirect to the same page */
        return Redirect::route('signup-wizard.financial-connections');
        
    }

    /**
     * ================================================== *
     *                   PRIVATE SECTION                  *
     * ================================================== *
     */

    /**
     * createUser
     * creates a new User object (and related models) 
     * from the POST data
     * --------------------------------------------------
     * @return ($user) (User) The new User object
     * --------------------------------------------------
     */
    private function createUser($input) {
        /* Create new user */
        $user = new User;

        /* Set authentication info */
        $user->email    = $input['email'];
        $user->password = Hash::make($input['password']);
        $user->name     = $input['name'];
        
        /* Save the user */
        $user->save();

        /* Create default settings for the user */
        $settings = new Settings;
        $settings->user_id              = $user->id;
        $settings->newsletter_frequency = 0;
        $settings->background_enabled   = true;

        /* Save settings */
        $settings->save();

        /* Create default subscription for the user */
        $plan = Plan::where('name', 'Free')->first();
        $subscription = new Subscription;
        $subscription->user_id              = $user->id;
        $subscription->plan_id              = $plan->id;
        $subscription->current_period_start = Carbon::now();
        $subscription->current_period_end   = Carbon::now()->addDays(Config::get('constants.TRIAL_PERIOD_IN_DAYS'));
        $subscription->status               = 'active';

        /* Save subscription */
        $subscription->save();

        /* Return */
        return $user;
    }

    /**
     * makePersonalAutoDashboard
     * creates a new Dashboard object and personal widgets 
     * from the POST data
     * --------------------------------------------------
     * @return ($dashboard) (Dashboard) The new Dashboard object
     * --------------------------------------------------
     */
    private function makePersonalAutoDashboard($user, $input) {
        /* Create new dashboard */
        $dashboard = new Dashboard;

        $dashboard->user_id     = $user->id;
        $dashboard->name        = 'My personal dashboard';
        $dashboard->background  = 'On';

        /* Save dashboard object */
        $dashboard->save();

        /* Create clock widget */
        $clockwidget = new ClockWidget;

        $clockwidget->dashboard_id  = $dashboard->id;
        $clockwidget->descriptor_id = Config::get('constants.WD_ID_CLOCK');
        $clockwidget->state         = 'active';
        $clockwidget->position      = '{"row":1,"col":3,"size_x":8,"size_y":3}';

        /* Save clock widget object */
        $clockwidget->save();

        /* Create quote widget */
        $quotewidget = new QuoteWidget;

        $quotewidget->dashboard_id  = $dashboard->id;
        $quotewidget->descriptor_id = Config::get('constants.WD_ID_QUOTE');
        $quotewidget->state         = 'active';
        $quotewidget->position      = '{"row":8,"col":3,"size_x":8,"size_y":1}';

        /* Save quote widget object */
        $quotewidget->save();

        /* Create greetings widget */
        /**
         * @todo: create if model exists
         */


        /* Return */
        return $dashboard;
    }

} /* SignupWizardController */