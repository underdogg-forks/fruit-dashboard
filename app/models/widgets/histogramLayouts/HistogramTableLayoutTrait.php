<?php

trait HistogramTableLayoutTrait
{
    protected function getTableTemplateData() {return array();}
    /**
     * getTableData
     * Returns the data in table format.
     * --------------------------------------------------
     * @param array $options
     * @return array
     * --------------------------------------------------
     */
    public function getTableData(array $options)
    {
        $settings = $this->getSettings();
        $dateHeader = rtrim(ucwords($this->getResolution()), 's');

        /* Initializing table. */
        $tableData = array(
            'header' => array(
                 $dateHeader,
                 $this->getDescriptor()->name,
                 'Trend'
            ),
            'content' => array(
            )
        );

        /* Populating table data. */
        for ($i = $this->getLength() - 1; $i >= 0; --$i) {
            $now = Carbon::now();
            switch ($settings['resolution']) {
                case 'days':   $date = $now->subDays($i)->format('M-d'); break;
                case 'weeks':  $date = $now->subWeeks($i)->format('W'); break;
                case 'months': $date = $now->subMonths($i)->format('M'); break;
                case 'years':  $date = $now->subYears($i)->format('Y'); break;
                default:$date = '';
            }

            /* Calculating data. */
            $history = $this->getHistory($i);
            $value = $history['value'];

            if (isset($previousValue) && $previousValue != 0) {
                $percent = ($value / $previousValue - 1) * 100;
            } else {
                $percent = 0;
            }

            /* Creating format for percent. */
            $success = static::isSuccess($percent);
            $trendFormat = '<div class="';
            if ($success) { $trendFormat .= 'text-success';
            } else { $trendFormat .= 'text-danger'; }
            $trendFormat .= '"> <span class="fa fa-arrow-';
            if ($percent >= 0) { $trendFormat .= 'up';
            } else { $trendFormat .= 'down'; }
            $trendFormat .= '"> %.2f%%</div>';

            array_push($tableData['content'], array(
                $date,
                Utilities::formatNumber($history['value'], $this->getFormat()),
                Utilities::formatNumber($percent, $trendFormat)
            ));

            /* Saving previous value. */
            $previousValue = $value;
        }
        $tableData['content'] = array_reverse($tableData['content']);
        return $tableData;
    }

    /**
     * setupTableDataManager
     * Setting up the datamanager
     * --------------------------------------------------
     * @param DataManager $manager
     * @return DataManager
     * --------------------------------------------------
     */
    protected function setupTableDataManager($manager) 
    {
        //$manager->setDiff(TRUE);
    }

    /**
     * getTableTemplateMeta
     * Returning the default meta.
     * --------------------------------------------------
     * @param array $meta
     * @return DataManager
     * --------------------------------------------------
     */
    protected function getTableTemplateMeta($meta) {
        return $meta;
    }

}
