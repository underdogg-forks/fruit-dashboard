<?php

/**
 * --------------------------------------------------------------------------
 * DashboardController: Handles the authentication related sites
 * --------------------------------------------------------------------------
 */
class DashboardController extends BaseController
{
    const OPTIMIZE = false;

    /**
     * ================================================== *
     *                   PUBLIC SECTION                   *
     * ================================================== *
     */

    /**
     * anyDashboard
     * --------------------------------------------------
     * Returns the user dashboard, or redirects to signup wizard
     * @return Renders the dashboard page
     * --------------------------------------------------
     */
    public function anyDashboard() {
        /* No caching in local development */
        if ( ! App::environment('local')) {
            /* Trying to load from cache. */
            $cachedDashboard = $this->getFromCache();
            if ( ! is_null($cachedDashboard)) {
                /* Some logging */
                if ( ! App::environment('production')) {
                    Log::info("Loading dashboard from cache.");
                    Log::info("Rendering time:" . (microtime(true) - LARAVEL_START));
                }

                /* Return the cached dashboard. */
                return $cachedDashboard;
            }
        }
        if (self::OPTIMIZE) {
            return $this->showOptimizeLog(Auth::user());
            exit(94);
        }

        /* Get the current user */
        $user = Auth::user();

        /* Check the default dashboard and create if not exists */
        $user->checkOrCreateDefaultDashboard();

        /* Check onboarding state */
        if ($user->settings->onboarding_state != 'finished') {
            return View::make('dashboard.dashboard-onboarding-not-finished', array(
                    'currentState' => $user->settings->onboarding_state
                ));
        }

        /* Get active dashboard, if the url contains it */
        $parameters = array();
        $activeDashboard = Input::get('active');
        if ($activeDashboard) {
            $parameters['activeDashboard'] = $activeDashboard;
        }

        /* Checking the user's widgets integrity */
        $user->checkWidgetsIntegrity();

        /* Creating view */
        $view = $user->createDashboardView($parameters);

        try {
            /* Trying to render the view. */
            $renderedView = $view->render();

            if ( ! App::environment('producion')) {
                Log::info("Rendering time:" . (microtime(true) - LARAVEL_START));
            }
        } catch (Exception $e) {
            /* Error occured, trying to find the widget. */
            $user->turnOffBrokenWidgets();
            /* Recreating view. */
            $renderedView= $user->createDashboardView($parameters)->render();
        }

        /* Saving the cache, and returning the view. */
        $sessionKeys = array_keys(Session::all());
        if ( ! (in_array('error', $sessionKeys) || in_array('success', $sessionKeys))) {
            $this->saveToCache($renderedView);
        }
        return $renderedView;

    }

    /**
     * getManageDashboards
     * --------------------------------------------------
     * @return Renders the mange dashboards page
     * --------------------------------------------------
     */
    public function getManageDashboards() {
        /* Check the default dashboard and create if not exists */
        Auth::user()->checkOrCreateDefaultDashboard();

        /* Render the page */
        return View::make('dashboard.manage-dashboards');
    }

    /**
     * anyDeleteDashboard
     * --------------------------------------------------
     * @return Deletes a dashboard.
     * --------------------------------------------------
     */
    public function anyDeleteDashboard($dashboardId) {
        /* Get the dashboard */
        $dashboard = $this->getDashboard($dashboardId);
        if (is_null($dashboard)) {
            return Response::json(false);
        }

        /* Track event | DELETE DASHBOARD */
        $tracker = new GlobalTracker();
        $tracker->trackAll('lazy', array(
            'en' => 'Dashboard deleted',
            'el' => $dashboard->name)
        );

        /* Delete the dashboard*/
        $dashboard->delete();

        /* Return. */
        return Response::json(true);
    }

    /**
     * anyLockDashboard
     * --------------------------------------------------
     * @return Locks a dashboard.
     * --------------------------------------------------
     */
    public function anyLockDashboard($dashboardId) {
        $dashboard = $this->getDashboard($dashboardId);
        if (is_null($dashboard)) {
            return Response::json(false);
        }

        $dashboard->is_locked = true;
        $dashboard->save();

        /* Return. */
        return Response::json(true);
    }

    /**
     * anyUnlockDashboard
     * --------------------------------------------------
     * @return Unlocks a dashboard.
     * --------------------------------------------------
     */
    public function anyUnlockDashboard($dashboardId) {
        $dashboard = $this->getDashboard($dashboardId);
        if (is_null($dashboard)) {
            return Response::json(false);
        }

        $dashboard->is_locked = false;
        $dashboard->save();

        /* Return. */
        return Response::json(true);
    }

    /**
     * anyMakeDefault
     * --------------------------------------------------
     * @return Makes a dashboard the default one.
     * --------------------------------------------------
     */
    public function anyMakeDefault($dashboardId) {
        // Make is_default false for all dashboards
        foreach (Auth::user()->dashboards()->where('is_default', true)->get() as $oldDashboard) {
            $oldDashboard->is_default = false;
            $oldDashboard->save();
        }

        $dashboard = $this->getDashboard($dashboardId);
        if (is_null($dashboard)) {
            return Response::json(false);
        }

        $dashboard->is_default = true;
        $dashboard->save();

        /* Return. */
        return Response::json(true);
    }

    /**
     * postRenameDashboard
     *
     */
    public function postRenameDashboard($dashboardId) {
        $dashboard = $this->getDashboard($dashboardId);
        if (is_null($dashboard)) {
            return Response::json(false);
        }

        $newName = Input::get('dashboard_name');
        if (is_null($newName)) {
            return Response::json(false);
        }

        $dashboard->name = $newName;
        $dashboard->save();

        /* Return. */
        return Response::json(true);
    }

    /**
     * postCreateDashboard
     *
     */
    public function postCreateDashboard() {
        $name = Input::get('dashboard_name');
        if (empty($name)) {
            return Response::json(false);
        }

        /* Creating dashboard. */
        $dashboard = new Dashboard(array(
            'name'       => $name,
            'background' => true,
            'number'     => Auth::user()->dashboards->max('number') + 1
        ));
        $dashboard->user()->associate(Auth::user());
        $dashboard->save();

        /* Track event | ADD DASHBOARD */
        $tracker = new GlobalTracker();
        $tracker->trackAll('lazy', array(
            'en' => 'Dashboard added',
            'el' => $dashboard->name)
        );

        /* Return. */
        return Response::json(true);
    }

    /**
     * anyGetDashboards
     * --------------------------------------------------
     * @return array
     * --------------------------------------------------
     */
    public function anyGetDashboards() {
        $dashboards = array();
        foreach (Auth::user()->dashboards as $dashboard) {
            array_push($dashboards, array(
                'id'        => $dashboard->id,
                'name'      => $dashboard->name,
                'is_locked' => $dashboard->is_locked,
            ));
        }

        /* Return. */
        return Response::json($dashboards);
    }

    /**
     * ================================================== *
     *                   PRIVATE SECTION                  *
     * ================================================== *
     */

    /**
     * getDashboard
     * --------------------------------------------------
     * @return Dashboard
     * --------------------------------------------------
     */
    private function getDashboard($dashboardId) {
        $dashboard = Dashboard::find($dashboardId);
        if (is_null($dashboard)) {
            return null;
        }
        if ($dashboard->user != Auth::user()) {
            return null;
        }
        return $dashboard;
    }
    /**
     * showOptimizeLog
     * Renders the status log.
     * --------------------------------------------------
     * @param User $user
     * --------------------------------------------------
     */
    private function showOptimizeLog($user) {
        var_dump(' -- DEBUG LOG --');
        $time = microtime(true);
        $startTime = $time;
        $queries = count(DB::getQueryLog());
        $startTime = microtime(true);
        $memUsage = memory_get_usage();
        var_dump("Initial memory usage: " . number_format($memUsage));
        /* Checking the user's widgets integrity */
        $user->checkWidgetsIntegrity();
        var_dump(
            "Widget check integrity time: ". (microtime(true) - $time) .
            " (" . (count(DB::getQueryLog()) - $queries ). ' db queries ' .
            number_format(memory_get_usage() - $memUsage) . ' bytes of memory)'
        );
        $memUsage = memory_get_usage();
        $queries = count(DB::getQueryLog());
        $time = microtime(true);

        /* Creating view */
        $view = $user->createDashboardView();
        var_dump(
            "Dashboards/widgets data loading time: ". (microtime(true) - $time) .
            " (" . (count(DB::getQueryLog()) - $queries ). ' db queries ' .
            number_format(memory_get_usage() - $memUsage) . ' bytes of memory)'
        );
        $memUsage = memory_get_usage();
        $queries = count(DB::getQueryLog());
        $time = microtime(true);

        $view->render();
        var_dump(
            "Rendering time: ". (microtime(true) - $time) .
            " (" . (count(DB::getQueryLog()) - $queries ). ' db queries)'
        );
        var_dump("Total memory usage: " . number_format(memory_get_usage()) . " bytes");
        var_dump(
             "Total loading time: ". (microtime(true) - LARAVEL_START) .
             " (" . count(DB::getQueryLog()) . ' db queries)'
        );
        var_dump(DB::getQueryLog());
        return;
    }

    /**
     * getFromCache
     * Returns the dashboard if it's cached.
     * --------------------------------------------------
     * @return View/null
     * --------------------------------------------------
     */
    private function getFromCache() {
        $user = Auth::user();
        if ( ! $user->update_cache) {
            return Cache::get($this->getDashboardCacheKey());
        }
        return;
    }

    /**
     * saveToCache
     * Saving the dashboard to cache.
     * --------------------------------------------------
     * @param string $renderedView
     * --------------------------------------------------
     */
    private function saveToCache($renderedView) {
        $user = Auth::user();
        Cache::put(
            $this->getDashboardCacheKey(),
            $renderedView,
            SiteConstants::getDashboardCacheMinutes()
        );
        $user->update_cache = false;
        $user->save();
    }

    /**
     * getDashboardCacheKey
     * Returns the cache key for the user's dashboard.
     * --------------------------------------------------
     * @return string
     * --------------------------------------------------
     */
    private function getDashboardCacheKey() {
        return 'dashboard_' . Auth::user()->id;
    }

} /* DashboardController */
