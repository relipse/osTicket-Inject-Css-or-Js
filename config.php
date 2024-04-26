<?php
require_once('polyfill.php');
require_once INCLUDE_DIR . 'class.plugin.php';

class InjectCssOrJsConfig extends PluginConfig {

    // Provide compatibility function for versions of osTicket prior to
    // translation support (v1.9.4)
    public function translate(): array {
        if (!method_exists('Plugin', 'translate')) {
            return [
                function($x) { return $x; },
                function($x, $y, $n) { return $n != 1 ? $y : $x; },
            ];
        }
        return Plugin::translate('injectcssorjs');
    }

    public function getOptions(): array {
        list($__, $_N) = self::translate();
        $onlyAppliesFrontEnd = $__('This only applies in the frontend user section.');
        $onlyAppliesStaff = $__('This only applies in the admin/staff section.');
        //$onlyAppliesLogin = $__('This only applies in the <b>staff</b> login section.');

        $textareaConfig = ['rows'=>10, 'cols'=>80, 'html'=>false];
        $textareaConfigCss = array_merge($textareaConfig, ['class' => 'syntaxHighlight syntax-css']);
        $textareaConfigJs = array_merge($textareaConfig, ['class' => 'syntaxHighlight syntax-js']);
        return [
            'injectcssorjs-plugin-page-heading' => new SectionBreakField([
                'label' => $__('Enter your custom code below (it is injected into the source, right above the &lt;/head&gt; and take effect on every page load)')
            ]),
            'use-syntax-highlighter' => new BooleanField([
                'label' => $__('Use Syntax Highlighter'),
                'hint' => $__('For JavaScript (JS) and Styles (CSS), show pretty syntax highlighter (for this staff plugin page only)'),
                'required' => false,
                'configuration' => [
                    'desc' => $__('Use pretty syntax highlighters on this plugin page (save or <a style="display: inline" href="javascript:location.reload();">reload</a> if not visible).'),
                ]
            ]),
            'injectcssorjs-code-css' => new TextareaField([
                'label' => $__('Customer/Client Styles (CSS)'),
                'hint' => $onlyAppliesFrontEnd,
                'configuration' => $textareaConfigCss,
            ]),
            'injectcssorjs-code-js' => new TextareaField([
                'label' => $__('Customer/Client JavaScript (JS)'),
                'hint' => $onlyAppliesFrontEnd,
                'configuration' => $textareaConfigJs,
            ]),
            'injectcssorjs-staff-css' => new TextareaField([
                'label' => $__('Admin/Staff Styles (CSS)'),
                'hint' => $onlyAppliesStaff,
                'configuration' => $textareaConfigCss,
            ]),
            'injectcssorjs-staff-js' => new TextareaField([
                'label' => $__('Admin/Staff JavaScript (JS)'),
                'hint' => $onlyAppliesStaff,
                'configuration' => $textareaConfigJs,
            ]),
            // Integrating css and js on the scp/login.php page is more difficult than I thought, so leave it commented out for later.
            /*
            'injectcssorjs-staff-login-css' => new TextareaField([
                'label' => $__('Staff Login Code Styles (CSS)'),
                'hint' => $onlyAppliesLogin,
                'configuration' => $textareaConfigCss,
            ]),
            'injectcssorjs-staff-login-js' => new TextareaField([
                'label' => $__('Staff Login JavaScript (JS)'),
                'hint' => $onlyAppliesLogin,
                'configuration' => $textareaConfigJs,
            ]),
            */
            'hide-begin-end-comments' => new BooleanField([
                'label'=>__('Hide Begin/End Comments'),
                'hint'=> __('In HTML Source (not visible to user anyway), do not put helper comments.'),
                'required'=>false,
                'configuration'=> [
                    'desc'=>$__(htmlentities("Do not put comments in html (like: <!-- InjectCssJsPlg: injectcssorjs-code-css Start -->)"))
                ]
            ]),
        ];
    }
}