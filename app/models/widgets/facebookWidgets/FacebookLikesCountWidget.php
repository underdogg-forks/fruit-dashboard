<?php

class FacebookLikesCountWidget extends Widget implements iServiceWidget
{
    use FacebookWidgetTrait;
    public static $histogramDescriptor = 'facebook_likes';

    /**
     * getTemplateData
     * Return the mostly used values in the template.
     * --------------------------------------------------
     * @return array
     * --------------------------------------------------
     */
    public function getTemplateData() {
        return array_merge(parent::getTemplateData(), array(
            'pageName' => $this->getPage()->name
        ));
    }

}
?>
