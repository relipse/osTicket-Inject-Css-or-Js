<?php
require_once('polyfill.php');
require_once INCLUDE_DIR . 'class.plugin.php';

class InjectCssOrJsConfig extends PluginConfig {

    // Provide compatibility function for versions of osTicket prior to
    // translation support (v1.9.4)
    public function translate() {
        if (!method_exists('Plugin', 'translate')) {
            return array(
                function($x) { return $x; },
                function($x, $y, $n) { return $n != 1 ? $y : $x; },
            );
        }
        return Plugin::translate('customcode');
    }

    public function getOptions() {
        list($__, $_N) = self::translate();        
        return array(
            'customcodeHeading' => new SectionBreakField(array(
                'label' => $__('Enter your custom code below (it is injected into the source, right above the &lt;/head&gt; and take effect on every page load)')
            )),
            'custom-code-css' => new TextareaField(array(
                'label' => $__('Custom Client CSS'),
                'configuration' => array('rows'=>10, 'cols'=>80, 'html'=>false),                
            )),
            'custom-code-js' => new TextareaField(array(
                'label' => $__('Custom Client JS'),
                'configuration' => array('rows'=>10, 'cols'=>80, 'html'=>false),                
            )),
            'custom-staff-code-css' => new TextareaField(array(
                'label' => $__('Custom Staff CSS'),
                'configuration' => array('rows'=>10, 'cols'=>80, 'html'=>false),                
            )),
            'custom-staff-code-js' => new TextareaField(array(
                'label' => $__('Custom Staff JS'),
                'configuration' => array('rows'=>10, 'cols'=>80, 'html'=>false),                
            )),
            'hide-begin-end-comments' => new BooleanField([
                'label'=>__('Hide Begin/End Comments in HTML Source'),
                'required'=>false,
                'configuration'=> [
                    'desc'=>__(htmlentities("Do not put comments in html (like: <!-- TfnInjectClientCss: custom-code-css Start -->)"))
                ]
            ])
        );
    }
}