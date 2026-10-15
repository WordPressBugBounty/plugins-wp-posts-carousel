<?php

if (!defined('ABSPATH')) {
    exit;
}

/** @internal Request-scoped slide reuse for JSON and HTML responses. */
final class WP_Posts_Carousel_Render_Context
{
    private $config;
    private $params;
    private $slides;

    public function prepare($config, $params, $slides)
    {
        $this->config = $config;
        $this->params = $params;
        $this->slides = $slides;
    }

    public function slides_for($config, $params)
    {
        return $config === $this->config && $params === $this->params ? $this->slides : null;
    }
}
