<?php

class TwitterFollowersCountWidget extends Widget implements iServiceWidget
{
    use TwitterWidgetTrait;
    public static $histogramDescriptor = 'twitter_followers';
}
?>
