<?php
/**
 * Adminn Component for Securelogin Dokuwiki Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mikhail I. Izmestev
 * @maintainer Matt Bagley
 *
 * @see also   https://www.dokuwiki.org/plugin:securelogin
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_securelogin extends DokuWiki_Admin_Plugin {
    protected $slhlp = null;

    function __construct() {
        $this->slhlp = plugin_load('helper', $this->getPluginName());
        if(!$this->slhlp) msg('Loading the '.$this->getPluginName().' helper failed. Make sure that the '.$this->getPluginName().' plugin is installed.', -1);
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 999;
    }

    function getMenuText($lang) {
        return $this->getLang('securelogin_conf');
    }

    /**
     * handle user request
     */
    function handle() {
        if(!$this->slhlp->canWork())
            msg("You need openssl php module for this plugin work!", -1);
        elseif($this->slhlp->haveKey() && !$this->slhlp->workCorrect())
            msg("Your version of dokuwiki not generate AUTH_LOGIN_CHECK event, plugin not work!");

        $fn = $_REQUEST['fn'];

        if (is_array($fn)) {
            $cmd = key($fn);
            $param = $fn[$cmd];
        } else {
            $cmd = $fn;
            $param = null;
        }

        switch($cmd) {
            case "newkey": $this->slhlp->generateKey($param); break;
            case "test": msg(urldecode($this->slhlp->decrypt($param['message']))); break;
        }
    }

    /**
     * output appropriate html
     */
    function html() {
        if(!$this->slhlp->canWork()) {
            print $this->locale_xhtml('needopenssl');
            return;
        }
        elseif($this->slhlp->haveKey() && !$this->slhlp->workCorrect())
            print $this->locale_xhtml('needpatch');
        ptln('<div id="secure__login">');
        $this->_html_generateKey();

        if($this->slhlp->haveKey()) {
            $this->_html_test();

//      print $this->render("===== ".$this->getLang('public_key')." ===== \n".
//          "<code>\n".
//          $this->slhlp->getPublicKey().
//          "</code>",
//          $format='xhtml');
        }
        ptln('</div>');
    }

    function _html_generateKey() {
        global $ID;
        $form = new Doku_Form('generate__key', wl($ID,'do=admin,page='.$this->getPluginName(), false, '&'));
        $form->startFieldset($this->getLang('generate_key'));
        $form->addElement(form_makeMenuField('fn[newkey]', $this->slhlp->getKeyLengths(), $this->slhlp->getKeyLength(), $this->getLang('key_length'), 'key__length', 'block', array('class' => 'edit')));
        $form->addElement(form_makeButton('submit', '', $this->getLang('generate')));
        $form->endFieldset();
        ptln('<div class="half">');
        html_form('generate', $form);
        ptln('</div>');
    }

    function _html_test() {
        global $ID;
        $form = new Doku_Form('test__publicKey', wl($ID,'do=admin,page='.$this->getPluginName(), false, '&'));
        $form->startFieldset($this->getLang('test_key'));
        $form->addElement(form_makeTextField('fn[test][message]', $this->getLang('sample_message'), $this->getLang('test_message'), 'test__message', 'block'));
        $form->addElement(form_makeButton('submit', '', $this->getLang('test')));
        $form->endFieldset();
        html_form('test__publicKey', $form);
    }
}
