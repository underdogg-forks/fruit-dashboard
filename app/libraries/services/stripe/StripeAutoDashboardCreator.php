<?php

class StripeAutoDashboardCreator extends GeneralAutoDashboardCreator
{
    const DAYS = 30;

    /* -- Class properties -- */
    /* LATE STATIC BINDING. */
    protected static $positioning = array(
        'stripe_mrr'  => '{"col":4,"row":1,"size_x":6,"size_y":6}',
        'stripe_arr'  => '{"col":2,"row":7,"size_x":5,"size_y":5}',
        'stripe_arpu' => '{"col":7,"row":7,"size_x":5,"size_y":5}',
    );
    protected static $service = 'stripe';
    /* /LATE STATIC BINDING. */

    private static $allowedEventTypes = array(
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted'
    );

    /**
     * The stripe events.
     *
     * @var array
     */
    private $events = null;

    /**
     * The stripe calculator object.
     *
     * @var StripeCalculator
     */
    private $calculator = null;

    /**
     * Setting up the calculator.
     */
    protected function setup($args) {
        $this->calculator = new StripeCalculator($this->user);
        $this->events = $this->calculator->getCollector()->getEvents();
        $this->filterEvents();
    }

    /**
     * Populating the widgets with data.
     */
    protected function populateData() {
        $mrrManager  = $this->dataManagers['stripe_mrr'];
        $arrManager  = $this->dataManagers['stripe_arr'];
        $arpuManager = $this->dataManagers['stripe_arpu'];

        /* Creating data for the last DAYS days. */
        $metrics = $this->getMetrics();

        $mrrManager->data->raw_value = json_encode($metrics['mrr']);
        $arrManager->data->raw_value = json_encode($metrics['arr']);
        $arpuManager->data->raw_value = json_encode($metrics['arpu']);
    }

    /**
     * Returning all metrics in an array.
     *
     * @return array.
    */
    private function getMetrics() {
        /* Updating subscriptions to be up to date. */
        $this->calculator->getCollector()->updateSubscriptions();

        $mrr = array();
        $arr = array();
        $arpu = array();

        for ($i = 0; $i < self::DAYS; $i++) {
            /* Calculating the date to mirror. */
            $date = Carbon::now()->subDays($i)->toDateString();
            $this->mirrorDay($date);
            array_push($mrr, array('date' => $date, 'value' => $this->calculator->getMrr()));
            array_push($arr, array('date' => $date, 'value' => $this->calculator->getArr()));
            array_push($arpu, array('date' => $date, 'value' => $this->calculator->getArpu()));
        }

        /* Sorting arrays accordingly. */
        return array(
            'mrr' => $this->sortByDate($mrr),
            'arr' => $this->sortByDate($arr),
            'arpu' => $this->sortByDate($arpu),
        );
    }

    /**
     * Sorting a multidimensional dataset by date.
     *
     * @param array dataSet
     * @return array
    */
    private function sortByDate($dataSet) {
        $dates = array();
        foreach($dataSet as $key=>$data) {
            $dates[$key] = $data['date'];
        }
        array_multisort($dates, SORT_ASC, $dataSet);
        return $dataSet;

    }

    /**
     * Filtering events to relevant only.
    */
    private function filterEvents() {
        $filteredEvents = array();
        foreach ($this->events as $key=>$event) {
            $save = FALSE;
            foreach (Static::$allowedEventTypes as $type) {
                if ($save) {
                    /* Save already set, going on. */
                    break;
                }
                if ($event['type'] == $type) {
                    $save = TRUE;
                }
            }
            if ($save) {
                array_push($filteredEvents, $event);
            }
        }
        $this->events = $filteredEvents;
    }

    /**
     * Trying to mirror the specific date, to our DB.
     *
     * @param date
    */
    private function mirrorDay($date) {
        foreach ($this->events as $key=>$event) {
            $eventDate = Carbon::createFromTimestamp($event['created'])->toDateString();
            if ($eventDate == $date) {
                switch ($event['type']) {
                    case 'customer.subscription.created': $this->handleSubscriptionCreation($event); break;
                    case 'customer.subscription.updated': $this->handleSubscriptionUpdate($event); break;
                    case 'customer.subscription.deleted': $this->handleSubscriptionDeletion($event); break; default:;
                }
                /* Making sure we're done with the event. */
                unset($this->events[$key]);
            }
        }
    }

    /**
     * Handling subscription deletion.
     *
     * @param Stripe\Event $event
    */
    private function handleSubscriptionDeletion($event) {
        $subscriptionData = $event['data']['object'];
        /* Creating a new susbcription */
        $subscription = new StripeSubscription(array(
            'subscription_id' => $subscriptionData['id'],
            'start'           => $subscriptionData['start'],
            'status'          => $subscriptionData['status'],
            'customer'        => $subscriptionData['customer'],
            'ended_at'        => $subscriptionData['ended_at'],
            'canceled_at'     => $subscriptionData['canceled_at'],
            'quantity'        => $subscriptionData['quantity'],
            'discount'        => $subscriptionData['discount'],
            'trial_start'     => $subscriptionData['trial_start'],
            'trial_end'       => $subscriptionData['trial_start'],
            'discount'        => $subscriptionData['discount']
        ));

        // Creating the plan if necessary.
        $plan = StripePlan::where('plan_id', $subscriptionData['plan']['id'])->first();
        if (is_null($plan)) {
            return;
        }

        $subscription->plan()->associate($plan);
        $subscription->save();
    }

    /**
     * Handling subscription update.
     *
     * @param Stripe\Event $event
    */
    private function handleSubscriptionUpdate($event) {
        /* Check if a plan's been changed./ */
        if (isset($event['data']['previous_attributes']['plan'])) {
            $subscriptionData = $event['data']['object'];

            $subscription = StripeSubscription::where('subscription_id', $subscriptionData['id'])->first();

            $newPlan = StripePlan::where('user_id', $this->user->id)->where('plan_id', $subscriptionData['plan']['id'])->first();

            $subscription->plan()->associate($newPlan);
            $subscription->save();
        }
    }

    /**
     * Handling subscription creation.
     *
     * @param Stripe\Event $event
    */
    private function handleSubscriptionCreation($event) {
        $subscriptionData = $event['data']['object'];
        /* Deleting the subscription */
        StripeSubscription::where('subscription_id', $subscriptionData['id'])->first()->delete();
    }
}