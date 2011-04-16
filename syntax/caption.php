<?php
/**
 * DokuWiki Plugin caption (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Till Biskup <till@till-biskup>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_caption_caption extends DokuWiki_Syntax_Plugin {

    /**
     * Array containing the types of environment supported by the plugin
     */
	private $_types = array('figure','table');

	private $_type = '';
	private $_incaption = false;

	private $_fignum = 1;
	private $_tabnum = 1;
	
	private $_label = '';
	
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
//        return 'stack';
        return 'block';
    }

    public function getSort() {
        return 319;
    }


    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('<figure.*?>(?=.*</figure>)',$mode,'plugin_caption_caption');
        $this->Lexer->addEntryPattern('<table.*?>(?=.*</table>)',$mode,'plugin_caption_caption');
        $this->Lexer->addPattern('<caption>(?=.*</caption>)','plugin_caption_caption');
        $this->Lexer->addPattern('</caption>','plugin_caption_caption');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('</figure>','plugin_caption_caption');
        $this->Lexer->addExitPattern('</table>','plugin_caption_caption');
    }

    public function handle($match, $state, $pos, &$handler){
        switch ($state) {
          case DOKU_LEXER_ENTER :
            $match = substr($match,1,-1);
          	return array($state, $match);
          case DOKU_LEXER_MATCHED :    return array($state, $match);
          case DOKU_LEXER_UNMATCHED :  return array($state, $match);
          case DOKU_LEXER_EXIT :
            $match = substr($match,1,-1);
          	return array($state, $match);
        }
        return array();
    }

    public function render($mode, &$renderer, $data) {
        if ($mode == 'xhtml') {

            list($state,$match) = $data;
            
            switch ($state) {
                case DOKU_LEXER_ENTER :
                	// Handle case that there is a label in the opening tag
                	list($match,$label) = explode(' ',$match);
					if (in_array($match,$this->_types)) {
						$this->_type = $match;
						switch ($this->_type) {
							case figure :
		    	                $renderer->doc .= '<div class="figure">';
		    	                // If we have a label, assign it to the global label array
			                	if ($label) {
			                		global $caption_labels;
			                		$caption_labels[$label] = $this->_fignum;
//			                		$_SESSION['caption_labels'][$label] = $this->_fignum;
            			    	}
		        	            break;
                    		case table :
		    	                $renderer->doc .= '<div class="table">';
		    	                // If we have a label, assign it to the global label array
			                	if ($label) {
			                		global $caption_labels;
			                		$caption_labels[$label] = $this->_tabnum;
//			                		$_SESSION['caption_labels'][$label] = $this->_tabnum;
            			    	}
		                    	break;
						}
					}
                    break;

                case DOKU_LEXER_MATCHED :
                	// return the dokuwiki markup within the caption tags
                	if (!$this->_incaption) {
                		$this->_incaption = true;
	                    $renderer->doc .= '<div class="caption">';
						switch ($this->_type) {
							case figure :
		    	                $renderer->doc .= '<span class="captionno">';
		    	                if ($this->getConf('abbrev')) {
		    	                	$renderer->doc .= $this->getLang('figureabbrev');
		    	                } else {
		    	                	$renderer->doc .= $this->getLang('figurelong');
		    	                }
		    	                $renderer->doc .= ' ' . $this->_fignum . ':</span>';
		    	                $renderer->doc .= ' <span class="captiontext">';
		        	            break;
                    		case table :
		    	                $renderer->doc .= '<span class="captionno">';
		    	                if ($this->getConf('abbrev')) {
		    	                	$renderer->doc .= $this->getLang('tableabbrev');
		    	                } else {
		    	                	$renderer->doc .= $this->getLang('tablelong');
		    	                }
		    	                $renderer->doc .= ' ' . $this->_tabnum . ':</span>';
		    	                $renderer->doc .= ' <span class="captiontext">';
		                    	break;
						}
                	} else {
                		$this->_incaption = false;
        	            $renderer->doc .= '</span></div>';
                	}
                    break;

                case DOKU_LEXER_UNMATCHED :
                	// return the dokuwiki markup within the figure tags
                    $renderer->doc .= $renderer->_xmlEntities($match);
                    break;

                case DOKU_LEXER_EXIT :
					// increment figure/table number
					switch ($this->_type) {
						case figure :
                    		$this->_fignum++;
                    		break;
                    	case table :
                    		$this->_tabnum++;
                    		break;
					}
					$this->_type = '';
		    	    $renderer->doc .= '</div>';
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
							case figure :
		    	                $renderer->doc .= '\begin{figure}';
		    	                // If we have a label, assign it to the global label array
			                	if ($label) {
			                		$this->_label = $label;
            			    	}
		        	            break;
                    		case table :
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
						case figure :
		                    $renderer->doc .= '\end{figure}' . "\n\n";
		    	            break;
                		case table :
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
         *			is allowed, without any additional markup.
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
							case figure :
		    	                // If we have a label, assign it to the global label array
			                	if ($label) {
			                		global $caption_labels;
			                		$caption_labels[$label] = $this->_fignum;
            			    	}
		        	            break;
                    		case table :
		    	                // If we have a label, assign it to the global label array
			                	if ($label) {
			                		global $caption_labels;
			                		$caption_labels[$label] = $this->_tabnum;
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
							case figure :
			                    $renderer->doc .= '<text:p text:style-name="Illustration">';
		    	                if ($this->getConf('abbrev')) {
		    	                	$renderer->doc .= $this->getLang('figureabbrev');
		    	                } else {
		    	                	$renderer->doc .= $this->getLang('figurelong');
		    	                }
		    	                $renderer->doc .= '<text:sequence text:ref-name="';
			            		if ($this->_label) {
					                $renderer->doc .= $this->_label;
					                $this->_label = '';
		            			} else {
		            				$renderer->doc .= 'refIllustration' . $this->_fignum;
		            			}
		    	                $renderer->doc .= '" text:name="Illustration" text:formula="ooow:Illustration+1" style:num-format="1">';
		    	                $renderer->doc .= ' ' . $this->_fignum . '</text:sequence>: ';
		                    	break;
                    		case table :
			                    $renderer->doc .= '<text:p text:style-name="Table">';
		    	                if ($this->getConf('abbrev')) {
		    	                	$renderer->doc .= $this->getLang('tableabbrev');
		    	                } else {
		    	                	$renderer->doc .= $this->getLang('tablelong');
		    	                }
		    	                $renderer->doc .= '<text:sequence text:ref-name="';
			            		if ($this->_label) {
					                $renderer->doc .= $this->_label;
					                $this->_label = '';
		            			} else {
		            				$renderer->doc .= 'refTable' . $this->_tabnum;
		            			}
		    	                $renderer->doc .= '" text:name="Table" text:formula="ooow:Table+1" style:num-format="1">';
		    	                $renderer->doc .= ' ' . $this->_tabnum . '</text:sequence>: ';
		                    	break;
						}
                	} else {
        	            $renderer->doc .= '</text:p>';
                		$this->_incaption = false;
						switch ($this->_type) {
							case figure :
								$renderer->p_open();
                	    		break;
            	        	case table :
//								$renderer->p_open();
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
						case figure :
                    		$this->_fignum++;
							$renderer->p_close();
                    		break;
                    	case table :
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
