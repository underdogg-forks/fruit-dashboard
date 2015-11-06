<?php

trait HistogramChartLayoutTrait
{

    /**
     * getChartData
     * Return the chart data.
     * --------------------------------------------------
     * @param array $options
     * @return array
     * --------------------------------------------------
     */
    protected function getChartData(array $options)
    {
        /* Setting options. */
        if (array_key_exists('range', $options)) {
            $this->setRange($options['range']);
        }
        if (array_key_exists('length', $options)) {
            $this->setLength($options['length']);
        }
        if (array_key_exists('resolution', $options)) {
            $this->setResolution($options['resolution']);
        }

        return $this->getChartJSData($this->dateFormat());
    }

    /**
     * getChartTemplateData
     * Return all values that are used in templates.
     * --------------------------------------------------
     * @return array
     * --------------------------------------------------
     */
    protected function getChartTemplateData()
    {
        /* Chart specific data. */
        return array(
            'currentDiff'   => $this->compare(),
            'currentValue'  => $this->getLatestValues(),
            'hasCumulative' => static::$isCumulative
        );
    }

    /**
     * getChartTemplateMeta
     * Return the url, and selector.
     * --------------------------------------------------
     * @param array $meta
     * @return array
     * --------------------------------------------------
     */
    protected function getChartTemplateMeta($meta)
    {
        /* Chart specific data. */
        $meta['urls']['statUrl'] = route('widget.singlestat', $this->id);
        $meta['selectors']['graph'] = '[id^=chart-container]';
        return $meta;
    }

    /**
     * getChartJSData
     * Return template ready grouped dataset.
     * --------------------------------------------------
     * @param string $dateFormat
     * @return array
     * --------------------------------------------------
     */
    protected function getChartJSData($dateFormat)
    {
        /* Data init. */
        $datetimes = array();
        $dataSets = $this->initializeDataSets();

        /* Data transform, to chartJS ready values. */
        foreach ($this->buildHistogram() as $entry) {
            /* Adding value */
            $value = $entry['value'];
            array_push($dataSets[0]['values'], $value);

            /* Adding diff. */
            if (isset($prevValue)) {
                $diffedValue = $value - $prevValue;
            } else {
                $diffedValue = 0;
            }
            array_push($dataSets[1]['values'], $diffedValue);

            /* Adding formatted datetimes. */
            array_push(
                $datetimes,
                Carbon::createFromTimestamp($entry['timestamp'])->format($dateFormat)
            );

            /* Saving value. */
            $prevValue = $value;
        }

        return array(
            'isCombined' => true,
            'datasets'   => $dataSets,
            'labels'     => $datetimes,
        );
    }

    /**
     * initializeDataSets
     * Return the default arrays for chartJS data.
     * --------------------------------------------------
     * @return array
     * --------------------------------------------------
     */
    protected function initializeDataSets()
    {
        return array(
            array(
                'type'   => 'line',
                'color'  => SiteConstants::getChartJsColors()[0],
                'name'   => $this->getDescriptor()->name,
                'values' => array()
            ),
            array(
                'type'   => 'bar',
                'color'  => SiteConstants::getChartJsColors()[1],
                'name'   => 'Difference',
                'values' => array()
            )
        );
    }

    /**
     * dateFormat
     * Return the dateFormat, based on resolution.
     * --------------------------------------------------
     * @param string $resolution
     * @return array
     * --------------------------------------------------
     */
    protected final function dateFormat($resolution=null)
    {
        if (is_null($resolution)) {
            $resolution = $this->getResolution();
        }

        switch ($resolution) {
            case 'hours':  return 'M-d h';
            case 'days':   return 'M-d';
            case 'weeks':  return 'Y-W';
            case 'months': return 'Y-M';
            case 'years':  return 'Y';
            default:       return 'Y-m-d';
        }
    }
}
