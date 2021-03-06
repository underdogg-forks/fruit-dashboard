<?php

trait FacebookWidgetTrait
{
    /* -- Settings -- */
    private static $pageSettings = array(
        'page' => array(
            'name'       => 'Page',
            'type'       => 'SCHOICE',
            'validation' => 'required',
            'help_text'  => 'The widget uses this facebook page for data representation.'
        )
    );

    private static $page = array('page');

    /**
     * layoutSetup
     * Set up the widget based on the layout.
     * --------------------------------------------------
     * @param layout
     * @return array
     * --------------------------------------------------
    */
    protected function layoutSetup($layout)
    {
        $this->setActiveHistogram($this->buildHistogramEntries());
    }

    public function page() {
        $pages = array();
        foreach ($this->user()->facebookPages as $page) {
            $pages[$page->id] = $page->name;
        }
        return $pages;
    }

    /**
     * getConnectorClass
     * --------------------------------------------------
     * Returns the connector class for the widgets.
     * @return string
     * --------------------------------------------------
     */
    public function getConnectorClass() {
        return 'FacebookConnector';
    }

    /**
     * getSettingsFields
     * Returns the SettingsFields
     * --------------------------------------------------
     * @return array
     * --------------------------------------------------
     */
    public static function getSettingsFields()
    {
        return array_merge(parent::getSettingsFields(), array(
            'Facebook settings' => static::$pageSettings
        ));
    }


    /**
     * getSetupFields
     * --------------------------------------------------
     * Updating setup fields.
     * @return array
     * --------------------------------------------------
     */
    public static function getSetupFields() {
        return array_merge(parent::getSetupFields(), self::$page);
    }

    /**
     * getCriteriaFields
     * --------------------------------------------------
     * Updating criteria fields.
     * @return array
     * --------------------------------------------------
     */
    public static function getCriteriaFields() {
        return array_merge(parent::getCriteriaFields(), self::$page);
    }

    /**
     * getPage
     * --------------------------------------------------
     * Return the corresponding page.
     * @return FacebookPage
     * @throws FacebookNotConnected
     * --------------------------------------------------
     */
    public function getPage() {
        $pageId = $this->getSettings()['page'];
        $page = $this->user()->facebookPages()->where('id', $pageId)->first();
        /* Invalid page in DB. */
        if (is_null($page)) {
            return $this->user()->facebookPages()->first();
        }
        return $page;
    }

    /**
     * getServiceSpecificName
     * Return the default name of the widget.
     * --------------------------------------------------
     * @return string
     * --------------------------------------------------
     */
    public function getServiceSpecificName() {
        return $this->getPage()->name;
    }

    /**
     * getCountFooter
     * --------------------------------------------------
     * Return the footer for the count widget.
     * @return array
     * --------------------------------------------------
     */
    protected function getCountFooter()
    {
        return $this->getPage()->name;
    }
}

?>
