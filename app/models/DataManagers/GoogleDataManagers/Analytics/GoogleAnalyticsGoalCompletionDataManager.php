<?php

class GoogleAnalyticsGoalCompletionDataManager extends HistogramDataManager
{
    use GoogleAnalyticsHistogramDataManagerTrait;
    use GoogleAnalyticsGoalDataManagerTrait;
    protected static $cumulative = TRUE;
    public function getCurrentValue() {
        /* Getting the page from settings. */
        $collector = new GoogleAnalyticsDataCollector($this->user);
        return $collector->getGoalCompletions($this->getProfileId(), $this->getGoalId());
    }

    /**
     * getMetricNames
     * Returning the names of the metric used by the DM.
     * --------------------------------------------------
     * @return array
     * --------------------------------------------------
     */
    public function getMetricNames() {
        return array('goal' . $this->getCriteria()['goal'] . 'Completions');
    }
}
?>