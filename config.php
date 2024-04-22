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
        $onlyAppliesFrontEnd = $__('This only applies in the frontend user section.');
        $onlyAppliesStaff = $__('This only applies in the admin/staff section.');
        return array(
            'customcodeHeading' => new SectionBreakField(array(
                'label' => $__('Enter your custom code below (it is injected into the source, right above the &lt;/head&gt; and take effect on every page load)')
            )),
            'custom-code-css' => new TextareaField(array(
                'label' => $__('Frontend/Client CSS'),
                'hint' => $onlyAppliesFrontEnd,
                'configuration' => array('rows'=>10, 'cols'=>80, 'html'=>false, 'class' => 'syntaxHighlight syntax-css',),
            )),
            'custom-code-js' => new TextareaField(array(
                'label' => $__('Frontend/Client JS'),
                'hint' => $onlyAppliesFrontEnd,
                'configuration' => array('rows'=>10, 'cols'=>80, 'html'=>false, 'class' => 'syntaxHighlight syntax-js',),
            )),
            'custom-staff-code-css' => new TextareaField(array(
                'label' => $__('Admin/Staff CSS'),
                'hint' => $onlyAppliesStaff,
                'configuration' => array('rows'=>10, 'cols'=>80, 'html'=>false,'class' => 'syntaxHighlight syntax-css',),
            )),
            'custom-staff-code-js' => new TextareaField(array(
                'label' => $__('Admin/Staff JS'),
                'hint' => $onlyAppliesStaff,
                'configuration' => array(
                    'rows'=>10,
                    'cols'=>80,
                    'html'=>false,
                    'class' => 'syntaxHighlight syntax-js',
                ),
            )),
            'hide-begin-end-comments' => new BooleanField([
                'label'=>__('Hide Begin/End Comments'),
                'hint'=> __('In HTML Source (not visible to user anyway), do not put helper comments.'),
                'required'=>false,
                'configuration'=> [
                    'desc'=>__(htmlentities("Do not put comments in html (like: <!-- TfnInjectClientCss: custom-code-css Start -->)"))
                ]
            ]),
            'use-syntax-highlighter' => new BooleanField([
                'label' => __('Use Syntax Highlighter'),
                'hint' => __('For JavaScript and CSS, show pretty syntax highlighter (for this staff page only)'),
                'required' => false,
                'configuration' => [
                    'desc' => __(htmlentities('On this page only, instead of regular textareas, use pretty syntax highlighters for JavaScript and CSS.')),
                ]
            ]),
        );
    }
}