<?php
/**
 * DokuWiki Plugin caption (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Till Biskup <till@till-biskup.de>
 */
 
class action_plugin_caption extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook("TOOLBAR_DEFINE", "AFTER", $this, "insert_button", array ());
    }

    /**
    * Inserts a toolbar button
    */
    public function insert_button(&$event, $param) {
        $event->data[] = array (
            'type' => 'picker',
            'title' => $this->getLang('picker'),
            'icon' => '../../plugins/caption/images/picker.png',
            'class' => 'captionpicker',
            'list' => array(
                array(
                     'type' => 'format',
                     'title' => $this->getLang('figure'),
                     'icon' => '../../plugins/caption/images/fig.png',
                     'open' => '<figure fig_label>\n',
                     'sample' => '{{:img |title}}',
                     'close' => '\n<caption>caption</caption>\n</figure>',
                ),
                array(
                     'type' => 'format',
                     'title' => $this->getLang('table'),
                     'icon' => '../../plugins/caption/images/tab.png',
                     'open' => '<table tab_label>\n<caption>caption</caption>\n',
                     'sample' => '^ Header1 ^ Header2 ^\n| foo    | bar    |\n',
                     'close' => '</table>',
                ),
                array(
                     'type' => 'format',
                     'title' => $this->getLang('code'),
                     'icon' => '../../plugins/caption/images/code.png',
                     'open' => '<codeblock code_label>\n<caption>caption</caption>\n',
                     'sample' => '<code>\n...\n</code>\n',
                     'close' => '</codeblock>',
                ),
                array(
                     'type' => 'format',
                     'title' => $this->getLang('file'),
                     'icon' => '../../plugins/caption/images/file.png',
                     'open' => '<fileblock file_label>\n<caption>caption</caption>\n',
                     'sample' => '<file "" foo.txt>\n...\n</file>\n',
                     'close' => '</fileblock>',
                ),
                array(
                     'type' => 'insert',
                     'title' => $this->getLang('reference'),
                     'icon' => '../../plugins/caption/images/ref.png',
                     'insert' => '{{ref>label}}',
                )
            )
        );
    }
}
