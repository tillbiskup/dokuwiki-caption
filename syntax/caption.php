<?php
/**
 * DokuWiki Plugin caption (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Till Biskup <till@till-biskup.de>
 */

class syntax_plugin_caption_caption extends DokuWiki_Syntax_Plugin {

    /**
     * Array containing the types of environment supported by the plugin
     */
    private $_types = array('figure','table','codeblock','fileblock');

    private $_type = '';
    private $_incaption = false;

    private $_fignum = 1;
    private $_tabnum = 1;
    private $_codenum = 1;
    private $_filenum = 1;
    
    private $_label = '';
    
    private $_figlabels = array();
    private $_tablabels = array();
    private $_codelabels = array();
    private $_filelabels = array();
    
    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/../plugin.info.txt');
    }

    public function getType() {
        return 'container';
    }

    public function getAllowedTypes() {
        return array('formatting', 'substition', 'disabled', 'container', 'protected');
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 319;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{setcounter [a-z0-9=]+?}}',$mode,'plugin_caption_caption');
        $this->Lexer->addEntryPattern('<figure.*?>(?=.*</figure>)',$mode,'plugin_caption_caption');
        $this->Lexer->addEntryPattern('<table.*?>(?=.*</table>)',$mode,'plugin_caption_caption');
        $this->Lexer->addEntryPattern('<codeblock.*?>(?=.*</codeblock>)',$mode,'plugin_caption_caption');
        $this->Lexer->addEntryPattern('<fileblock.*?>(?=.*</fileblock>)',$mode,'plugin_caption_caption');
        $this->Lexer->addPattern('<caption>(?=.*</caption>)','plugin_caption_caption');
        $this->Lexer->addPattern('</caption>','plugin_caption_caption');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('</figure>','plugin_caption_caption');
        $this->Lexer->addExitPattern('</table>','plugin_caption_caption');
        $this->Lexer->addExitPattern('</codeblock>','plugin_caption_caption');
        $this->Lexer->addExitPattern('</fileblock>','plugin_caption_caption');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler){
        switch ($state) {
          case DOKU_LEXER_ENTER :
	      $match = substr($match,1,-1);
              return array($state, $match);
	  case DOKU_LEXER_MATCHED :
              return array($state, $match);
	  case DOKU_LEXER_UNMATCHED :
              return array($state, $match);
          case DOKU_LEXER_EXIT :
              $match = substr($match,1,-1);
              return array($state, $match);
	  case DOKU_LEXER_SPECIAL :
              if (!(strpos($match,'{{setcounter')===false)) {
                  return array($state, substr($match,13,-2));
              }
        }
        return array();
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode == 'xhtml') {

            list($state,$match) = $data;
            
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    // Handle case that there is a label in the opening tag
                    // Fix warnings in PHP >=8.1, not relying on "sexplode" in DW Jack Jackrum
                    $label = count(explode(' ',$match)) > 1 ? explode(' ',$match)[1] : null;
                    if (in_array(explode(' ',$match)[0],$this->_types)) {
                        $this->_type = explode(' ',$match)[0];
                        switch ($this->_type) {
                            case "figure" :
                                $renderer->doc .= '<figure class="plugin_caption_figure"';
                                // If we have a label, assign it to the global label array
                                if ($label) {
                                    global $caption_labels;
                                    $caption_labels[$label] = $this->_fignum;
                                    $this->_figlabels[$this->_fignum] = $label;
                                    $renderer->doc .= ' id="'.$renderer->_xmlEntities($label).'"';
                                    // WARNING: Potential harmful way of handling references
                                    //          that have already been printed
                                    $pattern = '##REF:'.$this->_figlabels[$this->_fignum].'##';
                                    if (strpos($renderer->doc, $pattern) !== FALSE) { 
                                        $renderer->doc = str_replace($pattern, $this->_fignum, $renderer->doc);
                                    }
                                }
                                $renderer->doc .= '>';
                                break;
                            case "table" :
                                $renderer->doc .= '<div class="plugin_caption_table"';
                                // If we have a label, assign it to the global label array
                                if ($label) {
                                    global $caption_labels;
                                    $caption_labels[$label] = $this->_tabnum;
                                    $this->_tablabels[$this->_tabnum] = $label;
                                    $renderer->doc .= ' id="'.$renderer->_xmlEntities($label).'"';
                                    // WARNING: Potential harmful way of handling references
                                    //          that have already been printed
                                    $pattern = '##REF:'.$this->_tablabels[$this->_tabnum].'##';
                                    if (strpos($renderer->doc, $pattern) !== FALSE) { 
                                        $renderer->doc = str_replace($pattern, $this->_tabnum, $renderer->doc);
                                    }
                                }
                                $renderer->doc .= '>';
                                break;
                            case "codeblock" :
                                $renderer->doc .= '<div class="plugin_caption_codeblock"';
                                // If we have a label, assign it to the global label array
                                if ($label) {
                                    global $caption_labels;
                                    $caption_labels[$label] = $this->_tabnum;
                                    $this->_codelabels[$this->_codenum] = $label;
                                    $renderer->doc .= ' id="'.$renderer->_xmlEntities($label).'"';
                                    // WARNING: Potential harmful way of handling references
                                    //          that have already been printed
                                    if(isset($this->_codelabels[$this->_codenum])) {
                                        $pattern = '##REF:'.$this->_codelabels[$this->_codenum].'##';
                                        if (strpos($renderer->doc, $pattern) !== FALSE) { 
                                            $renderer->doc = str_replace($pattern, $this->_codenum, $renderer->doc);
                                        }
                                    }
                                }
                                $renderer->doc .= '>';
                                break;
                            case "fileblock" :
                                $renderer->doc .= '<div class="plugin_caption_fileblock"';
                                // If we have a label, assign it to the global label array
                                if ($label) {
                                    global $caption_labels;
                                    $caption_labels[$label] = $this->_filenum;
                                    $this->_tablabels[$this->_filenum] = $label;
                                    $renderer->doc .= ' id="'.$renderer->_xmlEntities($label).'"';
                                    // WARNING: Potential harmful way of handling references
                                    //          that have already been printed
                                    $pattern = '##REF:'.$this->_filelabels[$this->_filenum].'##';
                                    if (strpos($renderer->doc, $pattern) !== FALSE) { 
                                        $renderer->doc = str_replace($pattern, $this->_filenum, $renderer->doc);
                                    }
                                }
                                $renderer->doc .= '>';
                                break;
                        }
                    }
                    break;

		case DOKU_LEXER_MATCHED :
		    // return the dokuwiki markup within the caption tags
                    if (!$this->_incaption) {
                        $this->_incaption = true;
                        switch ($this->_type) {
                            case "figure" :
                                $renderer->doc .= '<figcaption class="plugin_caption_caption"><span class="plugin_caption_caption_number"';
                                if(array_key_exists($this->_fignum,$this->_figlabels)) {
                                    $renderer->doc .= ' title="'
                                                        .$this->_figlabels[$this->_fignum].'"';
                                }
                                $renderer->doc .= '>';
                                if ($this->getConf('abbrev')) {
                                    $renderer->doc .= $this->getLang('figureabbrev');
                                } else {
                                    $renderer->doc .= $this->getLang('figurelong');
                                }
                                $renderer->doc .= ' ' . $this->_fignum . ':</span>';
                                $renderer->doc .= ' <span class="plugin_caption_caption_text">';
                                break;
                            case "table" :
                                $renderer->doc .= '<div class="plugin_caption_caption"><span class="plugin_caption_caption_number"';
                                if(array_key_exists($this->_tabnum,$this->_tablabels)) {
                                    $renderer->doc .= ' title="'
                                                        .$this->_tablabels[$this->_tabnum].'"';
                                }
                                $renderer->doc .= '>';
                                if ($this->getConf('abbrev')) {
                                    $renderer->doc .= $this->getLang('tableabbrev');
                                } else {
                                    $renderer->doc .= $this->getLang('tablelong');
                                }
                                $renderer->doc .= ' ' . $this->_tabnum . ':</span>';
                                $renderer->doc .= ' <span class="captiontext">';
                                break;
                            case "codeblock" :
                                $renderer->doc .= '<div class="plugin_caption_caption"><span class="plugin_caption_caption_number"';
                                if(array_key_exists($this->_codenum,$this->_codelabels)) {
                                    $renderer->doc .= ' title="'
                                                        .$this->_codelabels[$this->_codenum].'"';
                                }
                                $renderer->doc .= '>';
                                if ($this->getConf('abbrev')) {
                                    $renderer->doc .= $this->getLang('codeabbrev');
                                } else {
                                    $renderer->doc .= $this->getLang('codelong');
                                }
                                $renderer->doc .= ' ' . $this->_codenum . ':</span>';
                                $renderer->doc .= ' <span class="captiontext">';
                                break;
                            case "fileblock" :
                                $renderer->doc .= '<div class="plugin_caption_caption"><span class="plugin_caption_caption_number"';
                                if(array_key_exists($this->_filenum,$this->_filelabels)) {
                                    $renderer->doc .= ' title="'
                                                        .$this->_tablabels[$this->_filenum].'"';
                                }
                                $renderer->doc .= '>';
                                if ($this->getConf('abbrev')) {
                                    $renderer->doc .= $this->getLang('fileabbrev');
                                } else {
                                    $renderer->doc .= $this->getLang('filelong');
                                }
                                $renderer->doc .= ' ' . $this->_filenum . ':</span>';
                                $renderer->doc .= ' <span class="captiontext">';
                                break;
                        }
                    } else {
                        $this->_incaption = false;
                        switch ($this->_type) {
                            case "figure" :
                                $renderer->doc .= '</span></figcaption>';
                                break;
                            case "table" :
                                $renderer->doc .= '</span></div>';
                                break;
                            case "codeblock" :
                                $renderer->doc .= '</span></div>';
                                break;
                            case "fileblock" :
                                $renderer->doc .= '</span></div>';
                                break;
                        }
                    }
                    break;

                case DOKU_LEXER_UNMATCHED :
                    // return the dokuwiki markup within the figure tags
                    $renderer->doc .= $renderer->_xmlEntities($match);
                    break;

                case DOKU_LEXER_EXIT :
                    // increment figure/table number
                    switch ($this->_type) {
                        case "figure" :
                            $this->_fignum++;
                            $renderer->doc .= '</figure>';
                            break;
                        case "table" :
                            $this->_tabnum++;
                            $renderer->doc .= '</div>';
                            break;
                        case "codeblock" :
                            $this->_codenum++;
                            $renderer->doc .= '</div>';
                            break;
                        case "fileblock" :
                            $this->_filenum++;
                            $renderer->doc .= '</div>';
                            break;
                    }
                    $this->_type = '';
		    break;

		case DOKU_LEXER_SPECIAL :
		    list($_type,$_num) = explode('=',trim($match)); 
                    $_type = trim($_type);
		    $_num = (int) trim($_num);
		    if (in_array($_type,$this->_types)) {
                        switch ($_type) {
                            case "figure" :
                                $this->_fignum = $_num;
                                break;
                            case "table" :
				$this->_tabnum = $_num;
                                break;
                            case "codeblock" :
				$this->_codenum = $_num;
                                break;
                            case "fileblock" :
				$this->_filenum = $_num;
                                break;
			}
		    }
		    break;
            }
            return true;
        }
        
        if ($mode == 'latex') {

            list($state,$match) = $data;
            
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    // Handle case that there is a label in the opening tag
                    list($match,$label) = explode(' ',$match);
                    if (in_array($match,$this->_types)) {
                        $this->_type = $match;
                        switch ($this->_type) {
                            case "figure" :
                                $renderer->doc .= '\begin{figure}';
                                // If we have a label, assign it to the global label array
                                if ($label) {
                                    $this->_label = $label;
                                }
                                break;
                            case "table" :
                                $renderer->doc .= '\begin{table}';
                                if ($label) {
                                    $this->_label = $label;
                                }
                                break;
                        }
                    }
                    break;

                case DOKU_LEXER_MATCHED :
                    // return the dokuwiki markup within the caption tags
                    if (!$this->_incaption) {
                        $this->_incaption = true;
                        $renderer->doc .= '\caption{';
                    } else {
                        $renderer->doc .= '}';
                        $this->_incaption = false;
                        if ($this->_label) {
                            $renderer->doc .= "\n" . '\label{'.$this->_label.'}';
                            $this->_label = '';
                        }
                    }
                    break;

                case DOKU_LEXER_UNMATCHED :
                    // return the dokuwiki markup within the figure tags
                    $renderer->doc .= $match; //$renderer->_xmlEntities($match);
                    break;

                case DOKU_LEXER_EXIT :
                    switch ($this->_type) {
                        case "figure" :
                            $renderer->doc .= '\end{figure}' . "\n\n";
                            break;
                        case "table" :
                            $renderer->doc .= '\end{table}' . "\n\n";
                            break;
                    }
                    $this->_type = '';
                    break;
            }
            return true;
        }
        
        /**
         * WARNING: The odt mode seems to work in general, but strange things happen
         *          with the tables - therefore, within the table tags only a table
         *            is allowed, without any additional markup.
         */
        if ($mode == 'odt') {

            list($state,$match) = $data;

            switch ($state) {
                case DOKU_LEXER_ENTER :
                    // Handle case that there is a label in the opening tag
                    list($match,$label) = explode(' ',$match);
                    if (in_array($match,$this->_types)) {
                        $this->_type = $match;
                        switch ($this->_type) {
                            case "figure" :
                                // If we have a label, assign it to the global label array
                                if ($label) {
                                    global $caption_labels;
                                    $caption_labels[$label] = $this->_fignum;
                                    $this->_label = $label;
                                }
                                break;
                            case "table" :
                                // If we have a label, assign it to the global label array
                                if ($label) {
                                    global $caption_labels;
                                    $caption_labels[$label] = $this->_tabnum;
                                    $this->_label = $label;
                                }
                                break;
                        }
                        $renderer->p_open();
                    }
                    break;

                case DOKU_LEXER_MATCHED :
                    // return the dokuwiki markup within the caption tags
                    if (!$this->_incaption) {
                        $this->_incaption = true;
                        $renderer->p_close();
                        switch ($this->_type) {
                            case "figure" :
                                if ($this->getConf('abbrev')) {
                                    $renderer->cdata($this->getLang('figureabbrev'));
                                } else {
                                    $renderer->cdata($this->getLang('figurelong'));
                                }
                                $renderer->cdata(" " . $this->_fignum . ": ");
                                break;
                            case "table" :
                                if ($this->getConf('abbrev')) {
                                    $renderer->cdata($this->getLang('tableabbrev'));
                                } else {
                                    $renderer->cdata($this->getLang('tablelong'));
                                }
                                $renderer->cdata(" " . $this->_tabnum . ": ");
                                break;
                        }
                    } else {
                        $this->_incaption = false;
                        switch ($this->_type) {
                            case "figure" :
                                $renderer->p_open();
                                break;
                            case "table" :
//                                $renderer->p_open();
                                break;
                        }
                    }
                    break;

                case DOKU_LEXER_UNMATCHED :
                    // return the dokuwiki markup within the figure tags
                    $renderer->cdata($match);
                    break;

                case DOKU_LEXER_EXIT :
                    // increment figure/table number
                    switch ($this->_type) {
                        case "figure" :
                            $this->_fignum++;
                            $renderer->p_close();
                            break;
                        case "table" :
                            $this->_tabnum++;
                            break;
                    }
                    $this->_type = '';
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }
}

// vim:ts=4:sw=4:et:
