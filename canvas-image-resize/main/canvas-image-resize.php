<?php

/*
Plugin Name: Canvas Image Resize
Description: Re-sizes images right inside the browser BEFORE uploading them.
Version: 1.0.1
Author: Simon Sippert
Author URI: http://www.sippsolutions.de/
Text Domain: canvas-image-resize
Domain Path: /lang
License: GNU
*/

/*
Canvas Image Resize, a plugin for WordPress
Copyright (C) 2017 Simon Sippert, sippsolutions (http://www.sippsolutions.de)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Canvas Image Resize
 *
 * @copyright 2017 Simon Sippert <s.sippert@sippsolutions.de>
 */
class CanvasImageResize
{
    /**
     * Define the plugin name
     *
     * @var string
     */
    const PLUGIN_NAME = 'Canvas Image Resize';

    /**
     * Defines the text domain
     *
     * @var string
     */
    const TEXT_DOMAIN = 'canvas-image-resize';

    /**
     * Defines the plugin's options page name
     *
     * @var string
     */
    const OPTIONS_PAGE_NAME = 'cir_options';

    /**
     * Field name for max width
     *
     * @var string
     */
    const FIELD_IMAGE_MAX_WIDTH = 'image_max_width';

    /**
     * Field name for max height
     *
     * @var string
     */
    const FIELD_IMAGE_MAX_HEIGHT = 'image_max_height';

    /**
     * Field name for max quality
     *
     * @var string
     */
    const FIELD_IMAGE_MAX_QUALITY = 'image_max_quality';

    /**
     * Store default options
     *
     * @var array
     */
    protected $defaultOptions = array(
        self::FIELD_IMAGE_MAX_WIDTH => 1600,
        self::FIELD_IMAGE_MAX_HEIGHT => 1600,
        self::FIELD_IMAGE_MAX_QUALITY => 100,
    );

    /**
     * Initialize the plugin
     */
    public function __construct()
    {
        $this->initPlugin();
        $this->initPluginPage();
    }

    /**
     * Initialize the filter settings
     *
     * @return void
     */
    protected function initPlugin()
    {
        // define function
        $setImageSettingsFunction = 'setImageSettings';
        // add filters
        add_filter('plupload_default_settings', array($this, $setImageSettingsFunction), 100);
        add_filter('plupload_default_params', array($this, $setImageSettingsFunction), 100);
        add_filter('plupload_init', array($this, $setImageSettingsFunction), 100);
    }

    /**
     * Initialize the plugin page
     *
     * @return void
     */
    protected function initPluginPage()
    {
        add_action('plugins_loaded', array($this, 'addTextDomain'));
        add_action('admin_init', array($this, 'initOptionsPage'));
        add_action('admin_menu', array($this, 'addOptionsPage'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'addPluginPage'));
    }

    /**
     * Add text domain
     *
     * @return void
     */
    public function addTextDomain()
    {
        load_plugin_textdomain(static::TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/lang');
    }

    /**
     * Get plugin name
     *
     * @return string
     */
    protected function getPluginName()
    {
        return __(static::PLUGIN_NAME, static::TEXT_DOMAIN);
    }

    /**
     * Get options name
     *
     * @return string
     */
    protected function getOptionsName()
    {
        return static::TEXT_DOMAIN . '_settings';
    }

    /**
     * Get options
     *
     * @return array
     */
    protected function getOptions()
    {
        return wp_parse_args(get_option($this->getOptionsName()), $this->defaultOptions);
    }

    /**
     * Add the plugin page
     *
     * @param array $links
     * @return array
     */
    public function addPluginPage(array $links)
    {
        array_unshift($links, '<a href="options-general.php?page=' . static::TEXT_DOMAIN . '">' . __('Settings', static::TEXT_DOMAIN) . '</a>');
        return $links;
    }

    /**
     * Add the options page
     *
     * @return void
     */
    public function addOptionsPage()
    {
        add_options_page($this->getPluginName(), $this->getPluginName(), 'manage_options', static::TEXT_DOMAIN, array($this, 'renderOptionsPage'));
    }

    /**
     * Render the options page
     *
     * @return void
     */
    public function initOptionsPage()
    {
        // add the possibility to add settings
        register_setting(static::OPTIONS_PAGE_NAME, $this->getOptionsName());

        // set section name
        $sectionName = implode('_', array(static::TEXT_DOMAIN, static::OPTIONS_PAGE_NAME, 'general'));

        // add section
        add_settings_section(
            $sectionName,
            __('General settings', static::TEXT_DOMAIN),
            null,
            static::OPTIONS_PAGE_NAME
        );

        // add fields
        add_settings_field(
            static::FIELD_IMAGE_MAX_WIDTH,
            __('Maximum width of images', static::TEXT_DOMAIN),
            array($this, 'renderFieldGeneralImageMaxWidth'),
            static::OPTIONS_PAGE_NAME,
            $sectionName
        );
        add_settings_field(
            static::FIELD_IMAGE_MAX_HEIGHT,
            __('Maximum height of images', static::TEXT_DOMAIN),
            array($this, 'renderFieldGeneralImageMaxHeight'),
            static::OPTIONS_PAGE_NAME,
            $sectionName
        );
        add_settings_field(
            static::FIELD_IMAGE_MAX_QUALITY,
            __('Quality of images (0-100)', static::TEXT_DOMAIN),
            array($this, 'renderFieldGeneralImageMaxQuality'),
            static::OPTIONS_PAGE_NAME,
            $sectionName
        );
    }

    /**
     * Render the options page
     *
     * @return void
     */
    public function renderOptionsPage()
    {
        ?>
        <form action='options.php' method='post'>
            <h1><?php echo $this->getPluginName(); ?></h1>

            <p><?php echo __('Below you can configure which maximum dimensions images uploaded to your site should have.', static::TEXT_DOMAIN); ?></p>
            <?php
            settings_fields(static::OPTIONS_PAGE_NAME);
            do_settings_sections(static::OPTIONS_PAGE_NAME);
            submit_button();
            ?>
        </form>
        <?php
    }

    /**
     * Render a field
     *
     * @param string $name
     * @param string [$type]
     *
     * @return void
     */
    protected function renderField($name, $type = 'number')
    {
        $options = $this->getOptions();
        ?>
        <input type='<?php echo $type; ?>'
               title='<?php echo $name; ?>'
               name='<?php echo $this->getOptionsName(); ?>[<?php echo $name; ?>]'
               value='<?php echo $type == 'number' ? abs((int)$options[$name]) : $options[$name]; ?>'>
        <?php
    }

    /**
     * Render a specific field
     *
     * @return void
     */
    public function renderFieldGeneralImageMaxWidth()
    {
        $this->renderField(static::FIELD_IMAGE_MAX_WIDTH);
    }

    /**
     * Render a specific field
     *
     * @return void
     */
    public function renderFieldGeneralImageMaxHeight()
    {
        $this->renderField(static::FIELD_IMAGE_MAX_HEIGHT);
    }

    /**
     * Render a specific field
     *
     * @return void
     */
    public function renderFieldGeneralImageMaxQuality()
    {
        $this->renderField(static::FIELD_IMAGE_MAX_QUALITY);
    }

    /**
     * Set image re-sizing settings
     * [Does all the magic :3]
     *
     * @param array $defaults
     * @return array
     */
    public function setImageSettings(array $defaults)
    {
        // get options
        $options = $this->getOptions();

        // set values
        $defaults['resize'] = array(
            'width' => abs((int)$options[static::FIELD_IMAGE_MAX_WIDTH]),
            'height' => abs((int)$options[static::FIELD_IMAGE_MAX_HEIGHT]),
            'quality' => abs((int)$options[static::FIELD_IMAGE_MAX_QUALITY]),
        );
        return $defaults;
    }
}

// init
new CanvasImageResize();
