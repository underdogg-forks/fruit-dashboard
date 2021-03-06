<?php

/** All classes that have interaction with data. */
abstract class DataWidget extends Widget implements iAjaxWidget
{
    abstract public function getData(array $postData=array());

    /**
     * An array of the used data types, criteria is being matched automatically.
     * Use late static binding.
     *
     * @var array
     */
    protected static $dataTypes = array();

    /**
     * An array of the decoded data.
     *
     * @var array
     */
    protected $data = array();

    /**
     * An array of the data ids.
     *
     * @var array
     */
    protected $dataIds = array();

    /**
     * getDataTypes
     * Return the used data types.
     * --------------------------------------------------
     * @return array
     * --------------------------------------------------
    */
    protected function getDataTypes()
    {
        return static::$dataTypes;
    }

    /**
     * handleAjax
     * Handling general ajax request.
     * --------------------------------------------------
     * @param array $postData
     * @return mixed
     * --------------------------------------------------
    */
    public function handleAjax($postData)
    {
        if (isset($postData['state_query']) && $postData['state_query']) {
            /* Trying to reload the data. */
            $this->loadData();

            /* Got state query signal */
            if ($this->state == 'loading') {
                return array('ready' => false);
            } else if($this->state == 'active') {
                /* Rerendering the widget */
                $view = View::make($this->getDescriptor()->getTemplateName())
                    ->with('widget', $this->getTemplateData());
                return array(
                    'ready' => true,
                    'data'  => $this->getData($postData),
                    'html'  => $view->render()
                );
            } else {
                return array('ready' => false);
            }
        }
        if (isset($postData['refresh_data']) && $postData['refresh_data']) {
            /* Refresh signal */
            try {
                $this->refreshWidget();
            } catch (ServiceException $e) {
                Log::error($e->getMessage());
                return array('status'  => false,
                             'message' => 'We couldn\'t refresh your data, because the service is unavailable.');
            }
        }
    }

    /**
     * updateData
     * Running collection on all related data.
     * --------------------------------------------------
     * @param array $options
     * @return string
     * --------------------------------------------------
    */
    public function updateData(array $options=array())
    {
        foreach ($this->dataIds as $dataId) {
            $dataObject = Data::find($dataId);

            try {
                $dataObject->collect($options);
            } catch (ServiceException $e) {
                Log::error('An error occurred during collecting data on #' . $dataId . ": " . $e->getMessage());

                $dataObject->setState('data_source_error');
            }
        }
    }

    /**
     * refreshWidget
     * Refreshing the widget data.
     * --------------------------------------------------
     * @return string
     * --------------------------------------------------
    */
    protected function refreshWidget()
    {
        $this->setState('loading');

        $this->populateData();

        $this->updateData();

        $this->populateData();

        $this->setState('active');
    }

    /**
     * loadData
     * Loading the widget data.
     * --------------------------------------------------
     * @param array $attributes
     * @throws ServiceNotConnected
     * --------------------------------------------------
     */
    public function loadData()
    {
        if ( ! $this->hasValidCriteria()) {
            $this->setState('setup_required');
            return;
        }

        try {
            /* Assigning the data. */
            $this->populateData();
        } catch (WidgetException $e) {
            /* Not enough data found. */
            $this->createDataObjects();

            /* Trying to reiniailize. */
            $this->populateData();
        }

    }

    /**
     * createDataObjects
     * Creating the data objects, if they did not exist.
     * --------------------------------------------------
     * @throws WidgetException
     * --------------------------------------------------
     */
    private function createDataObjects()
    {
        if ($this instanceof iServiceWidget) {
            /* Creating all related data. */
            $connectorClass = $this->getConnectorClass();
            $connector = new $connectorClass($this->user());

            $connector->createDataObjects($this->getCriteria());
        } else {
            /* Default widget creating data. */
            foreach (static::getDataTypes() as $dataType) {
                Data::createFromWidget(
                    $this,
                    $this->getDescriptor()->category,
                    $dataType
                );
            }
        }
    }

    /**
     * populateData
     * Populating the data.
     * --------------------------------------------------
     * @throws WidgetException
     * --------------------------------------------------
     */
    private function populateData()
    {
        /* Resetting ids. */
        $this->dataIds = array();

        foreach ($this->getDataObjects() as $dataObject) {
            /* Getting the related data. */
            $this->data[$dataObject->type] = $dataObject->decode();

            array_push($this->dataIds, $dataObject->id);
        }

        /* Handling data state. */
        $this->handleStates();

    }

    /**
     * getDataObjects
     * Return the corresponding data objects.
     * --------------------------------------------------
     * @throws WidgetException
     * --------------------------------------------------
     */
    private function getDataObjects()
    {
        $dataObjects = array();
        $widgetCriteria = $this->getCriteria();

        /* Getting the corresponding data objects, with one optimized query. */
        foreach ($this->user()->dataObjects()
            ->join('data_descriptors', 'data_descriptors.id', '=' , 'data.descriptor_id')
            ->where('data_descriptors.category', $this->getDescriptor()->category)
            ->whereIn('data_descriptors.type', static::getDataTypes())
            ->get(array('data.id', 'data.criteria', 'data_descriptors.type', 'data.state')) as $dataObject) {

            /* Filtering criteria. */
            $dataCriteria = $dataObject->getCriteria();
            if (count(array_intersect($dataCriteria, $widgetCriteria)) ==
                count($dataCriteria)) {
                array_push($dataObjects, $dataObject);
            }
        }

        if (count($dataObjects) != count(static::getDataTypes())) {
            throw new WidgetException('Insuficcient data available.');
        }

        return $dataObjects;
    }

    /**
     * handleDataStates
     * Setting loading state accordingly.
     * --------------------------------------------------
     * @param array $dataObjects
     * --------------------------------------------------
    */
    private function handleStates()
    {
        $state = 'active';

        foreach ($this->dataIds as $id) {
            $data = Data::find($id);

            if ($data->state == 'loading') {
                $state = 'loading';
            }

            if ($data->state == 'data_source_error') {
                $state = 'data_source_error';
            }
        }

        $this->setState($state);
    }
}
