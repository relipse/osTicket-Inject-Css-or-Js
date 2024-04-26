<?php
require_once('polyfill.php');
require_once(INCLUDE_DIR . 'class.plugin.php');		
require_once(INCLUDE_DIR . 'class.osticket.php');
require_once('config.php');

class InjectCssOrJs extends Plugin {
    //Warning, if you change any of these constants, you also need to change the inject functions
    const SIGNAL_CLIENT_CSS = 'inject.client.css';
    const SIGNAL_CLIENT_JS = 'inject.client.js';
    const SIGNAL_STAFF_CSS = 'inject.staff.css';
    const SIGNAL_STAFF_JS = 'inject.staff.js';
    //const SIGNAL_STAFF_LOGIN_CSS = 'inject.staff.login.css';
    //const SIGNAL_STAFF_LOGIN_JS = 'inject.staff.login.js';
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
        if (preg_match('/-css$/', $key)){
            $tag = 'style';
        }else if (preg_match('/-js$/', $key)){
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
    public function inject(string $configKey): bool{
        $config = $this->getConfig();
        $val = $config->get($configKey);
        if (empty($val)){
            //nothing to inject
            return false;
        }
        $hideComments = $config->get('hide-begin-end-comments');
        $s = $this->_wrapValueBasedOnKey($configKey, $val);
        if (!empty($s)){
            if (empty($hideComments)) {
                echo '<!-- InjectCssJsPlg: ' . $configKey . ' Start -->' . "\n";
            }
            echo $s;
            if (empty($hideComments)) {
                echo '<!-- InjectCssJsPlg: ' . $configKey . ' End -->' . "\n";
            }
            return true;
        }
    }

    public function injectInjectCssOrJsClientCss() {
        $this->inject('injectcssorjs-code-css');
    }
    public function injectInjectCssOrJsClientJs() {
        $this->inject('injectcssorjs-code-js');
    }

    protected function _syntaxHighlighterEnabledOnPage(): bool{
        return $this->_onPluginPage() && $this->_useSyntaxHighlighter();
    }

    protected function _onPluginPage(): bool{
        return isset($_GET['id']) && basename($_SERVER['PHP_SELF']) === 'plugins.php';
    }

    protected function _useSyntaxHighlighter(): bool
    {
        $cfg = $this->getConfig();
        return !empty($cfg->get('use-syntax-highlighter'));
    }

    public function injectInjectCssOrJsStaffCss() {
        $this->inject('injectcssorjs-staff-css');
        if ($this->_syntaxHighlighterEnabledOnPage()){
            echo <<<EOT
            <style>
                .ace_editor, .ace_editor *{
                    font-family: "Monaco", "Menlo", "Ubuntu Mono", "Droid Sans Mono", "Consolas", monospace !important;
                    font-size: 12px !important;
                    font-weight: 400 !important;
                    letter-spacing: 0 !important;
                }
                .syntaxHighlightDiv {
                      width: 600px;
                      height: 270px;
                      border: 1px solid lightgray;
                }
            </style>
            EOT;
        }
    }

    public function injectInjectCssOrJsStaffLoginJs(){
        $this->inject('injectcssorjs-staff-login-js');
    }

    public function injectInjectCssOrJsStaffLoginCss(){
        $this->inject('injectcssorjs-staff-login-css');
    }

    public function injectInjectCssOrJsStaffJs() {
        $this->inject('injectcssorjs-staff-js');
        if ($this->_syntaxHighlighterEnabledOnPage()) {
            echo <<<EOT
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.9.6/ace.js"></script>
<script>
if ($){
    $(function(){
        function determineModeFromClass(elem){
            if (elem.hasClass('syntax-js') || elem.hasClass('syntax-javascript')){
                return 'js';
            }
            if (elem.hasClass('syntax-css')){
                return 'css';
            }
            return '';
        }
        $('textarea.syntaxHighlight').each(function(){
            let self = $(this);
            let mode = determineModeFromClass(self);
            let theme = 'default';
            if (!theme){
                theme = 'default';
            }
            switch(mode){
                case 'js':
                    mode = 'javascript';
                    break;
                case 'css':
                    mode = 'css';
                    break;
                default:
                    mode = null;
            }
            if (mode){
                let txtId = self.attr('id');
                  let newDivExists = ($('div[data-textarea-id="'+txtId+'"]').length >= 1);
                  if (!newDivExists){
                      let nwDiv = $('<div data-textarea-id="'+txtId+'" class="syntaxHighlightDiv syntaxhighlightDivMode-'+mode+'"></div>');
                      self.after(nwDiv);
                      let editor = ace.edit(nwDiv.get(0));
                      editor.setTheme("ace/theme/monokai");
                      editor.session.setMode("ace/mode/"+mode);
                        
                      editor.setValue(self.val());  // copy the textarea value to the editor
                      editor.session.on('change', function(){ 
                        self.val(editor.getValue());  // update the textarea value if the editor changes
                      });
                      self.hide();
                      editor.clearSelection();
                  }
            }
        });
    });
}
</script>
EOT;
        }
    }

    protected function _generateSignalPhp(string $signal): string{
        return "<?php /* DO NOT MODIFY ANYTHING ON THIS LINE */ Signal::send('$signal', null); ?>";
    }


    protected function _getSignalsNotInFile(string $fileContents, array $signals): array{
        $signalsNotInFile = [];
        foreach($signals as $signal){
            if (is_string($signal) && !str_contains($fileContents, $signal)){
                $signalsNotInFile[] = $signal;
            }
        }
        return $signalsNotInFile;
    }

    /**
     * Checks if the given signals are present in the specified file's contents.
     * If any of the signals are missing, installs them by replacing the existing signals with the given ones.
     *
     * @param string $filepath The path to the file to check and manipulate.
     * @param array $signals An array of signals to be checked and installed.
     * @return bool Returns true if all signals are already present in the file or successfully installed, false otherwise.
     */
    protected function _checkOrInstallCustomSignalsInHead(string $filepath, array $signals): bool{
        if (empty($signals)){
            return false;
        }
        $contents = file_get_contents($filepath);
        if (empty($contents)){
            return false;
        }
        $signalsNotInFile = $this->_getSignalsNotInFile($contents, $signals);

        if (count($signalsNotInFile) > 0) {
            //if any of the signals are not in the file, we need to install them
            //First, let's go ahead and remove all signals.
            $contents = str_replace($signals, '', $contents);
            $strSignals = implode("\n", $signals);
            $contents = str_replace(self::ENDING_HEAD_TAG, $strSignals . "\n" . self::ENDING_HEAD_TAG, $contents);
            return !empty(file_put_contents($filepath, $contents));
        }else{
            //all signals are already in file
            return true;
        }
    }


    /**
     * Install the specified signal in the header.inc.php file.
     * With this method of injecting css/js into the site, there is an issue:
     * If deployment is simply a git pull, if any of the header.inc.php files are modified on the fly, then
     * when we do a "git pull" it will likely fail if there are modifications to any of those header.inc.php files
     * (during an upgrade?)
     * I believe the solution is to reset the local change on the header.inc.php files before doing git pull.
     * If everything works right, the Signals will be installed on the next page reload anyway.
     * Another methodology is to have a commit to our repo which puts the signals there already so there are "no changes"
     *
     * @param string $where The location where the signal should be installed. Can be either 'client' or 'staff'.
     * @return bool True if the signal was successfully installed, False otherwise.
     */
    protected function _ensureSignalInstalledInHead(string $where): bool{
        //if ($where === 'staff-login'){
        //    $filepath = INCLUDE_DIR.'staff/login.header.php';
        //}else {
        $filepath = INCLUDE_DIR . $where . '/header.inc.php';
        //}
        switch($where){
            case 'client':
                $signalCss = self::SIGNAL_CLIENT_CSS;
                $signalJs = self::SIGNAL_CLIENT_JS;
                $funcCss = 'injectInjectCssOrJsClientCss';
                $funcJs = 'injectInjectCssOrJsClientJs';
                break;
            case 'staff':
                $signalCss = self::SIGNAL_STAFF_CSS;
                $signalJs = self::SIGNAL_STAFF_JS;
                $funcCss = 'injectInjectCssOrJsStaffCss';
                $funcJs = 'injectInjectCssOrJsStaffJs';
                break;
            //Staff login page injection is more difficult and
            //not worth it at this time. The staff/login.header.php page
            //does not allow Signal injection properly.
            /*
            case 'staff-login':
                $signalCss = self::SIGNAL_STAFF_LOGIN_CSS;
                $signalJs = self::SIGNAL_STAFF_LOGIN_JS;
                $funcCss = 'injectInjectCssOrJsStaffLoginCss';
                $funcJs = 'injectInjectCssOrJsStaffLoginJs';
            */
            default:
                return false;
        }
        $signalCssPhp = $this->_generateSignalPhp($signalCss);
        $signalJsPhp = $this->_generateSignalPhp($signalJs);
        $installedAlready = $this->_checkOrInstallCustomSignalsInHead($filepath, [$signalCssPhp, $signalJsPhp]);
        Signal::connect($signalCss, array($this, $funcCss));
        Signal::connect($signalJs, array($this, $funcJs));
        return $installedAlready;
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
        //$this->_ensureSignalInstalledInHead('staff-login');
    }
}