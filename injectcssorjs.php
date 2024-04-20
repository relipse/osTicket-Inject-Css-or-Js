<?php
require_once('polyfill.php');
require_once(INCLUDE_DIR . 'class.plugin.php');		
require_once(INCLUDE_DIR . 'class.osticket.php');
require_once('config.php');

class InjectCssOrJs extends Plugin {
    //Warning, if you change any of these constants, you also need to change the inject functions
    const SIGNAL_CLIENT_CSS = 'TfnInjectClientCss';
    const SIGNAL_CLIENT_JS = 'TfnInjectClientJs';
    const SIGNAL_STAFF_CSS = 'TfnInjectStaffCss';
    const SIGNAL_STAFF_JS = 'TfnInjectStaffJs';
    const ENDING_HEAD_TAG = '</head>';

    public $config_class = "InjectCssOrJsConfig";

    /**
     * Wraps the given value based on the provided key.
     * This method takes a key and a value as input parameters and checks if the key contains either "css" or "js".
     * If the key contains "css", the value is wrapped inside a `<style>` tag.
     * If the key contains "js", the value is wrapped inside a `<script>` tag.
     * Otherwise, an empty string is returned.
     *
     * @param string $key The key used to determine the wrapping tag.
     * @param string $val The value to be wrapped.
     * @return string The wrapped value, or an empty string if the key is invalid or the value is empty.
     */
    public function _wrapValueBasedOnKey(string $key, string $val): string {
        if (empty($val)){
            return "";
        }
        $tag = null;
        if (str_contains($key, 'css')){
            $tag = 'style';
        }else if (str_contains($key, 'js')){
            $tag = 'script';
        }
        if (empty($tag)){
            return "";
        }
        return '<'.$tag.'>'."\n".$val."\n".'</'.$tag.'>'."\n";
    }


    /**
     * Injects custom code based on the given key.
     *
     * @param string $configKey The key to identify the custom code to inject.
     * @return void
     */
    public function inject(string $configKey){
        $config = $this->getConfig();
        $val = $config->get($configKey);
        $hideComments = $config->get('hide-begin-end-comments');
        $s = $this->_wrapValueBasedOnKey($configKey, $val);
        if (!empty($s)){
            if (empty($hideComments)) {
                echo '<!-- TCustomCode: ' . $configKey . ' Start -->' . "\n";
            }
            echo $s;
            if (empty($hideComments)) {
                echo '<!-- TCustomCode: ' . $configKey . ' End -->' . "\n";
            }
        }
    }

    public function injectTfnInjectClientCss() {
        $this->inject('custom-code-css');
    }
    public function injectTfnInjectClientJs() {
        $this->inject('custom-code-js');
    }
    public function injectTfnInjectStaffCss() {
        $this->inject('custom-staff-code-css');
    }
    public function injectTfnInjectStaffJs() {
        $this->inject('custom-staff-code-js');
    }

    /**
     * Generate the signal code for the given CSS and JS signals.
     *
     * This method generates the signal code that needs to be added to the header.inc.php file.
     * It includes PHP code to send the CSS and JS signals.
     * The CSS and JS signals are passed as arguments to this method and are inserted into the generated code.
     *
     * @param string $cssSignal The CSS signal to be sent.
     * @param string $jsSignal The JS signal to be sent.
     * @return string The generated signal code.
     */
    protected function _generateSignalCode(string $cssSignal, string $jsSignal): string{
        return <<<EOT
<?php Signal::send('$cssSignal', null); ?>
<?php Signal::send('$jsSignal', null); ?>
</head>
EOT;
    }

    /**
     * Checks if a custom signal is present in the head of a file. If not, it installs the custom signal.
     *
     * @param string $filepath The path of the file to check or install the custom signal.
     * @param string $lookForReplace The custom signal to look for or install.
     * @return bool Returns true if signal was added to file in $filepath, false otherwise.
     */
    protected function _checkOrInstallCustomSignalInHead(string $filepath, string $lookForReplace): bool{
        $contents = file_get_contents($filepath);
        if (!str_contains($contents, $lookForReplace)){
            $contents = str_replace(self::ENDING_HEAD_TAG, $lookForReplace, $contents);
            return file_put_contents($filepath, $contents) !== false;
        }
        return false; //nothing added
    }


    /**
     * Install the specified signal in the header.inc.php file.
     *
     * @param string $where The location where the signal should be installed. Can be either 'client' or 'staff'.
     * @return bool True if the signal was successfully installed, False otherwise.
     */
    protected function _ensureSignalInstalledInHead(string $where): bool{
        $filepath = INCLUDE_DIR . $where . '/header.inc.php';
        switch($where){
            case 'client':
                $signalCss = self::SIGNAL_CLIENT_CSS;
                $signalJs = self::SIGNAL_CLIENT_JS;
                break;
            case 'staff':
                $signalCss = self::SIGNAL_STAFF_CSS;
                $signalJs = self::SIGNAL_STAFF_JS;
                break;
            default:
                return false;
        }
        Signal::connect($signalCss, array($this, 'inject'.$signalCss));
        Signal::connect($signalJs, array($this, 'inject'.$signalJs));
        return $this->_checkOrInstallCustomSignalInHead($filepath, $this->_generateSignalCode($signalCss, $signalJs));
    }

    /**
     * Performs the necessary actions to bootstrap the application.
     * This method modifies the header.inc.php file by adding signals for both client and staff.
     * If the header already contains the signal, it will not be added again.
     *
     * @return void
     */
    public function bootstrap() {
        //So, the issue here is that, we will be modifying
        //header.inc.php, which means that the directory needs to
        //be writeable, if the actual header already contains the signal, then it will not be added.
        $this->_ensureSignalInstalledInHead('client');
        $this->_ensureSignalInstalledInHead('staff');
    }
}