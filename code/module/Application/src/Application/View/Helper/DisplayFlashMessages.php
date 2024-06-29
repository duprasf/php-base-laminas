<?php

namespace Application\View\Helper;

use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Helper\HelperInterface;
use Laminas\View\Renderer\RendererInterface;

/**
* Class to display the HTML Flash Messages received from the previous page (or current page)
*/
class DisplayFlashMessages implements HelperInterface
{
    private $view;
    public function setView(RendererInterface $view)
    {
        $this->view = $view;
    }
    public function getView()
    {
        return $this->view;
    }

    protected static $jsLoaded = false;

    public function __invoke($flashMessenger)
    {
        if(!$flashMessenger instanceof FlashMessenger) {
            return;
        }
        $html = '';
        if($flashMessenger->hasErrorMessages() || $flashMessenger->hasCurrentErrorMessages()) {
            $html .= $this->messageBox(array_merge($flashMessenger->getErrorMessages(), $flashMessenger->getCurrentErrorMessages()), FlashMessenger::NAMESPACE_ERROR);
        }
        if($flashMessenger->hasWarningMessages() || $flashMessenger->hasCurrentWarningMessages()) {
            $html .= $this->messageBox(array_merge($flashMessenger->getWarningMessages(), $flashMessenger->getCurrentWarningMessages()), FlashMessenger::NAMESPACE_WARNING);
        }
        if($flashMessenger->hasInfoMessages() || $flashMessenger->hasCurrentInfoMessages()) {
            $html .= $this->messageBox(array_merge($flashMessenger->getInfoMessages(), $flashMessenger->getCurrentInfoMessages()), FlashMessenger::NAMESPACE_INFO);
        }
        if($flashMessenger->hasSuccessMessages() || $flashMessenger->hasCurrentSuccessMessages()) {
            $html .= $this->messageBox(array_merge($flashMessenger->getSuccessMessages(), $flashMessenger->getCurrentSuccessMessages()), FlashMessenger::NAMESPACE_SUCCESS);
        }
        if($flashMessenger->hasMessages() || $flashMessenger->hasCurrentMessages()) {
            $html .= $this->messageBox(array_merge($flashMessenger->getMessages(), $flashMessenger->getCurrentMessages()), FlashMessenger::NAMESPACE_DEFAULT);
        }

        $flashMessenger->clearMessagesFromNamespace(FlashMessenger::NAMESPACE_DEFAULT);
        $flashMessenger->clearMessagesFromNamespace(FlashMessenger::NAMESPACE_ERROR);
        $flashMessenger->clearMessagesFromNamespace(FlashMessenger::NAMESPACE_WARNING);
        $flashMessenger->clearMessagesFromNamespace(FlashMessenger::NAMESPACE_INFO);
        $flashMessenger->clearMessagesFromNamespace(FlashMessenger::NAMESPACE_SUCCESS);
        $flashMessenger->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_DEFAULT);
        $flashMessenger->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_ERROR);
        $flashMessenger->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_WARNING);
        $flashMessenger->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_INFO);
        $flashMessenger->clearCurrentMessagesFromNamespace(FlashMessenger::NAMESPACE_SUCCESS);

        if(!static::$jsLoaded) {
            $this->javascript();
        }
        return $html;
    }

    public function messageBox($message, $namespace = FlashMessenger::NAMESPACE_DEFAULT)
    {
        switch($namespace) {
            case FlashMessenger::NAMESPACE_ERROR:
                $class = "module-alert alert alert-danger";
                break;
            case FlashMessenger::NAMESPACE_WARNING:
                $class = "module-alert alert alert-warning";
                break;
            case FlashMessenger::NAMESPACE_INFO:
                $class = "module-info alert alert-info";
                break;
            case 'warning':
                $class = "module-info alert alert-warning";
                break;
            case FlashMessenger::NAMESPACE_SUCCESS:
                $class = "module-summary module-simplify alert alert-success";
                break;
            case FlashMessenger::NAMESPACE_DEFAULT:
            default:
                $class = "module-info module-simplify alert alert-info";
                break;
        }
        // <button type="button" class="close" onclick="flashMessenger.remove(this.parentNode);"><span aria-hidden="true">&times;</span><span class="sr-only">'.$this->view->translate('close').'</span></button>
        $html = '<div class="flashMessenger '.$class.'"><div>'.(is_array($message) ? implode('<br>', array_merge($message)) : $message).'</div></div>';

        return $html;
    }

    /**
    * Set the Javascript required for the flash messenger to work in the page headScript
    *
    */
    public function javascript()
    {
        if(static::$jsLoaded) {
            return;
        }

        static::$jsLoaded = true;
        ob_start();?>
        var flashMessenger = (function() {
            this.flashMessenger = this;
            this.flashContainer = 'flashMessengerGroup';

            this.addErrorMessage = function(message, parent){ return this.addMessage(message, 'error', parent);}
            this.addInfoMessage = function(message, parent){ return this.addMessage(message, 'info', parent);}
            this.addWarningMessage = function(message, parent){ return this.addMessage(message, 'warning', parent);}
            this.addSuccessMessage = function(message, parent){ return this.addMessage(message, 'success', parent);}
            this.addDefaultMessage = function(message, parent){ return this.addMessage(message, 'default', parent);}
            this.addMessage = function(message, type, parent) {
                if(parent == undefined) parent = this.flashContainer;
                if(typeof(parent) == 'string') parent = document.getElementById(parent);
                if(parent) {
                    switch(type) {
                        case 'error':  template = '<?=$this->messageBox('[[MESSAGE]]', FlashMessenger::NAMESPACE_ERROR);?>';break;
                        case 'warning':  template = '<?=$this->messageBox('[[MESSAGE]]', 'warning');?>';break;
                        case 'info':   template = '<?=$this->messageBox('[[MESSAGE]]', FlashMessenger::NAMESPACE_INFO);?>';break;
                        case 'success':template = '<?=$this->messageBox('[[MESSAGE]]', FlashMessenger::NAMESPACE_SUCCESS);?>';break;
                        default:
                            template = '<?=$this->messageBox('[[MESSAGE]]', FlashMessenger::NAMESPACE_DEFAULT);?>';
                            break;
                    }
                    var child = $(template.replace('[[MESSAGE]]', message));
                    child.hide();
                    $(parent).append(child);
                    child.slideDown(600);
                }
                else { return false; }
            }

            this.remove = function(messenger) {
                if(!((messenger.classList && !messenger.classList.contains('flashMessenger')) || !messenger.className.match(/\bflashMessenger\b/))) {
                    //messenger.parentNode.removeChild(messenger);
                    $(messenger).slideUp(600, function(){this.remove();});
                }
            }

            return this;
        })();
        <?php
        $this->getView()->headScript()->appendScript(ob_get_clean());
    }
}
