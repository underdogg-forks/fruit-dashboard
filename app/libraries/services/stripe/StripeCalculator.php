<?php

/**
* --------------------------------------------------------------------------
* StripeCalculator:
*       Wrapper functions for Stripe calculations
* Usage:
*       To retrive $user's mrr: getMrr()
*       To retrive $user's arr: getArr()
*       To retrive $user's arpu: getArpu()
* --------------------------------------------------------------------------
*/

class StripeCalculator
{
    /* -- Class properties -- */
    protected $user;
    protected $dataCollector;

    /* -- Constructor -- */
    function __construct($user) {
        $this->user = $user;
        $this->dataCollector = new StripeDataCollector($this->user);
    }

    /**
     * ================================================== *
     *                   PUBLIC SECTION                   *
     * ================================================== *
     */

    /**
     * getCollector
     * --------------------------------------------------
     * Return the data collector instance.
     * @return the data collector.
     * --------------------------------------------------
    */
    public function getCollector() {
        return $this->dataCollector;
    }

    /**
     * getMrr
     * --------------------------------------------------
     * Calculating the MRR for the user.
     * @param $update, boolean Whether or not sync the db.
     * @return float The value of the mrr.
     * @throws StripeNotConnected
     * --------------------------------------------------
    */
    public function getMrr($update=False) {
        $mrr = 0;

        // Updating database, with the latest data.
        if ($update) {
            $this->dataCollector->updateSubscriptions();
        }

        // Iterating through the plans and subscriptions.
        foreach ($this->user->stripePlans()->get() as $plan) {
            foreach ($plan->subscriptions()->get() as $subscription) {
                // Dealing only with active subscriptions.
                if ($subscription->status == 'active') {
                    //
                    $value = $plan->amount * $subscription->quantity;
                    switch ($plan->interval) {
                        case 'day'  : $value *= 30; break;
                        case 'week' : $value *= 4.33; break;
                        case 'month': $value *= 1; break;
                        case 'year' : $value *= 1/12; break;
                        default: break;
                    }
                    $mrr += $value;
                }
            }
        }
        return $mrr;
    }

    /**
     * getArr
     * --------------------------------------------------
     * Calculating the ARR for the user.
     * @param $update, boolean Whether or not sync the db.
     * @return float The value of the arr.
     * @throws StripeNotConnected
     * --------------------------------------------------
    */
    public function getArr($update=False) {
        return $this->getMrr($update) * 12;
    }

    /**
     * getArpu
     * --------------------------------------------------
     * Calculating the ARPU for the user.
     * @param $update, boolean Whether or not sync the db.
     * @return float The value of the arpu.
     * @throws StripeNotConnected
     * --------------------------------------------------
    */
    public function getArpu($update=False) {
        $customerNumber = $this->dataCollector->getNumberOfCustomers($update);
        /* Avoiding division by zero. */
        if ($customerNumber == 0) {
            return 0;
        }
        return $this->getArr($update) / $customerNumber;
    }
} /* StripeCalculator */
