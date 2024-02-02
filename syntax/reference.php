<?php
/**
 * DokuWiki Plugin caption (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Till Biskup <till@till-biskup.de>
 */

class syntax_plugin_caption_reference extends DokuWiki_Syntax_Plugin {

    /**
     * Array containing the types of environment supported by the plugin
     */

    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/../plugin.info.txt');
    }

    public function getType() {
        return 'substition';
    }

    public function getAllowedTypes() {
        return array('formatting', 'substition', 'disabled', 'container', 'protected');
    }

    public function getPType() {
        return 'normal';
    }

    public function getSort() {
        return 319;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{ref>.+?}}',$mode,'plugin_caption_reference');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler){
        if (!(strpos($match,'{{ref>')===false)) {
              return array($state, substr($match,6,-2));
        }
        return array();
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode == 'xhtml') {

	    list($state,$match) = $data;
            
            switch ($state) {
                case DOKU_LEXER_SPECIAL :
                    global $caption_labels;
                    $renderer->doc .= '<a href="#'.$match.'">';
                    if (isset($caption_labels[$match]) && $caption_labels[$match]) {
                        $renderer->doc .= $caption_labels[$match];
                    } else {
                        $renderer->doc .= '##REF:'.$match.'##';
                    }
                    $renderer->doc .= '</a>';
                    break;
            }
            return true;
        }
        
        if ($mode == 'latex') {

            list($state,$match) = $data;
            
            switch ($state) {
                case DOKU_LEXER_SPECIAL :
                    $renderer->doc .= '\ref{'.$match.'}';
                    break;
            }
            return true;
        }
        
        if ($mode == 'odt') {

            list($state,$match) = $data;
            
            switch ($state) {
                case DOKU_LEXER_SPECIAL :
                    $renderer->doc .= '<text:sequence-ref text:reference-format="value" text:ref-name="'.$match.'">';
                    global $caption_labels;
                    if (isset($caption_labels[$match]) && $caption_labels[$match]) {
                        $renderer->doc .= $caption_labels[$match];
                    } else {
                        $renderer->doc .= '##REF:'.$match.'##';
                    }
                    $renderer->doc .= '</text:sequence-ref>';
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }
}

// vim:ts=4:sw=4:et:
