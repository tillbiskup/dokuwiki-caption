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
        
        if ($mode == 'odt' || $mode == 'odt_pdf') {

            list($state,$match) = $data;

            switch ($state) {
                case DOKU_LEXER_SPECIAL :
                    global $caption_labels;
                    $ref = null;
                    if (isset($caption_labels[$match]) && $caption_labels[$match] !== '') {
                        $ref = $caption_labels[$match];
                    } elseif (function_exists('plugin_load')) {
                        $captionPlugin = plugin_load('syntax', 'caption_caption');
                        if ($captionPlugin && method_exists($captionPlugin, 'resolveForwardRef')) {
                            $ref = $captionPlugin->resolveForwardRef($match);
                        }
                    }

                    if ($ref !== null) {
                        $refStr = (string) $ref;
                        if (method_exists($renderer, 'insertBookmark') && method_exists($renderer, 'externallink')) {
                            if (class_exists('syntax_plugin_caption_caption') && method_exists('syntax_plugin_caption_caption', 'odtBookmarkId')) {
                                $bookmarkId = syntax_plugin_caption_caption::odtBookmarkId($match, $renderer);
                            } else {
                                $escaped = method_exists($renderer, '_xmlEntities')
                                    ? $renderer->_xmlEntities(trim($match))
                                    : htmlspecialchars(trim($match), ENT_QUOTES, 'UTF-8');
                                $bookmarkId = 'plugin_caption_' . str_replace(' ', '_', strtolower($escaped));
                            }
                            $renderer->p_open();
                            $renderer->externallink('#' . $bookmarkId, $refStr);
                        } else {
                            $renderer->cdata($refStr);
                        }
                    } else {
                        $renderer->cdata('##REF:'.$match.'##');
                    }
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }
}

// vim:ts=4:sw=4:et:
